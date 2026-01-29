<?php

namespace App\Repository;

use App\Entity\DocumentShare;
use App\Entity\Proforma;
use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentShare>
 */
class DocumentShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentShare::class);
    }

    /**
     * Trouve un partage par son token
     */
    public function findByToken(string $token): ?DocumentShare
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un partage valide par token
     */
    public function findValidByToken(string $token): ?DocumentShare
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.token = :token')
            ->andWhere('ds.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return DocumentShare[]
     */
    public function findByProforma(Proforma $proforma): array
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.proforma = :proforma')
            ->setParameter('proforma', $proforma)
            ->orderBy('ds.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return DocumentShare[]
     */
    public function findByInvoice(Invoice $invoice): array
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->orderBy('ds.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Partages expirés
     * @return DocumentShare[]
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('ds')
            ->andWhere('ds.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques de partage par type
     */
    public function getShareStats(): array
    {
        return $this->createQueryBuilder('ds')
            ->select('ds.shareType, COUNT(ds.id) as count, SUM(ds.viewCount) as totalViews')
            ->groupBy('ds.shareType')
            ->getQuery()
            ->getResult();
    }

    /**
     * Partages récents
     * @return DocumentShare[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('ds')
            ->orderBy('ds.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Nettoyage des partages expirés
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('ds')
            ->delete()
            ->andWhere('ds.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
