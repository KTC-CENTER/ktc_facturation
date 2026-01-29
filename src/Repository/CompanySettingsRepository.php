<?php

namespace App\Repository;

use App\Entity\CompanySettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanySettings>
 */
class CompanySettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanySettings::class);
    }

    /**
     * Récupère les paramètres de l'entreprise (singleton)
     */
    public function getSettings(): ?CompanySettings
    {
        return $this->createQueryBuilder('cs')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère ou crée les paramètres
     */
    public function getOrCreateSettings(): CompanySettings
    {
        $settings = $this->getSettings();
        
        if (!$settings) {
            $settings = new CompanySettings();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }
        
        return $settings;
    }
}
