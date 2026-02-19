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

    public const STATUSES = [
        self::STATUS_DRAFT => 'Brouillon',
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->shares = new ArrayCollection();
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
        $this->status = $status;

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
        // unset the owning side of the relation if necessary
        if ($invoice === null && $this->invoice !== null) {
            $this->invoice->setProforma(null);
        }

        // set the owning side of the relation if necessary
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
        // Permet la conversion depuis DRAFT, SENT ou ACCEPTED (tant qu'il n'y a pas déjà de facture)
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
