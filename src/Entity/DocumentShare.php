<?php

namespace App\Entity;

use App\Repository\DocumentShareRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentShareRepository::class)]
#[ORM\Table(name: 'document_shares')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_token', columns: ['token'])]
#[ORM\Index(name: 'idx_expires_at', columns: ['expires_at'])]
class DocumentShare
{
    public const TYPE_EMAIL = 'email';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_LINK = 'link';

    public const TYPES = [
        self::TYPE_EMAIL => 'Email',
        self::TYPE_WHATSAPP => 'WhatsApp',
        self::TYPE_LINK => 'Lien',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_OPENED = 'opened';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVOKED = 'revoked';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_SENT => 'Envoyé',
        self::STATUS_DELIVERED => 'Délivré',
        self::STATUS_OPENED => 'Ouvert',
        self::STATUS_FAILED => 'Échoué',
        self::STATUS_REVOKED => 'Révoqué',
    ];

    public const EXPIRY_24H = 24;
    public const EXPIRY_48H = 48;
    public const EXPIRY_7D = 168;
    public const EXPIRY_30D = 720;
    public const EXPIRY_NEVER = 0;

    public const EXPIRY_OPTIONS = [
        self::EXPIRY_24H => '24 heures',
        self::EXPIRY_48H => '48 heures',
        self::EXPIRY_7D => '7 jours',
        self::EXPIRY_30D => '30 jours',
        self::EXPIRY_NEVER => 'Permanent',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_LINK;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipientEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $recipientPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipientName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column]
    private int $viewCount = 0;

    #[ORM\Column]
    private int $downloadCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $openedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastViewedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne(targetEntity: Proforma::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Proforma $proforma = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'documentShares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->token = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function regenerateToken(): static
    {
        $this->token = bin2hex(random_bytes(32));
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(?string $recipientEmail): static
    {
        $this->recipientEmail = $recipientEmail;
        return $this;
    }

    public function getRecipientPhone(): ?string
    {
        return $this->recipientPhone;
    }

    public function setRecipientPhone(?string $recipientPhone): static
    {
        $this->recipientPhone = $recipientPhone;
        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(?string $recipientName): static
    {
        $this->recipientName = $recipientName;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount++;
        $this->lastViewedAt = new \DateTime();
        return $this;
    }

    public function getDownloadCount(): int
    {
        return $this->downloadCount;
    }

    public function setDownloadCount(int $downloadCount): static
    {
        $this->downloadCount = $downloadCount;
        return $this;
    }

    public function incrementDownloadCount(): static
    {
        $this->downloadCount++;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function setExpiryHours(int $hours): static
    {
        if ($hours === 0) {
            $this->expiresAt = null;
        } else {
            $this->expiresAt = new \DateTime("+{$hours} hours");
        }
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getOpenedAt(): ?\DateTimeInterface
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?\DateTimeInterface $openedAt): static
    {
        $this->openedAt = $openedAt;
        return $this;
    }

    public function getLastViewedAt(): ?\DateTimeInterface
    {
        return $this->lastViewedAt;
    }

    public function setLastViewedAt(?\DateTimeInterface $lastViewedAt): static
    {
        $this->lastViewedAt = $lastViewedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): static
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
        return $this;
    }

    public function getProforma(): ?Proforma
    {
        return $this->proforma;
    }

    public function setProforma(?Proforma $proforma): static
    {
        $this->proforma = $proforma;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Vérifie si le partage est expiré
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTime();
    }

    /**
     * Vérifie si le partage est actif
     */
    public function isActive(): bool
    {
        return !$this->isExpired() && $this->status !== self::STATUS_REVOKED;
    }

    /**
     * Révoque le partage
     */
    public function revoke(): static
    {
        $this->status = self::STATUS_REVOKED;
        return $this;
    }

    /**
     * Marque comme envoyé
     */
    public function markAsSent(): static
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = new \DateTime();
        return $this;
    }

    /**
     * Marque comme ouvert
     */
    public function markAsOpened(): static
    {
        if ($this->openedAt === null) {
            $this->openedAt = new \DateTime();
        }
        $this->status = self::STATUS_OPENED;
        $this->incrementViewCount();
        return $this;
    }

    /**
     * Retourne le document associé (Proforma ou Invoice)
     */
    public function getDocument(): Proforma|Invoice|null
    {
        return $this->proforma ?? $this->invoice;
    }

    /**
     * Retourne le type de document
     */
    public function getDocumentType(): string
    {
        if ($this->proforma !== null) {
            return 'proforma';
        }
        if ($this->invoice !== null) {
            return 'invoice';
        }
        return 'unknown';
    }

    /**
     * Retourne la référence du document
     */
    public function getDocumentReference(): ?string
    {
        $document = $this->getDocument();
        return $document?->getReference();
    }

    /**
     * Génère l'URL WhatsApp Click-to-Chat
     */
    public function getWhatsAppUrl(string $baseUrl): ?string
    {
        if ($this->type !== self::TYPE_WHATSAPP || empty($this->recipientPhone)) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->recipientPhone);
        $shareUrl = $baseUrl . '/share/' . $this->token;
        $text = $this->message ?? "Voici votre document : {$shareUrl}";
        
        return 'https://wa.me/' . $phone . '?text=' . urlencode($text);
    }

    /**
     * Temps restant avant expiration
     */
    public function getTimeUntilExpiry(): ?string
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now = new \DateTime();
        if ($this->expiresAt < $now) {
            return 'Expiré';
        }

        $diff = $now->diff($this->expiresAt);
        
        if ($diff->days > 0) {
            return $diff->days . ' jour(s)';
        }
        if ($diff->h > 0) {
            return $diff->h . ' heure(s)';
        }
        return $diff->i . ' minute(s)';
    }

    /**
     * Retourne l'URL de partage publique
     */
    public function getShareUrl(): string
    {
        // URL sera générée dynamiquement avec le router
        return '/share/' . $this->token;
    }

    /**
     * Alias pour getType (compatibilité service)
     */
    public function getShareType(): string
    {
        return $this->type;
    }

    /**
     * Alias pour setType (compatibilité service)
     */
    public function setShareType(string $type): static
    {
        return $this->setType($type);
    }

    /**
     * Alias pour setCreatedBy (compatibilité service)
     */
    public function setSharedBy(?User $user): static
    {
        return $this->setCreatedBy($user);
    }

    /**
     * Alias pour getCreatedBy (compatibilité service)
     */
    public function getSharedBy(): ?User
    {
        return $this->getCreatedBy();
    }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodePath = null;

    public function getQrCodePath(): ?string
    {
        return $this->qrCodePath;
    }

    public function setQrCodePath(?string $qrCodePath): static
    {
        $this->qrCodePath = $qrCodePath;
        return $this;
    }
}
