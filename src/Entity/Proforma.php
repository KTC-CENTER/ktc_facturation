<?php

namespace App\Entity;

use App\Repository\ProformaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProformaRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Proforma
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SENT = 'SENT';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REFUSED = 'REFUSED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_INVOICED = 'INVOICED';

    // CHANGED: "Brouillon" -> "Initiée"
    public const STATUSES = [
        self::STATUS_DRAFT => 'Initiée',
        self::STATUS_SENT => 'Envoyée',
        self::STATUS_ACCEPTED => 'Acceptée',
        self::STATUS_REFUSED => 'Refusée',
        self::STATUS_EXPIRED => 'Expirée',
        self::STATUS_INVOICED => 'Facturée',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_SENT => 'blue',
        self::STATUS_ACCEPTED => 'success',
        self::STATUS_REFUSED => 'danger',
        self::STATUS_EXPIRED => 'warning',
        self::STATUS_INVOICED => 'primary',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $issueDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $totalHT = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $totalTVA = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $totalTTC = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $taxRate = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $object = null;

    #[ORM\ManyToOne(inversedBy: 'proformas')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le client est obligatoire.')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'proformas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'proformas')]
    private ?ProformaTemplate $template = null;

    #[ORM\OneToMany(targetEntity: DocumentItem::class, mappedBy: 'proforma', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $items;

    #[ORM\OneToOne(mappedBy: 'proforma', targetEntity: Invoice::class)]
    private ?Invoice $invoice = null;

    #[ORM\OneToMany(targetEntity: DocumentShare::class, mappedBy: 'proforma', cascade: ['remove'])]
    private Collection $shares;

    #[ORM\OneToMany(targetEntity: ProformaStatusHistory::class, mappedBy: 'proforma', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['changedAt' => 'ASC'])]
    private Collection $statusHistory;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // Dates spécifiques pour chaque statut
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $acceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $refusedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiredAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $invoicedAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->shares = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->issueDate = new \DateTime();
        $this->validUntil = (new \DateTime())->modify('+30 days');
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $oldStatus = $this->status;
        $this->status = $status;
        
        // Mettre à jour la date correspondante au statut
        $now = new \DateTime();
        switch ($status) {
            case self::STATUS_SENT:
                $this->sentAt = $now;
                break;
            case self::STATUS_ACCEPTED:
                $this->acceptedAt = $now;
                break;
            case self::STATUS_REFUSED:
                $this->refusedAt = $now;
                break;
            case self::STATUS_EXPIRED:
                $this->expiredAt = $now;
                break;
            case self::STATUS_INVOICED:
                $this->invoicedAt = $now;
                break;
        }

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getIssueDate(): ?\DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeInterface $issueDate): static
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeInterface $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->validUntil < new \DateTime();
    }

    public function getDaysUntilExpiry(): int
    {
        $now = new \DateTime();
        $diff = $now->diff($this->validUntil);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function getTotalHT(): string
    {
        return $this->totalHT;
    }

    public function getTotalHTFloat(): float
    {
        return (float) $this->totalHT;
    }

    public function setTotalHT(string $totalHT): static
    {
        $this->totalHT = $totalHT;
        return $this;
    }

    public function getTotalTVA(): string
    {
        return $this->totalTVA;
    }

    public function getTotalTVAFloat(): float
    {
        return (float) $this->totalTVA;
    }

    // Alias pour compatibilité
    public function getTotalTaxFloat(): float
    {
        return $this->getTotalTVAFloat();
    }

    public function setTotalTVA(string $totalTVA): static
    {
        $this->totalTVA = $totalTVA;
        return $this;
    }

    public function getTotalTTC(): string
    {
        return $this->totalTTC;
    }

    public function getTotalTTCFloat(): float
    {
        return (float) $this->totalTTC;
    }

    public function setTotalTTC(string $totalTTC): static
    {
        $this->totalTTC = $totalTTC;
        return $this;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function getTaxRateFloat(): float
    {
        return (float) $this->taxRate;
    }

    public function setTaxRate(?string $taxRate): static
    {
        $this->taxRate = $taxRate ?? '0.00';
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(?string $conditions): static
    {
        $this->conditions = $conditions;
        return $this;
    }

    public function getObject(): ?string
    {
        return $this->object;
    }

    public function setObject(?string $object): static
    {
        $this->object = $object;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
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

    public function getTemplate(): ?ProformaTemplate
    {
        return $this->template;
    }

    public function setTemplate(?ProformaTemplate $template): static
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return Collection<int, DocumentItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(DocumentItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setProforma($this);
        }
        return $this;
    }

    public function removeItem(DocumentItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getProforma() === $this) {
                $item->setProforma(null);
            }
        }
        return $this;
    }

    public function clearItems(): static
    {
        foreach ($this->items as $item) {
            $this->removeItem($item);
        }
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        if ($invoice === null && $this->invoice !== null) {
            $this->invoice->setProforma(null);
        }
        if ($invoice !== null && $invoice->getProforma() !== $this) {
            $invoice->setProforma($this);
        }
        $this->invoice = $invoice;
        return $this;
    }

    public function hasInvoice(): bool
    {
        return $this->invoice !== null;
    }

    /**
     * @return Collection<int, DocumentShare>
     */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    public function addShare(DocumentShare $share): static
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setProforma($this);
        }
        return $this;
    }

    public function removeShare(DocumentShare $share): static
    {
        if ($this->shares->removeElement($share)) {
            if ($share->getProforma() === $this) {
                $share->setProforma(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ProformaStatusHistory>
     */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function addStatusHistory(ProformaStatusHistory $history): static
    {
        if (!$this->statusHistory->contains($history)) {
            $this->statusHistory->add($history);
            $history->setProforma($this);
        }
        return $this;
    }

    public function removeStatusHistory(ProformaStatusHistory $history): static
    {
        if ($this->statusHistory->removeElement($history)) {
            if ($history->getProforma() === $this) {
                $history->setProforma(null);
            }
        }
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Getters pour les dates de changement de statut
    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeInterface
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeInterface $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }

    public function getRefusedAt(): ?\DateTimeInterface
    {
        return $this->refusedAt;
    }

    public function setRefusedAt(?\DateTimeInterface $refusedAt): static
    {
        $this->refusedAt = $refusedAt;
        return $this;
    }

    public function getExpiredAt(): ?\DateTimeInterface
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeInterface $expiredAt): static
    {
        $this->expiredAt = $expiredAt;
        return $this;
    }

    public function getInvoicedAt(): ?\DateTimeInterface
    {
        return $this->invoicedAt;
    }

    public function setInvoicedAt(?\DateTimeInterface $invoicedAt): static
    {
        $this->invoicedAt = $invoicedAt;
        return $this;
    }

    /**
     * Get timeline of status changes for display
     */
    public function getStatusTimeline(): array
    {
        $timeline = [];

        // Création / Initié
        $timeline[] = [
            'status' => self::STATUS_DRAFT,
            'label' => 'Initié',
            'date' => $this->createdAt,
            'icon' => 'document-text',
            'color' => 'gray',
            'active' => true,
        ];

        // Envoyée
        $timeline[] = [
            'status' => self::STATUS_SENT,
            'label' => 'Envoyée',
            'date' => $this->sentAt,
            'icon' => 'paper-airplane',
            'color' => 'blue',
            'active' => $this->sentAt !== null,
        ];

        // Acceptée OU Refusée
        if ($this->refusedAt !== null) {
            $timeline[] = [
                'status' => self::STATUS_REFUSED,
                'label' => 'Refusée',
                'date' => $this->refusedAt,
                'icon' => 'x-circle',
                'color' => 'danger',
                'active' => true,
            ];
        } else {
            $timeline[] = [
                'status' => self::STATUS_ACCEPTED,
                'label' => 'Acceptée',
                'date' => $this->acceptedAt,
                'icon' => 'check-circle',
                'color' => 'success',
                'active' => $this->acceptedAt !== null,
            ];
        }

        // Facturée (si acceptée ou pas refusée)
        if ($this->refusedAt === null) {
            $timeline[] = [
                'status' => self::STATUS_INVOICED,
                'label' => 'Facturée',
                'date' => $this->invoicedAt,
                'icon' => 'document-duplicate',
                'color' => 'primary',
                'active' => $this->invoicedAt !== null,
            ];
        }

        return $timeline;
    }

    /**
     * Recalcule les totaux à partir des lignes
     */
    public function calculateTotals(): static
    {
        $totalHT = 0;

        foreach ($this->items as $item) {
            $totalHT += $item->getTotalFloat();
        }

        $totalTVA = $totalHT * ($this->getTaxRateFloat() / 100);
        $totalTTC = $totalHT + $totalTVA;

        $this->totalHT = number_format($totalHT, 2, '.', '');
        $this->totalTVA = number_format($totalTVA, 2, '.', '');
        $this->totalTTC = number_format($totalTTC, 2, '.', '');

        return $this;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    public function canBeConverted(): bool
    {
        $allowedStatuses = [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_ACCEPTED];
        return in_array($this->status, $allowedStatuses) && !$this->hasInvoice();
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_ACCEPTED]);
    }

    public function __toString(): string
    {
        return $this->reference ?? 'Nouvelle proforma';
    }
}
