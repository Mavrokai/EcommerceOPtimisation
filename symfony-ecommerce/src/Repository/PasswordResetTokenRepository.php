<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findValidTokenByToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->andWhere('prt.token = :token')
            ->andWhere('prt.isUsed = false')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findValidTokenByUser(User $user): ?PasswordResetToken
    {
        return $this->createQueryBuilder('prt')
            ->andWhere('prt.user = :user')
            ->andWhere('prt.isUsed = false')
            ->andWhere('prt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('prt.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function removeExpiredTokens(): int
    {
        return $this->createQueryBuilder('prt')
            ->delete()
            ->andWhere('prt.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
