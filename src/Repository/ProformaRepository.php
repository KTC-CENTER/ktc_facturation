<?php

namespace App\Repository;

use App\Entity\Proforma;
use App\Entity\User;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proforma>
 */
class ProformaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proforma::class);
    }

    /**
     * @return Proforma[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Proforma[]
     */
    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.client = :client')
            ->setParameter('client', $client)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Proforma[]
     */
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par période
     * @return Proforma[]
     */
    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.issueDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('p.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Proformas qui expirent bientôt (dans les N prochains jours)
     * @return Proforma[]
     */
    public function findExpiringSoon(int $days = 7): array
    {
        $now = new \DateTime();
        $limit = new \DateTime("+{$days} days");

        return $this->createQueryBuilder('p')
            ->andWhere('p.validUntil BETWEEN :now AND :limit')
            ->andWhere('p.status NOT IN (:excludedStatuses)')
            ->setParameter('now', $now)
            ->setParameter('limit', $limit)
            ->setParameter('excludedStatuses', [Proforma::STATUS_INVOICED, Proforma::STATUS_EXPIRED, Proforma::STATUS_REFUSED])
            ->orderBy('p.validUntil', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Proformas expirées non converties
     * @return Proforma[]
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.validUntil < :now')
            ->andWhere('p.status NOT IN (:excludedStatuses)')
            ->setParameter('now', new \DateTime())
            ->setParameter('excludedStatuses', [Proforma::STATUS_INVOICED, Proforma::STATUS_EXPIRED])
            ->getQuery()
            ->getResult();
    }

    /**
     * Génère la prochaine référence
     */
    public function getNextReference(): string
    {
        $result = $this->createQueryBuilder('p')
            ->select('MAX(p.reference) as maxRef')
            ->getQuery()
            ->getSingleScalarResult();

        if ($result) {
            $number = (int) substr($result, 4);
            return 'PROV' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
        }

        return 'PROV0001';
    }

    /**
     * Total HT par période
     */
    public function getTotalByPeriod(\DateTimeInterface $from, \DateTimeInterface $to, ?string $status = null): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.totalHT) as total')
            ->andWhere('p.issueDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($status) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        return (float) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * Statistiques par statut
     */
    public function getStatsByStatus(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count, SUM(p.totalTTC) as total')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche globale
     * @return Proforma[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.client', 'c')
            ->andWhere('p.reference LIKE :query OR p.object LIKE :query OR c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
