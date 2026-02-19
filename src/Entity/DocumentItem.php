<?php

namespace App\Entity;

use App\Repository\DocumentItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentItemRepository::class)]
#[ORM\Table(name: 'document_items')]
#[ORM\HasLifecycleCallbacks]
class DocumentItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $quantity = '1.00';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $discount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $total = '0.00';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Proforma::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Proforma $proforma = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Invoice $invoice = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'documentItems')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;
        $this->calculateTotal();
        return $this;
    }

    public function getQuantityFloat(): float
    {
        return (float) $this->quantity;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->calculateTotal();
        return $this;
    }

    public function getUnitPriceFloat(): float
    {
        return (float) $this->unitPrice;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function setDiscount(?string $discount): static
    {
        $this->discount = $discount ?? '0.00';
        $this->calculateTotal();
        return $this;
    }

    public function getDiscountFloat(): float
    {
        return (float) $this->discount;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getTotalFloat(): float
    {
        return (float) $this->total;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): static
    {
        $this->sortOrder = $sortOrder ?? 0;
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        
        // Auto-remplir depuis le produit
        if ($product !== null) {
            $this->designation = $product->getName();
            $this->description = $product->getDescription();
            $this->unitPrice = $product->getUnitPrice();
            $this->unit = $product->getUnit();
            $this->calculateTotal();
        }
        
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotal(): void
    {
        $subtotal = $this->getQuantityFloat() * $this->getUnitPriceFloat();
        $discountAmount = $subtotal * ($this->getDiscountFloat() / 100);
        $this->total = number_format($subtotal - $discountAmount, 2, '.', '');
    }

    /**
     * Crée une copie de cet item pour une facture
     */
    public function cloneForInvoice(): self
    {
        $clone = new self();
        $clone->designation = $this->designation;
        $clone->description = $this->description;
        $clone->quantity = $this->quantity;
        $clone->unit = $this->unit;
        $clone->unitPrice = $this->unitPrice;
        $clone->discount = $this->discount;
        $clone->total = $this->total;
        $clone->sortOrder = $this->sortOrder;
        $clone->product = $this->product;
        
        return $clone;
    }

    /**
     * Formate le prix unitaire pour l'affichage
     */
    public function getFormattedUnitPrice(string $currency = 'FCFA'): string
    {
        return number_format($this->getUnitPriceFloat(), 0, ',', ' ') . ' ' . $currency;
    }

    /**
     * Formate le total pour l'affichage
     */
    public function getFormattedTotal(string $currency = 'FCFA'): string
    {
        return number_format($this->getTotalFloat(), 0, ',', ' ') . ' ' . $currency;
    }

    /**
     * Vérifie si l'item a une remise
     */
    public function hasDiscount(): bool
    {
        return $this->getDiscountFloat() > 0;
    }

    /**
     * Retourne le montant de la remise
     */
    public function getDiscountAmount(): float
    {
        $subtotal = $this->getQuantityFloat() * $this->getUnitPriceFloat();
        return $subtotal * ($this->getDiscountFloat() / 100);
    }
}
