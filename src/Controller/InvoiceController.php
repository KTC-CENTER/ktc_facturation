<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\DocumentItem;
use App\Form\InvoiceType;
use App\Form\PaymentType;
use App\Repository\InvoiceRepository;
use App\Repository\ClientRepository;
use App\Service\ReferenceGeneratorService;
use App\Service\PdfGeneratorService;
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
        private EntityManagerInterface $entityManager,
        private InvoiceRepository $invoiceRepository,
        private ClientRepository $clientRepository,
        private ReferenceGeneratorService $referenceGenerator,
        private PdfGeneratorService $pdfGenerator
    ) {}

    #[Route('', name: 'app_invoice_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $clientId = $request->query->get('client', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        $queryBuilder = $this->invoiceRepository->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->addSelect('c');

        if ($search) {
            $queryBuilder->andWhere('i.reference LIKE :search OR c.name LIKE :search OR i.object LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status && in_array($status, array_keys(Invoice::STATUSES))) {
            $queryBuilder->andWhere('i.status = :status')
                ->setParameter('status', $status);
        }

        if ($clientId) {
            $queryBuilder->andWhere('i.client = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($dateFrom) {
            $queryBuilder->andWhere('i.issueDate >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo) {
            $queryBuilder->andWhere('i.issueDate <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }

        $queryBuilder->orderBy('i.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('invoice/index.html.twig', [
            'invoices' => $pagination,
            'search' => $search,
            'status' => $status,
            'clientId' => $clientId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statuses' => Invoice::STATUSES,
            'clients' => $this->clientRepository->findBy(['isArchived' => false], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_invoice_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function new(Request $request): Response
    {
        $invoice = new Invoice();
        $invoice->setReference($this->referenceGenerator->generateInvoiceReference());
        $invoice->setCreatedBy($this->getUser());

        // Pré-sélectionner un client si spécifié
        $clientId = $request->query->get('client');
        if ($clientId) {
            $client = $this->clientRepository->find($clientId);
            if ($client) {
                $invoice->setClient($client);
            }
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->calculateTotals();
            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

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
        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_invoice_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function edit(Request $request, Invoice $invoice): Response
    {
        if (!$invoice->canBeEdited()) {
            $this->addFlash('error', 'Cette facture ne peut plus être modifiée.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->calculateTotals();
            $this->entityManager->flush();

            $this->addFlash('success', 'Facture modifiée avec succès.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_invoice_status', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function changeStatus(Request $request, Invoice $invoice, string $status): Response
    {
        if (!$this->isCsrfTokenValid('status' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        if (!in_array($status, array_keys(Invoice::STATUSES))) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $invoice->setStatus($status);

        // Si payée, mettre à jour la date de paiement
        if ($status === Invoice::STATUS_PAID) {
            $invoice->setPaidAt(new \DateTime());
            $invoice->setAmountPaid($invoice->getTotalTTC());
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/payment', name: 'app_invoice_payment', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function recordPayment(Request $request, Invoice $invoice): Response
    {
        if (!$this->isCsrfTokenValid('payment' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $amount = (float) $request->request->get('amount', 0);
        $paymentMethod = $request->request->get('payment_method', '');
        $paymentReference = $request->request->get('payment_reference', '');

        if ($amount <= 0) {
            $this->addFlash('error', 'Le montant doit être positif.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
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

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Paiement de %s FCFA enregistré.', number_format($amount, 0, ',', ' ')));
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/duplicate', name: 'app_invoice_duplicate', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function duplicate(Request $request, Invoice $invoice): Response
    {
        if (!$this->isCsrfTokenValid('duplicate' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $newInvoice = new Invoice();
        $newInvoice->setReference($this->referenceGenerator->generateInvoiceReference());
        $newInvoice->setClient($invoice->getClient());
        $newInvoice->setCreatedBy($this->getUser());
        $newInvoice->setObject($invoice->getObject());
        $newInvoice->setNotes($invoice->getNotes());
        $newInvoice->setConditions($invoice->getConditions());
        $newInvoice->setTaxRate($invoice->getTaxRate());
        $newInvoice->setDueDate(new \DateTime('+30 days'));

        // Copier les lignes
        foreach ($invoice->getItems() as $item) {
            $newItem = new DocumentItem();
            $newItem->setProduct($item->getProduct());
            $newItem->setDesignation($item->getDesignation());
            $newItem->setDescription($item->getDescription());
            $newItem->setQuantity($item->getQuantity());
            $newItem->setUnitPrice($item->getUnitPrice());
            $newItem->setDiscount($item->getDiscount());
            $newItem->setSortOrder($item->getSortOrder());
            $newInvoice->addItem($newItem);
        }

        $newInvoice->calculateTotals();
        $this->entityManager->persist($newInvoice);
        $this->entityManager->flush();

        $this->addFlash('success', 'Facture dupliquée avec succès.');
        return $this->redirectToRoute('app_invoice_edit', ['id' => $newInvoice->getId()]);
    }

    #[Route('/{id}/pdf', name: 'app_invoice_pdf', methods: ['GET'])]
    public function downloadPdf(Invoice $invoice): Response
    {
        $pdfContent = $this->pdfGenerator->generateInvoicePdf($invoice, false);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="facture_%s.pdf"', $invoice->getReference()),
        ]);
    }

    #[Route('/{id}/preview', name: 'app_invoice_preview', methods: ['GET'])]
    public function preview(Invoice $invoice): Response
    {
        return new Response($this->pdfGenerator->generateInvoicePreview($invoice));
    }

    #[Route('/{id}/cancel', name: 'app_invoice_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function cancel(Request $request, Invoice $invoice): Response
    {
        if (!$this->isCsrfTokenValid('cancel' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $invoice->setStatus(Invoice::STATUS_CANCELLED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Facture annulée.');
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_invoice_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Invoice $invoice): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($invoice->getStatus() === Invoice::STATUS_PAID) {
            $this->addFlash('error', 'Impossible de supprimer une facture payée.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        // Délier la proforma si elle existe
        if ($invoice->getProforma()) {
            $proforma = $invoice->getProforma();
            $proforma->setInvoice(null);
            $proforma->setStatus('ACCEPTED');
        }

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        $this->addFlash('success', 'Facture supprimée.');
        return $this->redirectToRoute('app_invoice_index');
    }

    #[Route('/{id}/send-email', name: 'app_invoice_send_email', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function sendEmail(Request $request, Invoice $invoice, \App\Service\BrevoMailerService $mailer, \App\Service\PdfGeneratorService $pdfService, \App\Repository\CompanySettingsRepository $settingsRepo): Response
    {
        $client = $invoice->getClient();
        
        if (!$client->getEmail()) {
            $this->addFlash('error', 'Ce client n\'a pas d\'adresse email.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject', 'Facture ' . $invoice->getReference());
            $message = $request->request->get('message', '');
            $attachPdf = $request->request->getBoolean('attach_pdf', true);

            $settings = $settingsRepo->getOrCreateSettings();
            $companyName = $settings->getCompanyName() ?? 'KTC-Center';

            // Generate PDF if needed
            $pdfContent = null;
            $pdfFilename = null;
            if ($attachPdf) {
                $pdfContent = $pdfService->generateInvoicePdf($invoice, false);
                $pdfFilename = 'Facture_' . $invoice->getReference() . '.pdf';
            }

            // Send email
            $success = $mailer->sendDocumentEmail(
                $client->getEmail(),
                $subject,
                $companyName,
                $client->getName(),
                'facture',
                $invoice->getReference(),
                $invoice->getTotalTTCFloat(),
                $settings->getCurrency() ?? 'FCFA',
                $message,
                $pdfContent,
                $pdfFilename
            );

            if ($success) {
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
}
