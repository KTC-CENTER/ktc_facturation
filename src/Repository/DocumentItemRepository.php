<?php

namespace App\Repository;

use App\Entity\DocumentItem;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentItem>
 */
class DocumentItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentItem::class);
    }

    /**
     * Statistiques de ventes par produit
     */
    public function getSalesByProduct(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('di')
            ->select('p.id, p.name, p.type, SUM(di.quantity) as totalQty, SUM(di.totalHT) as totalRevenue')
            ->leftJoin('di.product', 'p')
            ->leftJoin('di.proforma', 'pf')
            ->leftJoin('di.invoice', 'i')
            ->andWhere('(pf.issueDate BETWEEN :from AND :to) OR (i.issueDate BETWEEN :from AND :to)')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('p.id')
            ->orderBy('totalRevenue', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Produits les plus vendus
     */
    public function getTopProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('di')
            ->select('p.id, p.name, p.type, SUM(di.quantity) as totalQty, SUM(di.totalHT) as totalRevenue')
            ->leftJoin('di.product', 'p')
            ->leftJoin('di.invoice', 'i')
            ->andWhere('i.status = :status')
            ->setParameter('status', 'PAID')
            ->groupBy('p.id')
            ->orderBy('totalRevenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Chiffre d'affaires par type de produit
     */
    public function getRevenueByProductType(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('di')
            ->select('p.type, SUM(di.totalHT) as total')
            ->leftJoin('di.product', 'p')
            ->leftJoin('di.invoice', 'i')
            ->andWhere('i.issueDate BETWEEN :from AND :to')
            ->andWhere('i.status = :status')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', 'PAID')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();
    }
}
