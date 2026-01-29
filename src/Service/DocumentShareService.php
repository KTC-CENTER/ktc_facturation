<?php

namespace App\Service;

use App\Entity\Proforma;
use App\Entity\Invoice;
use App\Entity\DocumentShare;
use App\Entity\User;
use App\Repository\DocumentShareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DocumentShareService
{
    private DocumentShareRepository $shareRepository;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private BrevoMailerService $mailer;
    private string $uploadsDirectory;

    public function __construct(
        DocumentShareRepository $shareRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        BrevoMailerService $mailer,
        string $uploadsDirectory
    ) {
        $this->shareRepository = $shareRepository;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->mailer = $mailer;
        $this->uploadsDirectory = $uploadsDirectory;
    }

    /**
     * Crée un lien de partage pour une proforma
     */
    public function createProformaShare(
        Proforma $proforma,
        User $sharedBy,
        string $shareType,
        ?string $recipientEmail = null,
        ?string $recipientPhone = null,
        int $validityHours = 168
    ): DocumentShare {
        $share = new DocumentShare();
        $share->setProforma($proforma);
        $share->setSharedBy($sharedBy);
        $share->setShareType($shareType);
        $share->setRecipientEmail($recipientEmail);
        $share->setRecipientPhone($recipientPhone);
        $share->setToken($this->generateToken());
        $share->setExpiresAt(new \DateTime("+{$validityHours} hours"));

        $this->entityManager->persist($share);
        $this->entityManager->flush();

        // Générer le QR Code
        $this->generateQrCode($share);

        return $share;
    }

    /**
     * Crée un lien de partage pour une facture
     */
    public function createInvoiceShare(
        Invoice $invoice,
        User $sharedBy,
        string $shareType,
        ?string $recipientEmail = null,
        ?string $recipientPhone = null,
        int $validityHours = 168
    ): DocumentShare {
        $share = new DocumentShare();
        $share->setInvoice($invoice);
        $share->setSharedBy($sharedBy);
        $share->setShareType($shareType);
        $share->setRecipientEmail($recipientEmail);
        $share->setRecipientPhone($recipientPhone);
        $share->setToken($this->generateToken());
        $share->setExpiresAt(new \DateTime("+{$validityHours} hours"));

        $this->entityManager->persist($share);
        $this->entityManager->flush();

        // Générer le QR Code
        $this->generateQrCode($share);

        return $share;
    }

    /**
     * Partage par email
     */
    public function shareByEmail(DocumentShare $share): bool
    {
        if (!$share->getRecipientEmail()) {
            return false;
        }

        $result = $this->mailer->sendShareLink($share);

        if ($result) {
            $share->incrementViewCount(); // Compte l'envoi comme une "vue"
            $this->entityManager->flush();
        }

        return $result;
    }

    /**
     * Génère le lien WhatsApp
     */
    public function getWhatsAppLink(DocumentShare $share): string
    {
        $document = $share->getProforma() ?? $share->getInvoice();
        $docType = $share->getProforma() ? 'proforma' : 'facture';
        
        $message = sprintf(
            "Bonjour,\n\nVoici le lien pour consulter votre %s n° %s :\n%s\n\nCe lien expire le %s.",
            $docType,
            $document->getReference(),
            $share->getShareUrl(),
            $share->getExpiresAt()->format('d/m/Y à H:i')
        );

        $phone = $share->getRecipientPhone();
        if ($phone) {
            // Nettoyer le numéro de téléphone
            $phone = preg_replace('/[^0-9]/', '', $phone);
            // Ajouter l'indicatif Cameroun si nécessaire
            if (strlen($phone) === 9) {
                $phone = '237' . $phone;
            }
            return "https://wa.me/{$phone}?text=" . urlencode($message);
        }

        return "https://wa.me/?text=" . urlencode($message);
    }

    /**
     * Valide un token de partage et incrémente le compteur
     */
    public function validateAndTrack(string $token): ?DocumentShare
    {
        $share = $this->shareRepository->findValidByToken($token);

        if ($share) {
            $share->incrementViewCount();
            $share->setLastViewedAt(new \DateTime());
            $this->entityManager->flush();
        }

        return $share;
    }

    /**
     * Révoque un partage
     */
    public function revokeShare(DocumentShare $share): void
    {
        $share->setExpiresAt(new \DateTime('-1 hour'));
        $this->entityManager->flush();
    }

    /**
     * Génère un token unique
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Génère le QR Code pour un partage
     */
    private function generateQrCode(DocumentShare $share): void
    {
        $qrDir = $this->uploadsDirectory . '/qrcodes';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($share->getShareUrl())
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();

        $qrPath = $qrDir . '/' . $share->getToken() . '.png';
        $result->saveToFile($qrPath);

        $share->setQrCodePath($qrPath);
        $this->entityManager->flush();
    }

    /**
     * Nettoie les partages expirés
     */
    public function cleanupExpiredShares(): int
    {
        return $this->shareRepository->deleteExpired();
    }

    /**
     * Statistiques de partage pour un document
     */
    public function getShareStats(Proforma|Invoice $document): array
    {
        if ($document instanceof Proforma) {
            $shares = $this->shareRepository->findByProforma($document);
        } else {
            $shares = $this->shareRepository->findByInvoice($document);
        }

        $stats = [
            'total_shares' => count($shares),
            'total_views' => 0,
            'by_type' => [
                'email' => 0,
                'whatsapp' => 0,
                'link' => 0,
            ],
        ];

        foreach ($shares as $share) {
            $stats['total_views'] += $share->getViewCount();
            $type = $share->getShareType();
            if (isset($stats['by_type'][$type])) {
                $stats['by_type'][$type]++;
            }
        }

        return $stats;
    }
}
