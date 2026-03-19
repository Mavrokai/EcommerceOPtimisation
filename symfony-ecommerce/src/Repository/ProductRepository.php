<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Trouve les produits avec pagination
     */
    public function findPaginated(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->addSelect('s', 'c')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des produits par nom avec pagination (insensible à la casse)
     */
    public function findBySearchPaginated(string $search, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->addSelect('s', 'c')
            ->where('LOWER(p.name) LIKE LOWER(:search) 
                     OR LOWER(p.description) LIKE LOWER(:search) 
                     OR LOWER(s.name) LIKE LOWER(:search) 
                     OR LOWER(c.name) LIKE LOWER(:search)')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de produits trouvés par la recherche (insensible à la casse)
     */
    public function countBySearch(string $search): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->where('LOWER(p.name) LIKE LOWER(:search) 
                     OR LOWER(p.description) LIKE LOWER(:search) 
                     OR LOWER(s.name) LIKE LOWER(:search) 
                     OR LOWER(c.name) LIKE LOWER(:search)')
            ->setParameter('search', '%' . $search . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les produits en promotion
     */
    public function findPromotions(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->addSelect('s', 'c')
            ->where('p.isPromo = :isPromo')
            ->setParameter('isPromo', true)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les nouveaux produits
     */
    public function findNewProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->addSelect('s', 'c')
            ->where('p.isNew = :isNew')
            ->setParameter('isNew', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits filtrés par catégorie et/ou sous-catégorie
     */
    public function findByFilters(?int $categoryId = null, ?int $subCategoryId = null, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->addSelect('s', 'c');

        if ($subCategoryId) {
            $qb->where('s.id = :subCategoryId')
               ->setParameter('subCategoryId', $subCategoryId);
        } elseif ($categoryId) {
            $qb->where('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Compte les produits filtrés par catégorie et/ou sous-catégorie
     */
    public function countByFilters(?int $categoryId = null, ?int $subCategoryId = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c');

        if ($subCategoryId) {
            $qb->where('s.id = :subCategoryId')
               ->setParameter('subCategoryId', $subCategoryId);
        } elseif ($categoryId) {
            $qb->where('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche avancée avec filtres multiples
     */
    public function findByAdvancedFilters(array $filters, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->addSelect('s', 'c');

        // Recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) 
                          OR LOWER(p.description) LIKE LOWER(:search) 
                          OR LOWER(s.name) LIKE LOWER(:search) 
                          OR LOWER(c.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $filters['category']);
        }

        // Filtre par sous-catégorie
        if (!empty($filters['subcategory'])) {
            $qb->andWhere('s.id = :subCategoryId')
               ->setParameter('subCategoryId', $filters['subcategory']);
        }

        // Filtre par statut promotion
        if (isset($filters['promo'])) {
            $qb->andWhere('p.isPromo = :isPromo')
               ->setParameter('isPromo', $filters['promo']);
        }

        // Filtre par statut nouveau
        if (isset($filters['new'])) {
            $qb->andWhere('p.isNew = :isNew')
               ->setParameter('isNew', $filters['new']);
        }

        // Filtre par stock
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'low':
                    $qb->andWhere('p.stock < 10 AND p.stock > 0');
                    break;
                case 'out':
                    $qb->andWhere('p.stock = 0');
                    break;
                case 'available':
                    $qb->andWhere('p.stock > 0');
                    break;
            }
        }

        // Filtre par fourchette de prix
        if (!empty($filters['price_min'])) {
            $qb->andWhere('p.price >= :priceMin')
               ->setParameter('priceMin', $filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $qb->andWhere('p.price <= :priceMax')
               ->setParameter('priceMax', $filters['price_max']);
        }

        // Tri
        $orderBy = $filters['sort'] ?? 'created_desc';
        switch ($orderBy) {
            case 'name_asc':
                $qb->orderBy('p.name', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('p.name', 'DESC');
                break;
            case 'price_asc':
                $qb->orderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.price', 'DESC');
                break;
            case 'stock_asc':
                $qb->orderBy('p.stock', 'ASC');
                break;
            case 'stock_desc':
                $qb->orderBy('p.stock', 'DESC');
                break;
            case 'created_asc':
                $qb->orderBy('p.createdAt', 'ASC');
                break;
            case 'created_desc':
            default:
                $qb->orderBy('p.createdAt', 'DESC');
                break;
        }

        return $qb->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Compte les produits avec filtres avancés
     */
    public function countByAdvancedFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c');

        // Recherche textuelle
        if (!empty($filters['search'])) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) 
                          OR LOWER(p.description) LIKE LOWER(:search) 
                          OR LOWER(s.name) LIKE LOWER(:search) 
                          OR LOWER(c.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $filters['category']);
        }

        // Filtre par sous-catégorie
        if (!empty($filters['subcategory'])) {
            $qb->andWhere('s.id = :subCategoryId')
               ->setParameter('subCategoryId', $filters['subcategory']);
        }

        // Filtre par statut promotion
        if (isset($filters['promo'])) {
            $qb->andWhere('p.isPromo = :isPromo')
               ->setParameter('isPromo', $filters['promo']);
        }

        // Filtre par statut nouveau
        if (isset($filters['new'])) {
            $qb->andWhere('p.isNew = :isNew')
               ->setParameter('isNew', $filters['new']);
        }

        // Filtre par stock
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'low':
                    $qb->andWhere('p.stock < 10 AND p.stock > 0');
                    break;
                case 'out':
                    $qb->andWhere('p.stock = 0');
                    break;
                case 'available':
                    $qb->andWhere('p.stock > 0');
                    break;
            }
        }

        // Filtre par fourchette de prix
        if (!empty($filters['price_min'])) {
            $qb->andWhere('p.price >= :priceMin')
               ->setParameter('priceMin', $filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $qb->andWhere('p.price <= :priceMax')
               ->setParameter('priceMax', $filters['price_max']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
