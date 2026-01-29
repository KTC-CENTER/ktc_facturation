<?php

namespace App\Entity;

use App\Repository\EmailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'email_templates')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['code'], message: 'Ce code de template existe déjà')]
class EmailTemplate
{
    public const TYPE_PROFORMA = 'proforma';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_WELCOME = 'welcome';

    public const TYPES = [
        self::TYPE_PROFORMA => 'Envoi Proforma',
        self::TYPE_INVOICE => 'Envoi Facture',
        self::TYPE_REMINDER => 'Rappel de paiement',
        self::TYPE_PAYMENT => 'Confirmation de paiement',
        self::TYPE_WELCOME => 'Bienvenue client',
    ];

    /**
     * Variables disponibles pour les templates
     */
    public const AVAILABLE_VARIABLES = [
        '{{client_name}}' => 'Nom du client',
        '{{client_email}}' => 'Email du client',
        '{{client_company}}' => 'Entreprise du client',
        '{{document_reference}}' => 'Référence du document',
        '{{document_date}}' => 'Date du document',
        '{{document_amount}}' => 'Montant TTC',
        '{{document_amount_ht}}' => 'Montant HT',
        '{{document_due_date}}' => 'Date d\'échéance',
        '{{document_link}}' => 'Lien vers le document',
        '{{company_name}}' => 'Nom de l\'entreprise',
        '{{company_email}}' => 'Email de l\'entreprise',
        '{{company_phone}}' => 'Téléphone de l\'entreprise',
        '{{current_date}}' => 'Date actuelle',
        '{{sender_name}}' => 'Nom de l\'expéditeur',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_PROFORMA;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $bodyHtml = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bodyText = null;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    public function __construct()
    {
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

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBodyHtml(): ?string
    {
        return $this->bodyHtml;
    }

    public function setBodyHtml(string $bodyHtml): static
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }

    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }

    public function setBodyText(?string $bodyText): static
    {
        $this->bodyText = $bodyText;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
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

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Remplace les variables dans le sujet
     */
    public function renderSubject(array $variables): string
    {
        return $this->replaceVariables($this->subject ?? '', $variables);
    }

    /**
     * Remplace les variables dans le corps HTML
     */
    public function renderBodyHtml(array $variables): string
    {
        return $this->replaceVariables($this->bodyHtml ?? '', $variables);
    }

    /**
     * Remplace les variables dans le corps texte
     */
    public function renderBodyText(array $variables): string
    {
        if ($this->bodyText === null) {
            return strip_tags($this->renderBodyHtml($variables));
        }
        return $this->replaceVariables($this->bodyText, $variables);
    }

    /**
     * Remplace les variables dans un texte
     */
    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace($key, (string) $value, $text);
        }
        return $text;
    }

    /**
     * Retourne les variables utilisées dans le template
     */
    public function getUsedVariables(): array
    {
        $content = ($this->subject ?? '') . ($this->bodyHtml ?? '') . ($this->bodyText ?? '');
        $used = [];
        
        foreach (array_keys(self::AVAILABLE_VARIABLES) as $variable) {
            if (str_contains($content, $variable)) {
                $used[] = $variable;
            }
        }
        
        return $used;
    }

    /**
     * Vérifie si le template contient une variable
     */
    public function hasVariable(string $variable): bool
    {
        $content = ($this->subject ?? '') . ($this->bodyHtml ?? '') . ($this->bodyText ?? '');
        return str_contains($content, $variable);
    }

    /**
     * Génère un aperçu avec des données de test
     */
    public function generatePreview(): array
    {
        $testVariables = [
            '{{client_name}}' => 'Jean Dupont',
            '{{client_email}}' => 'jean.dupont@example.com',
            '{{client_company}}' => 'Entreprise Test',
            '{{document_reference}}' => 'PROV001',
            '{{document_date}}' => date('d/m/Y'),
            '{{document_amount}}' => '1 000 000 FCFA',
            '{{document_amount_ht}}' => '840 336 FCFA',
            '{{document_due_date}}' => date('d/m/Y', strtotime('+30 days')),
            '{{document_link}}' => 'https://example.com/share/abc123',
            '{{company_name}}' => 'KTC-CENTER SARL',
            '{{company_email}}' => 'contact@ktc-center.com',
            '{{company_phone}}' => '+237 6XX XXX XXX',
            '{{current_date}}' => date('d/m/Y'),
            '{{sender_name}}' => 'Service Commercial',
        ];

        return [
            'subject' => $this->renderSubject($testVariables),
            'bodyHtml' => $this->renderBodyHtml($testVariables),
            'bodyText' => $this->renderBodyText($testVariables),
        ];
    }

    /**
     * Clone le template
     */
    public function duplicate(string $newName, string $newCode): self
    {
        $clone = new self();
        $clone->name = $newName;
        $clone->code = $newCode;
        $clone->type = $this->type;
        $clone->subject = $this->subject;
        $clone->bodyHtml = $this->bodyHtml;
        $clone->bodyText = $this->bodyText;
        $clone->isDefault = false;
        $clone->isActive = true;
        
        return $clone;
    }
}
