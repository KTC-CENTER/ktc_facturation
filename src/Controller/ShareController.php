<?php

namespace App\Controller;

use App\Entity\DocumentShare;
use App\Entity\Proforma;
use App\Entity\Invoice;
use App\Repository\DocumentShareRepository;
use App\Service\DocumentShareService;
use App\Service\BrevoMailerService;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ShareController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentShareRepository $shareRepository,
        private DocumentShareService $shareService,
        private BrevoMailerService $mailerService,
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Page publique de visualisation d'un document partagé
     */
    #[Route('/share/{token}', name: 'app_share_view', methods: ['GET'])]
    public function view(string $token): Response
    {
        $share = $this->shareService->validateShare($token);

        if (!$share) {
            return $this->render('share/expired.html.twig');
        }

        $document = $share->getProforma() ?? $share->getInvoice();
        $type = $share->getProforma() ? 'proforma' : 'invoice';

        return $this->render('share/view.html.twig', [
            'share' => $share,
            'document' => $document,
            'type' => $type,
            'qrCode' => $this->shareService->generateQrCode($share),
        ]);
    }

    /**
     * Téléchargement PDF d'un document partagé
     */
    #[Route('/share/{token}/pdf', name: 'app_share_pdf', methods: ['GET'])]
    public function downloadPdf(string $token): Response
    {
        $share = $this->shareService->validateShare($token);

        if (!$share) {
            return $this->render('share/expired.html.twig');
        }

        if ($share->getProforma()) {
            $pdfContent = $this->pdfGenerator->generateProformaPdf($share->getProforma(), false);
            $filename = sprintf('proforma_%s.pdf', $share->getProforma()->getReference());
        } else {
            $pdfContent = $this->pdfGenerator->generateInvoicePdf($share->getInvoice(), false);
            $filename = sprintf('facture_%s.pdf', $share->getInvoice()->getReference());
        }

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Créer un partage pour une proforma
     */
    #[Route('/proformas/{id}/share', name: 'app_proforma_share', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function shareProforma(Request $request, Proforma $proforma): Response
    {
        if (!$this->isCsrfTokenValid('share' . $proforma->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
        }

        $shareType = $request->request->get('share_type', DocumentShare::TYPE_LINK);
        $recipientEmail = $request->request->get('recipient_email');
        $recipientPhone = $request->request->get('recipient_phone');
        $validityHours = (int) $request->request->get('validity_hours', 168);
        $message = $request->request->get('message');

        $share = $this->shareService->createProformaShare(
            $proforma,
            $this->getUser(),
            $shareType,
            $recipientEmail,
            $recipientPhone,
            $validityHours
        );

        // Actions selon le type de partage
        if ($shareType === DocumentShare::TYPE_EMAIL && $recipientEmail) {
            try {
                $this->mailerService->sendDocumentShare($share, $message);
                $this->addFlash('success', 'Proforma envoyée par email avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            }
        } elseif ($shareType === DocumentShare::TYPE_WHATSAPP) {
            // Rediriger vers WhatsApp
            return $this->redirect($this->shareService->getWhatsAppUrl($share, $message));
        } else {
            // Partage par lien - afficher l'URL
            $shareUrl = $this->shareService->getShareUrl($share);
            $this->addFlash('success', 'Lien de partage créé: ' . $shareUrl);
        }

        return $this->redirectToRoute('app_proforma_show', ['id' => $proforma->getId()]);
    }

    /**
     * Créer un partage pour une facture
     */
    #[Route('/invoices/{id}/share', name: 'app_invoice_share', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function shareInvoice(Request $request, Invoice $invoice): Response
    {
        if (!$this->isCsrfTokenValid('share' . $invoice->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $shareType = $request->request->get('share_type', DocumentShare::TYPE_LINK);
        $recipientEmail = $request->request->get('recipient_email');
        $recipientPhone = $request->request->get('recipient_phone');
        $validityHours = (int) $request->request->get('validity_hours', 168);
        $message = $request->request->get('message');

        $share = $this->shareService->createInvoiceShare(
            $invoice,
            $this->getUser(),
            $shareType,
            $recipientEmail,
            $recipientPhone,
            $validityHours
        );

        // Actions selon le type de partage
        if ($shareType === DocumentShare::TYPE_EMAIL && $recipientEmail) {
            try {
                $this->mailerService->sendDocumentShare($share, $message);
                $this->addFlash('success', 'Facture envoyée par email avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            }
        } elseif ($shareType === DocumentShare::TYPE_WHATSAPP) {
            // Rediriger vers WhatsApp
            return $this->redirect($this->shareService->getWhatsAppUrl($share, $message));
        } else {
            // Partage par lien - afficher l'URL
            $shareUrl = $this->shareService->getShareUrl($share);
            $this->addFlash('success', 'Lien de partage créé: ' . $shareUrl);
        }

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    /**
     * Liste des partages d'un document
     */
    #[Route('/proformas/{id}/shares', name: 'app_proforma_shares', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function proformaShares(Proforma $proforma): Response
    {
        $shares = $this->shareRepository->findByProforma($proforma->getId());

        return $this->render('share/list.html.twig', [
            'shares' => $shares,
            'document' => $proforma,
            'type' => 'proforma',
        ]);
    }

    /**
     * Liste des partages d'une facture
     */
    #[Route('/invoices/{id}/shares', name: 'app_invoice_shares', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function invoiceShares(Invoice $invoice): Response
    {
        $shares = $this->shareRepository->findByInvoice($invoice->getId());

        return $this->render('share/list.html.twig', [
            'shares' => $shares,
            'document' => $invoice,
            'type' => 'invoice',
        ]);
    }

    /**
     * Révoquer un partage
     */
    #[Route('/shares/{id}/revoke', name: 'app_share_revoke', methods: ['POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function revoke(Request $request, DocumentShare $share): Response
    {
        if (!$this->isCsrfTokenValid('revoke' . $share->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_dashboard');
        }

        $this->shareService->revokeShare($share);
        $this->addFlash('success', 'Partage révoqué.');

        // Rediriger vers le document approprié
        if ($share->getProforma()) {
            return $this->redirectToRoute('app_proforma_shares', ['id' => $share->getProforma()->getId()]);
        } else {
            return $this->redirectToRoute('app_invoice_shares', ['id' => $share->getInvoice()->getId()]);
        }
    }

    /**
     * Modal de partage (AJAX)
     */
    #[Route('/share/modal/{type}/{id}', name: 'app_share_modal', methods: ['GET'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function modal(string $type, int $id): Response
    {
        if ($type === 'proforma') {
            $document = $this->entityManager->getRepository(Proforma::class)->find($id);
        } else {
            $document = $this->entityManager->getRepository(Invoice::class)->find($id);
        }

        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        return $this->render('share/_modal.html.twig', [
            'document' => $document,
            'type' => $type,
            'shareTypes' => DocumentShare::SHARE_TYPES,
        ]);
    }
}
