<?php

namespace App\Repository;

use App\Entity\PromoCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromoCode>
 */
class PromoCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoCode::class);
    }

    public function findActivePromoCode(string $code): ?PromoCode
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.isActive = true')
            ->andWhere('p.validFrom <= :now')
            ->andWhere('p.validTo >= :now')
            ->setParameter('code', $code)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findValidPromoCodes(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.validFrom <= :now')
            ->andWhere('p.validTo >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
