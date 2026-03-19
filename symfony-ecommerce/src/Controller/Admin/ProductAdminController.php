<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/products')]
class ProductAdminController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private CategoryRepository $categoryRepository,
        private SubCategoryRepository $subCategoryRepository
    ) {}

    #[Route('/', name: 'admin_product_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $filters = [
            'search' => $request->query->get('search', ''),
            'category' => $request->query->getInt('category') ?: null,
            'subcategory' => $request->query->getInt('subcategory') ?: null,
            'promo' => $request->query->has('promo') ? $request->query->getBoolean('promo') : null,
            'new' => $request->query->has('new') ? $request->query->getBoolean('new') : null,
            'stock_status' => $request->query->get('stock_status', ''),
            'price_min' => $request->query->get('price_min') ? (float)$request->query->get('price_min') : null,
            'price_max' => $request->query->get('price_max') ? (float)$request->query->get('price_max') : null,
            'sort' => $request->query->get('sort', 'created_desc'),
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        try {
            if (!empty($filters)) {
                $products = $this->productRepository->findByAdvancedFilters($filters, $page, $limit);
                $totalProducts = $this->productRepository->countByAdvancedFilters($filters);
            } else {
                $offset = ($page - 1) * $limit;
                $products = $this->productRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
                $totalProducts = $this->productRepository->count([]);
            }

            $totalPages = ceil($totalProducts / $limit);
            $hasNextPage = $page < $totalPages;
            $hasPrevPage = $page > 1;

            $categories = $this->categoryRepository->findAll();
            $subCategories = $this->subCategoryRepository->findAll();

            return $this->render('admin/product/index.html.twig', [
                'products' => $products,
                'totalProducts' => $totalProducts,
                'categories' => $categories,
                'subCategories' => $subCategories,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'hasNextPage' => $hasNextPage,
                'hasPrevPage' => $hasPrevPage,
                'limit' => $limit,
                'search' => $request->query->get('search', ''),
                'categoryFilter' => $request->query->getInt('category') ?: null,
                'subCategoryFilter' => $request->query->getInt('subcategory') ?: null,
                'promoFilter' => $request->query->has('promo') ? $request->query->getBoolean('promo') : null,
                'newFilter' => $request->query->has('new') ? $request->query->getBoolean('new') : null,
                'stockFilter' => $request->query->get('stock_status', ''),
                'priceMinFilter' => $request->query->get('price_min', ''),
                'priceMaxFilter' => $request->query->get('price_max', ''),
                'sortFilter' => $request->query->get('sort', 'created_desc'),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des produits: ' . $e->getMessage());
            
            return $this->render('admin/product/index.html.twig', [
                'products' => [],
                'totalProducts' => 0,
                'categories' => $this->categoryRepository->findAll(),
                'subCategories' => $this->subCategoryRepository->findAll(),
                'currentPage' => 1,
                'totalPages' => 0,
                'hasNextPage' => false,
                'hasPrevPage' => false,
                'limit' => 20,
                'search' => '',
                'categoryFilter' => null,
                'subCategoryFilter' => null,
                'promoFilter' => null,
                'newFilter' => null,
                'stockFilter' => '',
                'priceMinFilter' => '',
                'priceMaxFilter' => '',
                'sortFilter' => 'created_desc',
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $product = new Product();
        
        $subCategoryId = $request->query->getInt('subcategory');
        if ($subCategoryId) {
            $subCategory = $this->subCategoryRepository->find($subCategoryId);
            if ($subCategory) {
                $product->setSubCategory($subCategory);
            }
        }
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageUrl')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $product->setImageUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès !');

            $redirectParams = [];
            if ($product->getSubCategory()) {
                $redirectParams['subcategory'] = $product->getSubCategory()->getId();
            }

            return $this->redirectToRoute('admin_product_index', $redirectParams);
        }

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageUrl')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );

                    $oldImage = $product->getImageUrl();
                    if ($oldImage && file_exists($this->getParameter('images_directory').'/'.$oldImage)) {
                        unlink($this->getParameter('images_directory').'/'.$oldImage);
                    }

                    $product->setImageUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès !');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            try {
                // Vérifier s'il y a des commandes liées à ce produit
                $orderItems = $this->entityManager->getRepository('App\Entity\OrderItem')->findBy(['product' => $product]);
                
                if (!empty($orderItems)) {
                    $this->addFlash('error', 
                        'Impossible de supprimer ce produit car il est référencé dans ' . count($orderItems) . 
                        ' commande(s). Vous pouvez le désactiver ou modifier son stock à 0 à la place.'
                    );
                    return $this->redirectToRoute('admin_product_index');
                }

                $imageUrl = $product->getImageUrl();
                if ($imageUrl && file_exists($this->getParameter('images_directory').'/'.$imageUrl)) {
                    try {
                        unlink($this->getParameter('images_directory').'/'.$imageUrl);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Produit supprimé mais l\'image n\'a pas pu être supprimée.');
                    }
                }

                $this->entityManager->remove($product);
                $this->entityManager->flush();

                $this->addFlash('success', 'Produit supprimé avec succès !');
                
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', 
                    'Impossible de supprimer ce produit car il est référencé dans des commandes existantes. ' .
                    'Vous pouvez le désactiver ou modifier son stock à 0 à la place.'
                );
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/{id}/toggle-promo', name: 'admin_product_toggle_promo', methods: ['POST'])]
    public function togglePromo(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('toggle-promo'.$product->getId(), $request->request->get('_token'))) {
            if ($product->isPromo()) {
                // Désactiver la promotion
                $product->setIsPromo(false);
                $status = 'désactivée';
            } else {
                $product->setIsPromo(true);
                if (!$product->getPromoPercentage() || (float) $product->getPromoPercentage() <= 0) {
                    $product->setPromoPercentage('10');
                }
                $status = 'activée';
            }
            
            $this->entityManager->flush();

            $this->addFlash('success', "Promotion {$status} pour le produit {$product->getName()}");
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/{id}/toggle-new', name: 'admin_product_toggle_new', methods: ['POST'])]
    public function toggleNew(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('toggle-new'.$product->getId(), $request->request->get('_token'))) {
            $product->setIsNew(!$product->isNew());
            $this->entityManager->flush();

            $status = $product->isNew() ? 'marqué comme nouveau' : 'retiré des nouveautés';
            $this->addFlash('success', "Produit {$status}: {$product->getName()}");
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_product_index');
    }
}
