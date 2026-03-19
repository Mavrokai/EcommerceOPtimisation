<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Recherche des utilisateurs par critères de recherche
     * @return User[]
     */
    public function findBySearchCriteria(string $search, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('u');

        // Recherche dans email, prénom et nom
        if ($search) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('u.email', ':search'),
                $qb->expr()->like('u.firstName', ':search'),
                $qb->expr()->like('u.lastName', ':search')
            ))
            ->setParameter('search', '%' . $search . '%');
        }

        // Critères additionnels
        foreach ($criteria as $field => $value) {
            $qb->andWhere("u.$field = :$field")
               ->setParameter($field, $value);
        }

        return $qb->orderBy('u.createdAt', 'DESC')
                  ->setFirstResult(($page - 1) * $limit)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Compte les utilisateurs par critères de recherche
     */
    public function countBySearchCriteria(string $search, array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('u')
                   ->select('COUNT(u.id)');

        // Recherche dans email, prénom et nom
        if ($search) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('u.email', ':search'),
                $qb->expr()->like('u.firstName', ':search'),
                $qb->expr()->like('u.lastName', ':search')
            ))
            ->setParameter('search', '%' . $search . '%');
        }

        // Critères additionnels
        foreach ($criteria as $field => $value) {
            $qb->andWhere("u.$field = :$field")
               ->setParameter($field, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compte les utilisateurs récents
     */
    public function countRecentUsers(int $days = 30): int
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('u.createdAt >= :date')
                    ->setParameter('date', $date)
                    ->getQuery()
                    ->getSingleScalarResult();
    }
}
