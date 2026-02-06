<?php

namespace App\Controller;

use App\Entity\Proforma;
use App\Entity\DocumentItem;
use App\Entity\Invoice;
use App\Form\ProformaType;
use App\Repository\ProformaRepository;
use App\Repository\ClientRepository;
use App\Repository\ProformaTemplateRepository;
use App\Service\ReferenceGeneratorService;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/proformas')]
#[IsGranted('ROLE_VIEWER')]
class ProformaController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProformaRepository $proformaRepository,
        private ClientRepository $clientRepository,
        private ProformaTemplateRepository $templateRepository,
        private ReferenceGeneratorService $referenceGenerator,
        private PdfGeneratorService $pdfGenerator
    ) {}

    #[Route('', name: 'app_proforma_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $clientId = $request->query->get('client', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        $queryBuilder = $this->proformaRepository->createQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->addSelect('c');

        if ($search) {
            $queryBuilder->andWhere('p.reference LIKE :search OR c.name LIKE :search OR p.object LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status && in_array($status, array_keys(Proforma::STATUSES))) {
            $queryBuilder->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        if ($clientId) {
            $queryBuilder->andWhere('p.client = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($dateFrom) {
            $queryBuilder->andWhere('p.issueDate >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo) {
            $queryBuilder->andWhere('p.issueDate <= :dateTo')
                ->setParameter('dateTo', new \DateTime($dateTo));
        }

        $queryBuilder->orderBy('p.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('proforma/index.html.twig', [
            'proformas' => $pagination,
            'search' => $search,
            'status' => $status,
            'clientId' => $clientId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statuses' => Proforma::STATUSES,
            'clients' => $this->clientRepository->findBy(['isArchived' => false], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_proforma_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function new(Request $request): Response
    {
        $proforma = new Proforma();
        $proforma->setReference($this->referenceGenerator->generateProformaReference());
        $proforma->setCreatedBy($this->getUser());

        // Pré-remplir depuis un template si spécifié
        $templateId = $request->query->get('template');
        if ($templateId) {
            $template = $this->templateRepository->find($templateId);
            if ($template) {
                $proforma->setTemplate($template);
                $proforma->setObject($template->getName());
                $proforma->setConditions($template->getDefaultConditions());
                // Note: taxRate is set at proforma level, not from template

                // Copier les lignes du template
                foreach ($template->getItems() as $templateItem) {
                    $item = new DocumentItem();
                    $item->setProduct($templateItem->getProduct());
                    $item->setDesignation($templateItem->getDesignation());
                    $item->setDescription($templateItem->getDescription());
                    $item->setQuantity($templateItem->getQuantity());
                    $item->setUnitPrice($templateItem->getUnitPrice());
                    $item->setDiscount($templateItem->getDiscount());
                    $item->setSortOrder($templateItem->getSortOrder());
                    $proforma->addItem($item);
                }
            }
        }

        // Pré-sélectionner un client si spécifié
        $clientId = $request->query->get('client');
        if ($clientId) {
            $client = $this->clientRepository->find($clientId);
            if ($client) {
                $proforma->setClient($client);
            }
        }

        $form = $this->createForm(ProformaType::class, $proforma);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proforma->calculateTotals();
            $this->entityManager->persist($proforma);
            $this->entityManager->flush();

            $this->addFlash('success', 'Proforma créée avec succès.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        return $this->render('proforma/new.html.twig', [
            'proforma' => $proforma,
            'form' => $form,
            'templates' => $this->templateRepository->findBy(['isActive' => true], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{id}', name: 'app_proforma_show', methods: ['GET'])]
    public function show(Proforma $proforma): Response
    {
        return $this->render('proforma/show.html.twig', [
            'proforma' => $proforma,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proforma_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function edit(Request $request, Proforma $proforma): Response
    {
        if (!$proforma->canBeEdited()) {
            $this->addFlash('error', 'Cette proforma ne peut plus être modifiée.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        $form = $this->createForm(ProformaType::class, $proforma);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $proforma->calculateTotals();
            $this->entityManager->flush();

            $this->addFlash('success', 'Proforma modifiée avec succès.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        return $this->render('proforma/edit.html.twig', [
            'proforma' => $proforma,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_proforma_status', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function changeStatus(Request $request, Proforma $proforma, string $status): Response
    {
        if (!$this->isCsrfTokenValid('status' . $proforma->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        if (!in_array($status, array_keys(Proforma::STATUSES))) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        $proforma->setStatus($status);
        $this->entityManager->flush();

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
    }

    #[Route('/{id}/duplicate', name: 'app_proforma_duplicate', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function duplicate(Request $request, Proforma $proforma): Response
    {
        if (!$this->isCsrfTokenValid('duplicate' . $proforma->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        $newProforma = new Proforma();
        $newProforma->setReference($this->referenceGenerator->generateProformaReference());
        $newProforma->setClient($proforma->getClient());
        $newProforma->setCreatedBy($this->getUser());
        $newProforma->setObject($proforma->getObject());
        $newProforma->setNotes($proforma->getNotes());
        $newProforma->setConditions($proforma->getConditions());
        $newProforma->setTaxRate($proforma->getTaxRate());
        $newProforma->setTemplate($proforma->getTemplate());

        // Copier les lignes
        foreach ($proforma->getItems() as $item) {
            $newItem = new DocumentItem();
            $newItem->setProduct($item->getProduct());
            $newItem->setDesignation($item->getDesignation());
            $newItem->setDescription($item->getDescription());
            $newItem->setQuantity($item->getQuantity());
            $newItem->setUnitPrice($item->getUnitPrice());
            $newItem->setDiscount($item->getDiscount());
            $newItem->setSortOrder($item->getSortOrder());
            $newProforma->addItem($newItem);
        }

        $newProforma->calculateTotals();
        $this->entityManager->persist($newProforma);
        $this->entityManager->flush();

        $this->addFlash('success', 'Proforma dupliquée avec succès.');
        return $this->redirectToRoute('app_proforma_edit', ['id' => $newProforma->getId()]);
    }

    #[Route('/{id}/convert', name: 'app_proforma_convert', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function convertToInvoice(Request $request, Proforma $proforma): Response
    {
        if (!$this->isCsrfTokenValid('convert' . $proforma->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        if (!$proforma->canBeConverted()) {
            $this->addFlash('error', 'Cette proforma ne peut pas être convertie en facture.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        // Créer la facture
        $invoice = new Invoice();
        $invoice->setReference($this->referenceGenerator->generateInvoiceReference());
        $invoice->setClient($proforma->getClient());
        $invoice->setProforma($proforma);
        $invoice->setCreatedBy($this->getUser());
        $invoice->setObject($proforma->getObject());
        $invoice->setNotes($proforma->getNotes());
        $invoice->setConditions($proforma->getConditions());
        $invoice->setTaxRate($proforma->getTaxRate());
        $invoice->setDueDate(new \DateTime('+30 days'));

        // Copier les lignes
        foreach ($proforma->getItems() as $item) {
            $newItem = new DocumentItem();
            $newItem->setProduct($item->getProduct());
            $newItem->setDesignation($item->getDesignation());
            $newItem->setDescription($item->getDescription());
            $newItem->setQuantity($item->getQuantity());
            $newItem->setUnitPrice($item->getUnitPrice());
            $newItem->setDiscount($item->getDiscount());
            $newItem->setSortOrder($item->getSortOrder());
            $invoice->addItem($newItem);
        }

        $invoice->calculateTotals();

        // Mettre à jour la proforma
        $proforma->setStatus(Proforma::STATUS_INVOICED);
        $proforma->setInvoice($invoice);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->addFlash('success', 'Facture créée avec succès.');
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/pdf', name: 'app_proforma_pdf', methods: ['GET'])]
    public function downloadPdf(Proforma $proforma): Response
    {
        $pdfContent = $this->pdfGenerator->generateProformaPdf($proforma, false);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="proforma_%s.pdf"', $proforma->getReference()),
        ]);
    }

    #[Route('/{id}/preview', name: 'app_proforma_preview', methods: ['GET'])]
    public function preview(Proforma $proforma): Response
    {
        return new Response($this->pdfGenerator->generateProformaPreview($proforma));
    }

    #[Route('/{id}/delete', name: 'app_proforma_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Proforma $proforma): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $proforma->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        if ($proforma->hasInvoice()) {
            $this->addFlash('error', 'Impossible de supprimer une proforma convertie en facture.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        $this->entityManager->remove($proforma);
        $this->entityManager->flush();

        $this->addFlash('success', 'Proforma supprimée.');
        return $this->redirectToRoute('app_proforma_index');
    }

    #[Route('/{id}/send-email', name: 'app_proforma_send_email', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function sendEmail(Request $request, Proforma $proforma, \App\Service\BrevoMailerService $mailer, \App\Repository\CompanySettingsRepository $settingsRepo): Response
    {
        $client = $proforma->getClient();
        
        if (!$client->getEmail()) {
            $this->addFlash('error', 'Ce client n\'a pas d\'adresse email.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        if ($request->isMethod('POST')) {
            $subject = $request->request->get('subject', 'Proforma ' . $proforma->getReference());
            $message = $request->request->get('message', '');
            $attachPdf = $request->request->getBoolean('attach_pdf', true);

            $settings = $settingsRepo->getOrCreateSettings();
            $companyName = $settings->getCompanyName() ?? 'KTC-Center';

            // Generate PDF if needed
            $pdfContent = null;
            $pdfFilename = null;
            if ($attachPdf) {
                $pdfContent = $this->pdfGenerator->generateProformaPdf($proforma, false);
                $pdfFilename = 'Proforma_' . $proforma->getReference() . '.pdf';
            }

            // Send email
            $success = $mailer->sendDocumentEmail(
                $client->getEmail(),
                $subject,
                $companyName,
                $client->getName(),
                'proforma',
                $proforma->getReference(),
                $proforma->getTotalTTCFloat(),
                $settings->getCurrency() ?? 'FCFA',
                $message,
                $pdfContent,
                $pdfFilename
            );

            if ($success) {
                $this->addFlash('success', 'Proforma envoyée par email à ' . $client->getEmail());
                return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email.');
            }
        }

        return $this->render('proforma/send_email.html.twig', [
            'proforma' => $proforma,
        ]);
    }
}
