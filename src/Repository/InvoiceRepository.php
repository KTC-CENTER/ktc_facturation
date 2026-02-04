<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * @return Invoice[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Invoice[]
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.issueDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('i.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Factures impayées en retard
     * @return Invoice[]
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.dueDate < :now')
            ->andWhere('i.status = :status')
            ->setParameter('now', new \DateTime())
            ->setParameter('status', Invoice::STATUS_SENT)
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Génère la prochaine référence légale
     */
    public function getNextReference(): string
    {
        $year = date('Y');
        
        $result = $this->createQueryBuilder('i')
            ->select('MAX(i.reference) as maxRef')
            ->andWhere('i.reference LIKE :pattern')
            ->setParameter('pattern', 'FAC' . $year . '%')
            ->getQuery()
            ->getSingleScalarResult();

        if ($result) {
            $number = (int) substr($result, 7);
            return 'FAC' . $year . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
        }

        return 'FAC' . $year . '0001';
    }

    /**
     * Chiffre d'affaires par période
     */
    public function getRevenueByPeriod(\DateTimeInterface $from, \DateTimeInterface $to): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalTTC) as total')
            ->andWhere('i.issueDate BETWEEN :from AND :to')
            ->andWhere('i.status = :status')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Statistiques par statut
     */
    public function getStatsByStatus(): array
    {
        return $this->createQueryBuilder('i')
            ->select('i.status, COUNT(i.id) as count, SUM(i.totalTTC) as total')
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Chiffre d'affaires mensuel sur les 12 derniers mois
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $from = new \DateTime("-{$months} months");
        $from->setDate((int) $from->format('Y'), (int) $from->format('m'), 1);
        
        return $this->createQueryBuilder('i')
            ->select("SUBSTRING(i.issueDate, 1, 7) as month, SUM(i.totalTTC) as total")
            ->andWhere('i.issueDate >= :from')
            ->andWhere('i.status = :status')
            ->setParameter('from', $from)
            ->setParameter('status', Invoice::STATUS_PAID)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche globale
     * @return Invoice[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.client', 'c')
            ->andWhere('i.reference LIKE :query OR i.object LIKE :query OR c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Total TTC des factures par statut
     */
    public function getTotalByStatus(string $status): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalTTC) as total')
            ->andWhere('i.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Chiffre d'affaires total par période (alias pour le Dashboard)
     */
    public function getTotalRevenueByPeriod(\DateTimeInterface $from, \DateTimeInterface $to): float
    {
        return $this->getRevenueByPeriod($from, $to);
    }

    /**
     * Chiffre d'affaires par type de produit
     */
    public function getRevenueByProductType(): array
    {
        return $this->createQueryBuilder('i')
            ->select('p.type as productType, SUM(di.unitPrice * di.quantity) as total')
            ->join('i.items', 'di')
            ->join('di.product', 'p')
            ->andWhere('i.status = :status')
            ->setParameter('status', Invoice::STATUS_PAID)
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();
    }
}
