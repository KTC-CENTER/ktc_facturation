<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les utilisateurs actifs
     * @return User[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les utilisateurs par rôle
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commerciaux actifs
     * @return User[]
     */
    public function findActiveCommercials(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1 OR JSON_CONTAINS(u.roles, :adminRole) = 1')
            ->setParameter('active', true)
            ->setParameter('role', json_encode(User::ROLE_COMMERCIAL))
            ->setParameter('adminRole', json_encode(User::ROLE_ADMIN))
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'utilisateurs
     * @return User[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs par rôle
     */
    public function countByRole(): array
    {
        $users = $this->findAll();
        $counts = [
            User::ROLE_SUPER_ADMIN => 0,
            User::ROLE_ADMIN => 0,
            User::ROLE_COMMERCIAL => 0,
            User::ROLE_VIEWER => 0,
        ];

        foreach ($users as $user) {
            $mainRole = $user->getMainRole();
            if (isset($counts[$mainRole])) {
                $counts[$mainRole]++;
            }
        }

        return $counts;
    }

    /**
     * Met à jour la date de dernière connexion
     */
    public function updateLastLogin(User $user): void
    {
        $user->setLastLoginAt(new \DateTime());
        $this->getEntityManager()->flush();
    }
}
