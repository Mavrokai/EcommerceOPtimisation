<?php

namespace App\Controller;
use App\Entity\Product;
use App\Entity\SubCategory;
use App\Entity\User;
use App\Entity\Review;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/search', methods: ['GET'], name: 'product_search')]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }
        
        $products = $em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.subCategory', 's')
            ->leftJoin('s.category', 'c')
            ->where('LOWER(p.name) LIKE LOWER(:query) OR LOWER(p.description) LIKE LOWER(:query) OR LOWER(s.name) LIKE LOWER(:query) OR LOWER(c.name) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(8)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
        
        $data = [];
        foreach ($products as $product) {
            $categoryName = '';
            $subCategoryName = '';
            if ($product->getSubCategory()) {
                $subCategoryName = $product->getSubCategory()->getName();
                if ($product->getSubCategory()->getCategory()) {
                    $categoryName = $product->getSubCategory()->getCategory()->getName();
                }
            }
            
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'description' => $product->getDescription() ? substr($product->getDescription(), 0, 100) . '...' : '',
                'category' => $categoryName,
                'subcategory' => $subCategoryName,
                'image' => $product->getImageUrl() ? '/assets/images/' . $product->getImageUrl() : '/assets/images/default-product.svg',
                'url' => $this->generateUrl('product_detail', ['id' => $product->getId()]),
                'isPromo' => $product->isPromo(),
                'promotionalPrice' => $product->getPromotionalPrice(),
                'inStock' => $product->getStock() > 0
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $categorySlug = $request->query->get('category');
        $subcategorySlug = $request->query->get('subcategory');
        
        $products = $em->getRepository(Product::class)->findAll();
        
        $categories = $em->getRepository(\App\Entity\Category::class)->findAll();
        $subCategories = $em->getRepository(\App\Entity\SubCategory::class)->findAll();

        return $this->render('pages/productList.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'subCategories' => $subCategories,
            'navbarCategory' => $categorySlug,
            'navbarSubcategory' => $subcategorySlug,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!$data || !isset($data['name'], $data['price'])) {
            return $this->json(['error' => 'Invalid data'], 400);
        }
    
        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setDescription($data['description'] ?? null);
        $product->setStock($data['stock'] ?? 0);
        $product->setIsPromo($data['isPromo'] ?? false);
        $product->setIsNew($data['isNew'] ?? false);
        $product->setCreatedAt(new \DateTime());
        $product->setUpdatedAt(new \DateTime());
    
        $subCategoryId = $data['subCategoryId'] ?? null;
        if ($subCategoryId) {
            $subCategory = $em->getRepository(\App\Entity\SubCategory::class)->find($subCategoryId);
            if (!$subCategory) {
                return $this->json(['error' => 'Sub-category not found'], 400);
            }
            $product->setSubCategory($subCategory);
        }
    
        $em->persist($product);
        $em->flush();
    
        return $this->json(['message' => 'Produit créé !', 'id' => $product->getId()], 201);
    }
    #[Route('/api/{id}', name: 'product_api', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'stock' => $product->getStock(),
            'isPromo' => $product->isPromo(),
            'isNew' => $product->isNew(),
            'reviews' => array_map(function (Review $review) {
                $user = $review->getUser();
                $username = $user->getFirstName() ?: $user->getEmail();
                
                return [
                    'id' => $review->getId(),
                    'rating' => $review->getRating(),
                    'comment' => $review->getComment(),
                    'createdAt' => $review->getCreatedAt()->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $user->getId(),
                        'username' => $username,
                    ],
                ];
            }, $product->getReviews()->toArray()),
        ]);
    }
    #[Route('/details/{id}', name: 'product_detail', methods: ['GET'])]
    public function productDetails(int $id, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('pages/ProductDetail.html.twig', [
            'product' => $product, 
        ]);
    }

    #[Route('/comment/{id}', name: 'app_product_comment', methods: ['POST'])]
    public function addComment(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $authorName = $request->request->get('author_name');
        $commentText = $request->request->get('comment_text');
        $rating = $request->request->get('rating');

        if (empty($authorName) || empty($commentText) || empty($rating)) {
            $this->addFlash('comment_error', 'Tous les champs sont requis.');
            return $this->redirectToRoute('product_detail', ['id' => $id]);
        }

        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            $this->addFlash('comment_error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirectToRoute('product_detail', ['id' => $id]);
        }

        $comment = new Comment();
        $comment->setAuthorName($authorName);
        $comment->setContent($commentText);
        $comment->setRating((int) $rating);
        $comment->setProduct($product);

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('comment_success', 'Votre commentaire a été ajouté avec succès !');
        
        return $this->redirectToRoute('product_detail', ['id' => $id]);
    }


    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, Product $product, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'], $data['price'])) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setDescription($data['description'] ?? $product->getDescription());
        $product->setStock($data['stock'] ?? $product->getStock());
        $product->setIsPromo($data['isPromo'] ?? $product->isPromo());
        $product->setIsNew($data['isNew'] ?? $product->isNew());
        $product->setUpdatedAt(new \DateTime());

        $em->flush();

        return $this->json(['message' => 'Produit modifié !']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return $this->json(['message' => 'Produit supprimé !']);
    }

    private function getSubcategoryName(string $slug): ?string
    {
        return match($slug) {
            'accessoires' => 'Laisses & Colliers',
            'habitat' => 'Gamelles & Accessoires',
            'arbres' => 'Arbres à chat',
            'graines' => 'Graines & Alimentation',
            'cages' => 'Cages & Volières',
            'perchoirs' => 'Perchoirs',
            'chauffage' => 'Chauffage & UV',
            'terrariums' => 'Terrariums',
            'decoration' => 'Décoration',
            'alimentation' => match($this->getCurrentCategory()) {
                'Chiens' => 'Croquettes pour chiens',
                'Chats' => 'Croquettes pour chats',
                'Oiseaux' => 'Graines & Alimentation',
                'Lézards' => 'Alimentation',
                default => 'Alimentation'
            },
            'jouets' => match($this->getCurrentCategory()) {
                'Chiens' => 'Os & Jouets',
                'Chats' => 'Jouets pour chats',
                'Oiseaux' => 'Jouets & Accessoires',
                default => 'Jouets'
            },
            'hygiene' => match($this->getCurrentCategory()) {
                'Chiens' => 'Soins & Hygiène',
                'Chats' => 'Soins & Hygiène', 
                'Oiseaux' => 'Soins & Vitamines',
                'Lézards' => 'Soins',
                default => 'Soins'
            },
            'soins' => match($this->getCurrentCategory()) {
                'Oiseaux' => 'Soins & Vitamines',
                'Lézards' => 'Soins',
                default => 'Soins'
            },
            default => null
        };
    }

    private ?string $currentCategory = null;

    private function getCurrentCategory(): ?string
    {
        return $this->currentCategory;
    }
}