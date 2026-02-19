<?php

namespace App\Repository;

use App\Entity\TemplateItem;
use App\Entity\ProformaTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TemplateItem>
 */
class TemplateItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemplateItem::class);
    }

    /**
     * @return TemplateItem[]
     */
    public function findByTemplate(ProformaTemplate $template): array
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.template = :template')
            ->setParameter('template', $template)
            ->orderBy('ti.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Éléments requis d'un template
     * @return TemplateItem[]
     */
    public function findRequiredItems(ProformaTemplate $template): array
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.template = :template')
            ->andWhere('ti.isOptional = :optional')
            ->setParameter('template', $template)
            ->setParameter('optional', false)
            ->orderBy('ti.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Éléments optionnels d'un template
     * @return TemplateItem[]
     */
    public function findOptionalItems(ProformaTemplate $template): array
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.template = :template')
            ->andWhere('ti.isOptional = :optional')
            ->setParameter('template', $template)
            ->setParameter('optional', true)
            ->orderBy('ti.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
