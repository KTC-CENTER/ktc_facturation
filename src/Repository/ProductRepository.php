<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->andWhere('p.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :query OR p.code LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par type de produit
     */
    public function getStatsByType(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.type, COUNT(p.id) as count')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();
    }
}
