<?php

namespace App\Entity;

use App\Repository\ProformaStatusHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProformaStatusHistoryRepository::class)]
#[ORM\Table(name: 'proforma_status_history')]
class ProformaStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Proforma::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Proforma $proforma = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $statusLabel = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $changedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $changedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->changedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->statusLabel = Proforma::STATUSES[$status] ?? $status;
        return $this;
    }

    public function getStatusLabel(): ?string
    {
        return $this->statusLabel;
    }

    public function setStatusLabel(?string $statusLabel): static
    {
        $this->statusLabel = $statusLabel;
        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): static
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function getChangedAt(): ?\DateTimeInterface
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeInterface $changedAt): static
    {
        $this->changedAt = $changedAt;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getStatusColor(): string
    {
        return Proforma::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getStatusIcon(): string
    {
        return match($this->status) {
            Proforma::STATUS_DRAFT => 'pencil',
            Proforma::STATUS_SENT => 'paper-airplane',
            Proforma::STATUS_ACCEPTED => 'check-circle',
            Proforma::STATUS_REFUSED => 'x-circle',
            Proforma::STATUS_INVOICED => 'document-text',
            Proforma::STATUS_EXPIRED => 'clock',
            default => 'information-circle',
        };
    }
}
