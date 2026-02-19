<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * @return EmailTemplate[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('et.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EmailTemplate[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.type = :type')
            ->andWhere('et.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('et.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le template par défaut pour un type
     */
    public function findDefaultByType(string $type): ?EmailTemplate
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.type = :type')
            ->andWhere('et.isDefault = :default')
            ->andWhere('et.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un template par son code
     */
    public function findByCode(string $code): ?EmailTemplate
    {
        return $this->createQueryBuilder('et')
            ->andWhere('et.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
