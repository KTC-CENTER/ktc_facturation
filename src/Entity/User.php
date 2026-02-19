<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_VIEWER = 'ROLE_VIEWER';
    public const ROLE_COMMERCIAL = 'ROLE_COMMERCIAL';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const ROLES = [
        self::ROLE_VIEWER => 'Visualiseur',
        self::ROLE_COMMERCIAL => 'Commercial',
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_SUPER_ADMIN => 'Super Administrateur',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $phone = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\OneToMany(targetEntity: Proforma::class, mappedBy: 'createdBy')]
    private Collection $proformas;

    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'createdBy')]
    private Collection $invoices;

    #[ORM\OneToMany(targetEntity: DocumentShare::class, mappedBy: 'createdBy')]
    private Collection $documentShares;

    public function __construct()
    {
        $this->proformas = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->documentShares = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->roles = [self::ROLE_VIEWER];
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_VIEWER
        $roles[] = self::ROLE_VIEWER;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getMainRole(): string
    {
        $roles = $this->getRoles();
        
        if (in_array(self::ROLE_SUPER_ADMIN, $roles)) {
            return self::ROLE_SUPER_ADMIN;
        }
        if (in_array(self::ROLE_ADMIN, $roles)) {
            return self::ROLE_ADMIN;
        }
        if (in_array(self::ROLE_COMMERCIAL, $roles)) {
            return self::ROLE_COMMERCIAL;
        }
        
        return self::ROLE_VIEWER;
    }

    public function getMainRoleLabel(): string
    {
        return self::ROLES[$this->getMainRole()] ?? 'Visualiseur';
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getInitials(): string
    {
        $initials = '';
        if ($this->firstName) {
            $initials .= mb_substr($this->firstName, 0, 1);
        }
        if ($this->lastName) {
            $initials .= mb_substr($this->lastName, 0, 1);
        }
        return mb_strtoupper($initials);
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

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    /**
     * @return Collection<int, Proforma>
     */
    public function getProformas(): Collection
    {
        return $this->proformas;
    }

    public function addProforma(Proforma $proforma): static
    {
        if (!$this->proformas->contains($proforma)) {
            $this->proformas->add($proforma);
            $proforma->setCreatedBy($this);
        }

        return $this;
    }

    public function removeProforma(Proforma $proforma): static
    {
        if ($this->proformas->removeElement($proforma)) {
            // set the owning side to null (unless already changed)
            if ($proforma->getCreatedBy() === $this) {
                $proforma->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setCreatedBy($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getCreatedBy() === $this) {
                $invoice->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DocumentShare>
     */
    public function getDocumentShares(): Collection
    {
        return $this->documentShares;
    }

    public function addDocumentShare(DocumentShare $documentShare): static
    {
        if (!$this->documentShares->contains($documentShare)) {
            $this->documentShares->add($documentShare);
            $documentShare->setSharedBy($this);
        }

        return $this;
    }

    public function removeDocumentShare(DocumentShare $documentShare): static
    {
        if ($this->documentShares->removeElement($documentShare)) {
            if ($documentShare->getSharedBy() === $this) {
                $documentShare->setSharedBy(null);
            }
        }

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }

    public function isResetTokenValid(): bool
    {
        if (!$this->resetToken || !$this->resetTokenExpiresAt) {
            return false;
        }

        return $this->resetTokenExpiresAt > new \DateTime();
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
