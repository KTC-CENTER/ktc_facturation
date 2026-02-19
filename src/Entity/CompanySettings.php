<?php

namespace App\Entity;

use App\Repository\CompanySettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanySettingsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CompanySettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $rccm = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $taxId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = 'Cameroun';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $logoBase64 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faviconPath = null;

    #[ORM\Column(length: 10)]
    private string $currency = 'FCFA';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $defaultTaxRate = '19.25';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $defaultMargin = '30.00';

    #[ORM\Column(length: 50)]
    private string $proformaPrefix = 'PROV';

    #[ORM\Column(length: 50)]
    private string $invoicePrefix = 'FAC';

    #[ORM\Column]
    private int $proformaStartNumber = 1;

    #[ORM\Column]
    private int $invoiceStartNumber = 1;

    #[ORM\Column]
    private int $proformaCurrentNumber = 0;

    #[ORM\Column]
    private int $invoiceCurrentNumber = 0;

    #[ORM\Column]
    private int $defaultValidityDays = 30;

    #[ORM\Column]
    private int $defaultPaymentDays = 30;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultProformaConditions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultInvoiceConditions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defaultPaymentTerms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bankDetails = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brevoApiKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $senderEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $senderName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $replyToEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $whatsappNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $whatsappDefaultMessage = null;

    #[ORM\Column(length: 50)]
    private string $timezone = 'Africa/Douala';

    #[ORM\Column(length: 10)]
    private string $locale = 'fr';

    #[ORM\Column(length: 20)]
    private string $dateFormat = 'd/m/Y';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): static
    {
        $this->legalName = $legalName;
        return $this;
    }

    public function getRccm(): ?string
    {
        return $this->rccm;
    }

    public function setRccm(?string $rccm): static
    {
        $this->rccm = $rccm;
        return $this;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): static
    {
        $this->taxId = $taxId;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone2(): ?string
    {
        return $this->phone2;
    }

    public function setPhone2(?string $phone2): static
    {
        $this->phone2 = $phone2;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;
        return $this;
    }

    public function getLogoBase64(): ?string
    {
        return $this->logoBase64;
    }

    public function setLogoBase64(?string $logoBase64): static
    {
        $this->logoBase64 = $logoBase64;
        return $this;
    }

    public function getFaviconPath(): ?string
    {
        return $this->faviconPath;
    }

    public function setFaviconPath(?string $faviconPath): static
    {
        $this->faviconPath = $faviconPath;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getDefaultTaxRate(): float
    {
        return (float) $this->defaultTaxRate;
    }

    public function setDefaultTaxRate(string|float $defaultTaxRate): static
    {
        $this->defaultTaxRate = (string) $defaultTaxRate;
        return $this;
    }

    public function getDefaultMargin(): float
    {
        return (float) $this->defaultMargin;
    }

    public function setDefaultMargin(string|float $defaultMargin): static
    {
        $this->defaultMargin = (string) $defaultMargin;
        return $this;
    }

    public function getProformaPrefix(): string
    {
        return $this->proformaPrefix;
    }

    public function setProformaPrefix(string $proformaPrefix): static
    {
        $this->proformaPrefix = $proformaPrefix;
        return $this;
    }

    public function getInvoicePrefix(): string
    {
        return $this->invoicePrefix;
    }

    public function setInvoicePrefix(string $invoicePrefix): static
    {
        $this->invoicePrefix = $invoicePrefix;
        return $this;
    }

    public function getProformaStartNumber(): int
    {
        return $this->proformaStartNumber;
    }

    public function setProformaStartNumber(int $proformaStartNumber): static
    {
        $this->proformaStartNumber = $proformaStartNumber;
        return $this;
    }

    public function getInvoiceStartNumber(): int
    {
        return $this->invoiceStartNumber;
    }

    public function setInvoiceStartNumber(int $invoiceStartNumber): static
    {
        $this->invoiceStartNumber = $invoiceStartNumber;
        return $this;
    }

    public function getProformaCurrentNumber(): int
    {
        return $this->proformaCurrentNumber;
    }

    public function setProformaCurrentNumber(int $proformaCurrentNumber): static
    {
        $this->proformaCurrentNumber = $proformaCurrentNumber;
        return $this;
    }

    public function getInvoiceCurrentNumber(): int
    {
        return $this->invoiceCurrentNumber;
    }

    public function setInvoiceCurrentNumber(int $invoiceCurrentNumber): static
    {
        $this->invoiceCurrentNumber = $invoiceCurrentNumber;
        return $this;
    }

    public function getDefaultValidityDays(): int
    {
        return $this->defaultValidityDays;
    }

    public function setDefaultValidityDays(int $defaultValidityDays): static
    {
        $this->defaultValidityDays = $defaultValidityDays;
        return $this;
    }

    public function getDefaultPaymentDays(): int
    {
        return $this->defaultPaymentDays;
    }

    public function setDefaultPaymentDays(int $defaultPaymentDays): static
    {
        $this->defaultPaymentDays = $defaultPaymentDays;
        return $this;
    }

    public function getDefaultProformaConditions(): ?string
    {
        return $this->defaultProformaConditions;
    }

    public function setDefaultProformaConditions(?string $defaultProformaConditions): static
    {
        $this->defaultProformaConditions = $defaultProformaConditions;
        return $this;
    }

    public function getDefaultInvoiceConditions(): ?string
    {
        return $this->defaultInvoiceConditions;
    }

    public function setDefaultInvoiceConditions(?string $defaultInvoiceConditions): static
    {
        $this->defaultInvoiceConditions = $defaultInvoiceConditions;
        return $this;
    }

    public function getDefaultPaymentTerms(): ?string
    {
        return $this->defaultPaymentTerms;
    }

    public function setDefaultPaymentTerms(?string $defaultPaymentTerms): static
    {
        $this->defaultPaymentTerms = $defaultPaymentTerms;
        return $this;
    }

    public function getBankDetails(): ?string
    {
        return $this->bankDetails;
    }

    public function setBankDetails(?string $bankDetails): static
    {
        $this->bankDetails = $bankDetails;
        return $this;
    }

    public function getBrevoApiKey(): ?string
    {
        return $this->brevoApiKey;
    }

    public function setBrevoApiKey(?string $brevoApiKey): static
    {
        $this->brevoApiKey = $brevoApiKey;
        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): static
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): static
    {
        $this->senderName = $senderName;
        return $this;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function setReplyToEmail(?string $replyToEmail): static
    {
        $this->replyToEmail = $replyToEmail;
        return $this;
    }

    public function getWhatsappNumber(): ?string
    {
        return $this->whatsappNumber;
    }

    public function setWhatsappNumber(?string $whatsappNumber): static
    {
        $this->whatsappNumber = $whatsappNumber;
        return $this;
    }

    public function getWhatsappDefaultMessage(): ?string
    {
        return $this->whatsappDefaultMessage;
    }

    public function setWhatsappDefaultMessage(?string $whatsappDefaultMessage): static
    {
        $this->whatsappDefaultMessage = $whatsappDefaultMessage;
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $dateFormat): static
    {
        $this->dateFormat = $dateFormat;
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

    /**
     * Get full address formatted
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([$this->address, $this->city, $this->country]);
        return implode(', ', $parts);
    }
}
