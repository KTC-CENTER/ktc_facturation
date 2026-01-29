<?php

namespace App\Entity;

use App\Repository\ProformaTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ProformaTemplateRepository::class)]
#[ORM\Table(name: 'proforma_templates')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'Ce nom de modèle existe déjà')]
class ProformaTemplate
{
    public const CATEGORY_SOFTWARE = 'software';
    public const CATEGORY_HARDWARE = 'hardware';
    public const CATEGORY_SERVICE = 'service';
    public const CATEGORY_MIXED = 'mixed';

    public const CATEGORIES = [
        self::CATEGORY_SOFTWARE => 'Logiciel',
        self::CATEGORY_HARDWARE => 'Matériel',
        self::CATEGORY_SERVICE => 'Service',
        self::CATEGORY_MIXED => 'Mixte',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private string $category = self::CATEGORY_MIXED;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $basePrice = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultConditions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultObject = null;

    #[ORM\Column]
    private int $validityDays = 30;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $usageCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Proforma::class)]
    private Collection $proformas;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->proformas = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getBasePrice(): string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;
        return $this;
    }

    public function getBasePriceFloat(): float
    {
        return (float) $this->basePrice;
    }

    public function getDefaultNotes(): ?string
    {
        return $this->defaultNotes;
    }

    public function setDefaultNotes(?string $defaultNotes): static
    {
        $this->defaultNotes = $defaultNotes;
        return $this;
    }

    public function getDefaultConditions(): ?string
    {
        return $this->defaultConditions;
    }

    public function setDefaultConditions(?string $defaultConditions): static
    {
        $this->defaultConditions = $defaultConditions;
        return $this;
    }

    public function getDefaultObject(): ?string
    {
        return $this->defaultObject;
    }

    public function setDefaultObject(?string $defaultObject): static
    {
        $this->defaultObject = $defaultObject;
        return $this;
    }

    public function getValidityDays(): int
    {
        return $this->validityDays;
    }

    public function setValidityDays(int $validityDays): static
    {
        $this->validityDays = $validityDays;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): static
    {
        $this->usageCount++;
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, TemplateItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(TemplateItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setTemplate($this);
        }
        return $this;
    }

    public function removeItem(TemplateItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getTemplate() === $this) {
                $item->setTemplate(null);
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
     * @return Collection<int, Proforma>
     */
    public function getProformas(): Collection
    {
        return $this->proformas;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Calcule le total HT du modèle
     */
    public function calculateTotalHT(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getTotalFloat();
        }
        return $total;
    }

    /**
     * Retourne le nombre d'items dans le modèle
     */
    public function getItemCount(): int
    {
        return $this->items->count();
    }

    /**
     * Vérifie si le modèle a des items
     */
    public function hasItems(): bool
    {
        return !$this->items->isEmpty();
    }

    /**
     * Formate le prix de base pour l'affichage
     */
    public function getFormattedBasePrice(string $currency = 'FCFA'): string
    {
        return number_format($this->getBasePriceFloat(), 0, ',', ' ') . ' ' . $currency;
    }

    /**
     * Crée des DocumentItems à partir des items du modèle
     * @return DocumentItem[]
     */
    public function createDocumentItems(): array
    {
        $documentItems = [];
        foreach ($this->items as $item) {
            $documentItems[] = $item->toDocumentItem();
        }
        return $documentItems;
    }
}
