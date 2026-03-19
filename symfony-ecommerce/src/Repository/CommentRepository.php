<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[] Returns an array of Comment objects
     */
    public function findByProduct($product): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.product = :product')
            ->setParameter('product', $product)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneById($id): ?Comment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
