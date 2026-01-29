<?php

namespace App\Repository;

use App\Entity\ProformaTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProformaTemplate>
 */
class ProformaTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProformaTemplate::class);
    }

    /**
     * @return ProformaTemplate[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ProformaTemplate[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.category = :category')
            ->andWhere('t.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou description
     * @return ProformaTemplate[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :query OR t.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste des catégories distinctes
     */
    public function getCategories(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.category')
            ->andWhere('t.category IS NOT NULL')
            ->orderBy('t.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Modèles les plus utilisés
     */
    public function getMostUsed(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->select('t, COUNT(p.id) as usageCount')
            ->leftJoin('t.proformas', 'p')
            ->groupBy('t.id')
            ->orderBy('usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
