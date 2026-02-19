<?php

namespace App\Service;

use App\Entity\Proforma;
use App\Entity\Invoice;
use App\Repository\ProformaRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReferenceGeneratorService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProformaRepository $proformaRepository,
        private InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Génère une référence unique pour une proforma
     * Format: PROV{ANNEE}{NUMERO} ex: PROV2026001
     */
    public function generateProformaReference(): string
    {
        $year = date('Y');
        $prefix = 'PROV' . $year;
        
        // Trouve le dernier numéro de l'année en cours
        $lastProforma = $this->proformaRepository->createQueryBuilder('p')
            ->where('p.reference LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('p.reference', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastProforma) {
            $lastNumber = (int) substr($lastProforma->getReference(), strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Génère une référence unique pour une facture
     * Format: FAC{ANNEE}{NUMERO} ex: FAC2026001
     */
    public function generateInvoiceReference(): string
    {
        $year = date('Y');
        $prefix = 'FAC' . $year;
        
        // Trouve le dernier numéro de l'année en cours
        $lastInvoice = $this->invoiceRepository->createQueryBuilder('i')
            ->where('i.reference LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('i.reference', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->getReference(), strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Génère un code produit unique
     * Format: PRD{TYPE}{NUMERO} ex: PRDLOG001
     */
    public function generateProductCode(string $type): string
    {
        $typePrefix = match($type) {
            'LOGICIEL' => 'LOG',
            'MATERIEL' => 'MAT',
            'SERVICE' => 'SRV',
            default => 'PRD'
        };

        $prefix = 'PRD' . $typePrefix;
        
        $conn = $this->entityManager->getConnection();
        $sql = "SELECT code FROM product WHERE code LIKE :prefix ORDER BY code DESC LIMIT 1";
        $result = $conn->executeQuery($sql, ['prefix' => $prefix . '%'])->fetchOne();

        if ($result) {
            $lastNumber = (int) substr($result, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Génère un token unique pour le partage de documents
     */
    public function generateShareToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Génère un code client unique
     * Format: CLI{NUMERO} ex: CLI00001
     */
    public function generateClientCode(): string
    {
        $prefix = 'CLI';
        
        $conn = $this->entityManager->getConnection();
        $sql = "SELECT COUNT(*) as count FROM client";
        $count = (int) $conn->executeQuery($sql)->fetchOne();

        return $prefix . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }
}
