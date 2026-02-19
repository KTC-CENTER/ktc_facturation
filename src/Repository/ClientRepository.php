<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 *
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function save(Client $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Client $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne un QueryBuilder pour la liste paginée
     */
    public function createListQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', false)
            ->orderBy('c.name', 'ASC');
    }

    /**
     * Trouve les clients actifs (non archivés)
     * @return Client[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', false)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les clients archivés
     * @return Client[]
     */
    public function findArchived(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de clients
     * @return Client[]
     */
    public function search(string $query, bool $includeArchived = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :query OR c.email LIKE :query OR c.phone LIKE :query OR c.contactPerson LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC');

        if (!$includeArchived) {
            $qb->andWhere('c.isArchived = :archived')
               ->setParameter('archived', false);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les clients par ville
     * @return Client[]
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.city = :city')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('city', $city)
            ->setParameter('archived', false)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les villes distinctes
     * @return string[]
     */
    public function findDistinctCities(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('DISTINCT c.city')
            ->andWhere('c.city IS NOT NULL')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', false)
            ->orderBy('c.city', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'city');
    }

    /**
     * Retourne les meilleurs clients par chiffre d'affaires
     * @return Client[]
     */
    public function findTopClients(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.invoices', 'i')
            ->andWhere('c.isArchived = :archived')
            ->andWhere('i.status = :paid')
            ->setParameter('archived', false)
            ->setParameter('paid', 'paid')
            ->groupBy('c.id')
            ->orderBy('SUM(i.totalTTC)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les clients par mois de création
     */
    public function countByMonth(int $year): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('MONTH(c.createdAt) as month, COUNT(c.id) as count')
            ->andWhere('YEAR(c.createdAt) = :year')
            ->setParameter('year', $year)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $months = array_fill(1, 12, 0);
        foreach ($result as $row) {
            $months[(int)$row['month']] = (int)$row['count'];
        }

        return $months;
    }

    /**
     * Trouve les clients récents
     * @return Client[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', false)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le total des clients actifs
     */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
