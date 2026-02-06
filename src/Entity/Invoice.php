<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['reference'], message: 'Cette référence de facture existe déjà')]
class Invoice
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_SENT => 'Envoyée',
        self::STATUS_PAID => 'Payée',
        self::STATUS_PARTIAL => 'Partiellement payée',
        self::STATUS_OVERDUE => 'En retard',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_SENT => 'blue',
        self::STATUS_PAID => 'green',
        self::STATUS_PARTIAL => 'yellow',
        self::STATUS_OVERDUE => 'red',
        self::STATUS_CANCELLED => 'gray',
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
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $totalHT = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $totalTVA = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $totalTTC = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $amountPaid = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $taxRate = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $object = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToOne(targetEntity: Proforma::class, inversedBy: 'invoice')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Proforma $proforma = null;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: DocumentItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: DocumentShare::class, cascade: ['remove'])]
    private Collection $shares;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->shares = new ArrayCollection();
        $this->issueDate = new \DateTime();
        $this->dueDate = new \DateTime('+30 days');
        $this->createdAt = new \DateTime();
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

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getTotalHT(): string
    {
        return $this->totalHT;
    }

    public function setTotalHT(string $totalHT): static
    {
        $this->totalHT = $totalHT;
        return $this;
    }

    public function getTotalHTFloat(): float
    {
        return (float) $this->totalHT;
    }

    public function getTotalTVA(): string
    {
        return $this->totalTVA;
    }

    public function setTotalTVA(string $totalTVA): static
    {
        $this->totalTVA = $totalTVA;
        return $this;
    }

    public function getTotalTVAFloat(): float
    {
        return (float) $this->totalTVA;
    }

    public function getTotalTTC(): string
    {
        return $this->totalTTC;
    }

    public function setTotalTTC(string $totalTTC): static
    {
        $this->totalTTC = $totalTTC;
        return $this;
    }

    public function getTotalTTCFloat(): float
    {
        return (float) $this->totalTTC;
    }

    public function getAmountPaid(): string
    {
        return $this->amountPaid;
    }

    public function setAmountPaid(string $amountPaid): static
    {
        $this->amountPaid = $amountPaid;
        return $this;
    }

    public function getAmountPaidFloat(): float
    {
        return (float) $this->amountPaid;
    }

    public function getAmountDue(): float
    {
        return $this->getTotalTTCFloat() - $this->getAmountPaidFloat();
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $taxRate): static
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    public function getTaxRateFloat(): float
    {
        return (float) $this->taxRate;
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

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): static
    {
        $this->paymentTerms = $paymentTerms;
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

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;
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

    public function getProforma(): ?Proforma
    {
        return $this->proforma;
    }

    public function setProforma(?Proforma $proforma): static
    {
        $this->proforma = $proforma;
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
            $item->setInvoice($this);
        }
        return $this;
    }

    public function removeItem(DocumentItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
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

    /**
     * @return Collection<int, DocumentShare>
     */
    public function getShares(): Collection
    {
        return $this->shares;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return false;
        }
        return $this->dueDate < new \DateTime('today');
    }

    public function getDaysUntilDue(): int
    {
        $now = new \DateTime('today');
        return (int) $now->diff($this->dueDate)->format('%r%a');
    }

    public function calculateTotals(): void
    {
        $totalHT = 0;
        foreach ($this->items as $item) {
            $totalHT += $item->getTotalFloat();
        }
        
        $this->totalHT = number_format($totalHT, 2, '.', '');
        $this->totalTVA = number_format($totalHT * ($this->getTaxRateFloat() / 100), 2, '.', '');
        $this->totalTTC = number_format($totalHT + (float)$this->totalTVA, 2, '.', '');
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT]);
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_PARTIAL, self::STATUS_OVERDUE]);
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_PARTIAL, self::STATUS_OVERDUE]);
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    public function markAsPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->amountPaid = $this->totalTTC;
        $this->paidAt = new \DateTime();
    }

    public function addPayment(float $amount): void
    {
        $newAmount = $this->getAmountPaidFloat() + $amount;
        $this->amountPaid = number_format($newAmount, 2, '.', '');
        
        if ($newAmount >= $this->getTotalTTCFloat()) {
            $this->markAsPaid();
        } else {
            $this->status = self::STATUS_PARTIAL;
        }
    }

    public function hasProforma(): bool
    {
        return $this->proforma !== null;
    }

    public function getPaymentPercentage(): float
    {
        if ($this->getTotalTTCFloat() == 0) {
            return 0;
        }
        return ($this->getAmountPaidFloat() / $this->getTotalTTCFloat()) * 100;
    }
}
