<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\DocumentItem;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use App\Repository\ClientRepository;
use App\Repository\ProformaRepository;
use App\Repository\CompanySettingsRepository;
use App\Service\BrevoMailerService;
use App\Service\PdfGeneratorService;
use App\Service\NumberToWordsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invoices')]
#[IsGranted('ROLE_VIEWER')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private InvoiceRepository $invoiceRepository,
        private ClientRepository $clientRepository,
        private ProformaRepository $proformaRepository,
        private CompanySettingsRepository $settingsRepository,
        private PdfGeneratorService $pdfGenerator,
        private NumberToWordsService $numberToWords
    ) {}

    #[Route('', name: 'app_invoice_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $clientId = $request->query->get('client', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->addSelect('c');

        if ($search) {
            $qb->andWhere('i.reference LIKE :search OR c.name LIKE :search OR i.object LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status && in_array($status, array_keys(Invoice::STATUSES))) {
            $qb->andWhere('i.status = :status')
                ->setParameter('status', $status);
        }

        if ($clientId) {
            $qb->andWhere('i.client = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($dateFrom) {
            $qb->andWhere('i.issueDate >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo) {
            $qb->andWhere('i.issueDate <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }

        $qb->orderBy('i.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            15
        );

        // Stats simples - using constant values
        $stats = [
            'draft' => $this->invoiceRepository->count(['status' => Invoice::STATUS_DRAFT]),
            'sent' => $this->invoiceRepository->count(['status' => Invoice::STATUS_SENT]),
            'partial' => $this->invoiceRepository->count(['status' => Invoice::STATUS_PARTIAL]),
            'overdue' => $this->invoiceRepository->count(['status' => Invoice::STATUS_OVERDUE]),
            'paid' => $this->invoiceRepository->count(['status' => Invoice::STATUS_PAID]),
        ];

        return $this->render('invoice/index.html.twig', [
            'invoices' => $pagination,
            'stats' => $stats,
            'statuses' => Invoice::STATUSES,
            'clients' => $this->clientRepository->findBy(['isArchived' => false], ['name' => 'ASC']),
        ]);
    }

    #[Route('/from-proforma', name: 'app_invoice_from_proforma', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function fromProforma(): Response
    {
        $proformas = $this->proformaRepository->createQueryBuilder('p')
            ->leftJoin('p.invoice', 'i')
            ->where('i.id IS NULL')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('statuses', ['DRAFT', 'SENT', 'ACCEPTED'])
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('invoice/from_proforma.html.twig', [
            'proformas' => $proformas,
        ]);
    }

    #[Route('/new', name: 'app_invoice_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function new(Request $request): Response
    {
        $settings = $this->settingsRepository->getSettings();
        
        $invoice = new Invoice();
        $invoice->setIssueDate(new \DateTime());
        $invoice->setDueDate((new \DateTime())->modify('+' . ($settings?->getDefaultPaymentDays() ?? 30) . ' days'));
        $invoice->setTaxRate((string) ($settings?->getDefaultTaxRate() ?? 19.25));
        $invoice->setConditions($settings?->getDefaultInvoiceConditions());
        $invoice->setPaymentTerms($settings?->getDefaultPaymentTerms());

        $clientId = $request->query->get('client');
        if ($clientId) {
            $client = $this->clientRepository->find($clientId);
            if ($client) {
                $invoice->setClient($client);
            }
        }

        $item = new DocumentItem();
        $item->setQuantity('1');
        $item->setDiscount('0');
        $invoice->addItem($item);

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->setCreatedBy($this->getUser());
            $invoice->setReference($this->generateReference($settings));
            $this->calculateTotals($invoice);

            $this->em->persist($invoice);
            $this->em->flush();

            $this->addFlash('success', 'Facture créée avec succès.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/new.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_invoice_show', methods: ['GET'])]
    public function show(Invoice $invoice): Response
    {
        $settings = $this->settingsRepository->getSettings();
        
        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
            'settings' => $settings,
            'totalInWords' => $this->numberToWords->convert($invoice->getTotalTTCFloat(), $settings?->getCurrency() ?? 'FCFA'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_invoice_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function edit(Request $request, Invoice $invoice): Response
    {
        if ($invoice->getStatus() === Invoice::STATUS_PAID) {
            $this->addFlash('error', 'Une facture payée ne peut pas être modifiée.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->calculateTotals($invoice);
            $this->em->flush();

            $this->addFlash('success', 'Facture mise à jour.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_invoice_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Invoice $invoice): Response
    {
        if ($this->isCsrfTokenValid('delete' . $invoice->getId(), $request->request->get('_token'))) {
            $this->em->remove($invoice);
            $this->em->flush();
            $this->addFlash('success', 'Facture supprimée.');
        }

        return $this->redirectToRoute('app_invoice_index');
    }

    #[Route('/{id}/pdf', name: 'app_invoice_pdf', methods: ['GET'])]
    public function pdf(Invoice $invoice): Response
    {
        $pdfContent = $this->pdfGenerator->generateInvoicePdf($invoice);
        
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Facture_' . $invoice->getReference() . '.pdf"',
        ]);
    }

    #[Route('/{id}/pdf/download', name: 'app_invoice_pdf_download', methods: ['GET'])]
    public function pdfDownload(Invoice $invoice): Response
    {
        $pdfContent = $this->pdfGenerator->generateInvoicePdf($invoice);
        
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Facture_' . $invoice->getReference() . '.pdf"',
        ]);
    }

    #[Route('/{id}/send-email', name: 'app_invoice_send_email', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function sendEmail(Request $request, Invoice $invoice, BrevoMailerService $mailer): Response
    {
        $client = $invoice->getClient();

        if (!$client?->getEmail()) {
            $this->addFlash('error', 'Ce client n\'a pas d\'adresse email.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject', 'Facture ' . $invoice->getReference());
            $message = $request->request->get('message', '');
            $attachPdf = $request->request->getBoolean('attach_pdf', true);

            $settings = $this->settingsRepository->getSettings();
            $companyName = $settings?->getCompanyName() ?? 'KTC-Center';
            $currency = $settings?->getCurrency() ?? 'FCFA';

            $pdfContent = null;
            $pdfFilename = null;
            if ($attachPdf) {
                $pdfContent = $this->pdfGenerator->generateInvoicePdf($invoice, false);
                $pdfFilename = 'Facture_' . $invoice->getReference() . '.pdf';
            }

            $success = $mailer->sendDocumentEmail(
                $client->getEmail(),
                $subject,
                $companyName,
                $client->getName(),
                'facture',
                $invoice->getReference(),
                $invoice->getTotalTTCFloat(),
                $currency,
                $message,
                $pdfContent,
                $pdfFilename
            );

            if ($success) {
                if ($invoice->getStatus() === Invoice::STATUS_DRAFT) {
                    $invoice->setStatus(Invoice::STATUS_SENT);
                    $this->em->flush();
                }
                $this->addFlash('success', 'Facture envoyée par email à ' . $client->getEmail());
                return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email.');
            }
        }

        return $this->render('invoice/send_email.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/whatsapp', name: 'app_invoice_whatsapp', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function whatsapp(Invoice $invoice): Response
    {
        $settings = $this->settingsRepository->getSettings();
        $client = $invoice->getClient();
        $phone = $client?->getPhone();

        if (!$phone) {
            $this->addFlash('error', 'Ce client n\'a pas de numéro de téléphone.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 3) !== '237') {
            $phone = '237' . $phone;
        }

        $companyName = $settings?->getCompanyName() ?? 'KTC-Center';
        $currency = $settings?->getCurrency() ?? 'FCFA';

        $message = sprintf(
            "Bonjour %s,\n\nVoici votre facture *%s* d'un montant de *%s %s*.\n\nCordialement,\n%s",
            $client->getName(),
            $invoice->getReference(),
            number_format($invoice->getTotalTTCFloat(), 0, ',', ' '),
            $currency,
            $companyName
        );

        $url = 'https://wa.me/' . $phone . '?text=' . urlencode($message);

        return $this->redirect($url);
    }

    #[Route('/{id}/change-status/{status}', name: 'app_invoice_change_status', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function changeStatus(Request $request, Invoice $invoice, string $status): Response
    {
        if ($this->isCsrfTokenValid('status' . $invoice->getId(), $request->request->get('_token'))) {
            $validStatuses = array_keys(Invoice::STATUSES);

            if (in_array($status, $validStatuses)) {
                $invoice->setStatus($status);
                
                if ($status === Invoice::STATUS_PAID) {
                    $invoice->setAmountPaid((string) $invoice->getTotalTTCFloat());
                    $invoice->setPaidAt(new \DateTime());
                }
                
                $this->em->flush();
                $this->addFlash('success', 'Statut mis à jour.');
            }
        }

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/payment', name: 'app_invoice_payment', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function payment(Request $request, Invoice $invoice): Response
    {
        if ($invoice->getStatus() === Invoice::STATUS_PAID) {
            $this->addFlash('warning', 'Cette facture est déjà payée.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($request->isMethod('POST')) {
            $amount = (float) $request->request->get('amount', 0);
            $paymentMethod = $request->request->get('payment_method', '');
            $paymentReference = $request->request->get('payment_reference', '');

            if ($amount <= 0) {
                $this->addFlash('error', 'Le montant doit être supérieur à 0.');
                return $this->redirectToRoute('app_invoice_payment', ['id' => $invoice->getId()]);
            }

            $currentPaid = $invoice->getAmountPaidFloat();
            $newTotal = $currentPaid + $amount;
            $totalTTC = $invoice->getTotalTTCFloat();

            $invoice->setAmountPaid((string) $newTotal);
            $invoice->setPaymentMethod($paymentMethod);
            $invoice->setPaymentReference($paymentReference);

            if ($newTotal >= $totalTTC) {
                $invoice->setStatus(Invoice::STATUS_PAID);
                $invoice->setPaidAt(new \DateTime());
            } elseif ($newTotal > 0) {
                $invoice->setStatus(Invoice::STATUS_PARTIAL);
            }

            $this->em->flush();

            $this->addFlash('success', sprintf('Paiement de %s FCFA enregistré.', number_format($amount, 0, ',', ' ')));
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/payment.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'app_invoice_duplicate', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function duplicate(Request $request, Invoice $invoice): Response
    {
        if ($this->isCsrfTokenValid('duplicate' . $invoice->getId(), $request->request->get('_token'))) {
            $settings = $this->settingsRepository->getSettings();
            
            $newInvoice = new Invoice();
            $newInvoice->setClient($invoice->getClient());
            $newInvoice->setCreatedBy($this->getUser());
            $newInvoice->setReference($this->generateReference($settings));
            $newInvoice->setIssueDate(new \DateTime());
            $newInvoice->setDueDate((new \DateTime())->modify('+' . ($settings?->getDefaultPaymentDays() ?? 30) . ' days'));
            $newInvoice->setTaxRate($invoice->getTaxRate());
            $newInvoice->setObject($invoice->getObject());
            $newInvoice->setNotes($invoice->getNotes());
            $newInvoice->setConditions($invoice->getConditions());
            $newInvoice->setPaymentTerms($invoice->getPaymentTerms());

            foreach ($invoice->getItems() as $item) {
                $newItem = new DocumentItem();
                $newItem->setProduct($item->getProduct());
                $newItem->setDesignation($item->getDesignation());
                $newItem->setDescription($item->getDescription());
                $newItem->setQuantity($item->getQuantity());
                $newItem->setUnit($item->getUnit());
                $newItem->setUnitPrice($item->getUnitPrice());
                $newItem->setDiscount($item->getDiscount());
                $newItem->setTotal($item->getTotal());
                $newItem->setSortOrder($item->getSortOrder());
                $newInvoice->addItem($newItem);
            }

            $this->calculateTotals($newInvoice);

            $this->em->persist($newInvoice);
            $this->em->flush();

            $this->addFlash('success', 'Facture dupliquée.');
            return $this->redirectToRoute('app_invoice_edit', ['id' => $newInvoice->getId()]);
        }

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    private function generateReference($settings): string
    {
        $prefix = $settings?->getInvoicePrefix() ?? 'FAC';
        $year = date('Y');
        
        $currentNumber = ($settings?->getInvoiceCurrentNumber() ?? 0) + 1;
        
        if ($settings) {
            $settings->setInvoiceCurrentNumber($currentNumber);
            $this->em->flush();
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $currentNumber);
    }

    private function calculateTotals(Invoice $invoice): void
    {
        $totalHT = 0;

        foreach ($invoice->getItems() as $item) {
            $qty = (float) $item->getQuantity();
            $price = (float) $item->getUnitPrice();
            $discount = (float) $item->getDiscount();

            $lineTotal = $qty * $price * (1 - $discount / 100);
            $item->setTotal((string) $lineTotal);
            $totalHT += $lineTotal;
        }

        $taxRate = (float) $invoice->getTaxRate();
        $totalTVA = $totalHT * $taxRate / 100;
        $totalTTC = $totalHT + $totalTVA;

        $invoice->setTotalHT((string) $totalHT);
        $invoice->setTotalTVA((string) $totalTVA);
        $invoice->setTotalTTC((string) $totalTTC);
    }
}
