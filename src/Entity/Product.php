<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    public const TYPE_LOGICIEL = 'LOGICIEL';
    public const TYPE_MATERIEL = 'MATERIEL';
    public const TYPE_SERVICE = 'SERVICE';

    public const TYPES = [
        self::TYPE_LOGICIEL => 'Logiciel',
        self::TYPE_MATERIEL => 'Matériel',
        self::TYPE_SERVICE => 'Service',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $code = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type de produit est obligatoire.')]
    #[Assert\Choice(choices: [self::TYPE_LOGICIEL, self::TYPE_MATERIEL, self::TYPE_SERVICE])]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $characteristics = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix unitaire est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul.')]
    private ?string $unitPrice = '0';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unit = null;

    // Champs spécifiques LOGICIEL
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $licenseType = null;

    #[ORM\Column(nullable: true)]
    private ?int $licenseDuration = null; // en mois

    #[ORM\Column(nullable: true)]
    private ?int $maxUsers = null;

    // Champs spécifiques MATERIEL
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(nullable: true)]
    private ?int $warrantyMonths = null;

    // Champs spécifiques SERVICE
    #[ORM\Column(nullable: true)]
    private ?int $durationHours = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: DocumentItem::class, mappedBy: 'product')]
    private Collection $documentItems;

    #[ORM\OneToMany(targetEntity: TemplateItem::class, mappedBy: 'product')]
    private Collection $templateItems;

    public function __construct()
    {
        $this->documentItems = new ArrayCollection();
        $this->templateItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
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

    public function getType(): ?string
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCharacteristics(): ?string
    {
        return $this->characteristics;
    }

    public function setCharacteristics(?string $characteristics): static
    {
        $this->characteristics = $characteristics;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function getUnitPriceFloat(): float
    {
        return (float) $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
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

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function setLicenseType(?string $licenseType): static
    {
        $this->licenseType = $licenseType;

        return $this;
    }

    public function getLicenseDuration(): ?int
    {
        return $this->licenseDuration;
    }

    public function setLicenseDuration(?int $licenseDuration): static
    {
        $this->licenseDuration = $licenseDuration;

        return $this;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(?int $maxUsers): static
    {
        $this->maxUsers = $maxUsers;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getWarrantyMonths(): ?int
    {
        return $this->warrantyMonths;
    }

    public function setWarrantyMonths(?int $warrantyMonths): static
    {
        $this->warrantyMonths = $warrantyMonths;

        return $this;
    }

    public function getDurationHours(): ?int
    {
        return $this->durationHours;
    }

    public function setDurationHours(?int $durationHours): static
    {
        $this->durationHours = $durationHours;

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
     * @return Collection<int, DocumentItem>
     */
    public function getDocumentItems(): Collection
    {
        return $this->documentItems;
    }

    public function addDocumentItem(DocumentItem $documentItem): static
    {
        if (!$this->documentItems->contains($documentItem)) {
            $this->documentItems->add($documentItem);
            $documentItem->setProduct($this);
        }

        return $this;
    }

    public function removeDocumentItem(DocumentItem $documentItem): static
    {
        if ($this->documentItems->removeElement($documentItem)) {
            if ($documentItem->getProduct() === $this) {
                $documentItem->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TemplateItem>
     */
    public function getTemplateItems(): Collection
    {
        return $this->templateItems;
    }

    public function addTemplateItem(TemplateItem $templateItem): static
    {
        if (!$this->templateItems->contains($templateItem)) {
            $this->templateItems->add($templateItem);
            $templateItem->setProduct($this);
        }

        return $this;
    }

    public function removeTemplateItem(TemplateItem $templateItem): static
    {
        if ($this->templateItems->removeElement($templateItem)) {
            if ($templateItem->getProduct() === $this) {
                $templateItem->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * Génère les caractéristiques formatées selon le type
     */
    public function getFormattedCharacteristics(): string
    {
        if ($this->characteristics) {
            return $this->characteristics;
        }

        $parts = [];

        switch ($this->type) {
            case self::TYPE_LOGICIEL:
                if ($this->version) {
                    $parts[] = "Version: {$this->version}";
                }
                if ($this->licenseType) {
                    $parts[] = "Licence: {$this->licenseType}";
                }
                if ($this->maxUsers) {
                    $parts[] = "{$this->maxUsers} utilisateur(s)";
                }
                if ($this->licenseDuration) {
                    $parts[] = "Durée: {$this->licenseDuration} mois";
                }
                break;

            case self::TYPE_MATERIEL:
                if ($this->brand) {
                    $parts[] = $this->brand;
                }
                if ($this->model) {
                    $parts[] = $this->model;
                }
                if ($this->warrantyMonths) {
                    $parts[] = "Garantie: {$this->warrantyMonths} mois";
                }
                break;

            case self::TYPE_SERVICE:
                if ($this->durationHours) {
                    $parts[] = "Durée: {$this->durationHours}h";
                }
                break;
        }

        return implode(' / ', $parts);
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
