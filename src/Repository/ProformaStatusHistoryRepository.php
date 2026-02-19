<?php

namespace App\Repository;

use App\Entity\ProformaStatusHistory;
use App\Entity\Proforma;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProformaStatusHistory>
 */
class ProformaStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProformaStatusHistory::class);
    }

    /**
     * @return ProformaStatusHistory[]
     */
    public function findByProforma(Proforma $proforma): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.proforma = :proforma')
            ->setParameter('proforma', $proforma)
            ->orderBy('h.changedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
