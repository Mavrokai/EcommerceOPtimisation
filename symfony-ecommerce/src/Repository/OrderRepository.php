<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Trouve toutes les commandes associées à un utilisateur (commandes liées + commandes d'invité avec le même email)
     * 
     * @param User $user
     * @return Order[]
     */
    public function findAllOrdersForUser($user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->orWhere('o.guestEmail = :email AND o.user IS NULL')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Associe les commandes d'invité à un utilisateur basé sur l'email
     * 
     * @param User $user
     * @return int Nombre de commandes mises à jour
     */
    public function linkGuestOrdersToUser($user): int
    {
        return $this->createQueryBuilder('o')
            ->update()
            ->set('o.user', ':user')
            ->where('o.guestEmail = :email AND o.user IS NULL')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getQuery()
            ->execute();
    }
}
