<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        CacheInterface $cache
    ): Response {
        $categories = $cache->get('homepage_categories', function (ItemInterface $item) use ($categoryRepository) {
            $item->expiresAfter(3600);
            return $categoryRepository->findAll();
        });

        $promoProducts = $productRepository->findBy(['isPromo' => true], ['id' => 'ASC'], 8);
        
        if (count($promoProducts) < 8) {
            $extraProducts = $productRepository->findBy([], ['id' => 'ASC'], 8 - count($promoProducts));
            $promoProducts = array_merge($promoProducts, $extraProducts);
        }
        
        $mockPromotions = [];
        $promoTitles = [
            'Méga Promo Croquettes',
            'Accessoires -30%', 
            'Gamelles Design',
            'Pack Hygiène',
            'Jouets Interactifs',
            'Colliers & Laisses',
            'Friandises Naturelles',
            'Soins Vétérinaires'
        ];
        $promoDescriptions = [
            'Nutrition premium à prix réduit',
            'Tout pour le confort de vos animaux',
            'Repas stylés pour vos compagnons',
            'Hygiène complète à petit prix',
            'Amusement garanti pour vos pets',
            'Sécurité et style réunis',
            'Récompenses saines et savoureuses',
            'Santé et bien-être prioritaires'
        ];
        $promoSizes = ['large', 'medium', 'medium', 'medium', 'large', 'medium', 'medium', 'medium'];
        $promoBadges = [
            'FLASH SALE',
            '-30%',
            'NOUVEAU',
            'PACK',
            'TOP VENTE',
            'QUALITÉ',
            'BIO',
            'VÉTO'
        ];
        
        foreach ($promoProducts as $index => $product) {
            $mockPromotions[] = [
                'title' => $promoTitles[$index] ?? 'Offre Spéciale',
                'description' => $promoDescriptions[$index] ?? 'Découvrez nos meilleures promotions',
                'imageUrl' => $product->getImageUrl() ?: 'promo-image.png',
                'buttonText' => 'Découvrir',
                'buttonUrl' => $this->generateUrl('product_detail', ['id' => $product->getId()]),
                'discountPercent' => $product->isPromo() ? rand(15, 35) : rand(10, 30),
                'size' => $promoSizes[$index] ?? 'medium',
                'productName' => $product->getName(),
                'productPrice' => $product->getPrice(),
                'badge' => $promoBadges[$index] ?? null
            ];
        }

        $allProducts = $productRepository->findBy([], ['id' => 'ASC'], 10);
        $featuredProducts = [];
        
        $badges = ['Bestseller', 'Top Ventes', 'Promo'];
        $counter = 0;
        
        foreach ($allProducts as $product) {
            if ($counter >= 3) break;
            
            $featuredProducts[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'oldPrice' => $product->isPromo() ? round($product->getPrice() * 1.2, 2) : null,
                'image' => $product->getImageUrl() ?: 'default-product.svg',
                'rating' => 4.5 + (rand(0, 8) / 10),
                'reviewCount' => rand(20, 150),
                'badge' => $badges[$counter % 3],
                'description' => substr($product->getDescription(), 0, 50) . '...'
            ];
            $counter++;
        }

        // Avis clients pour la crédibilité
        $customerReviews = [
            [
                'name' => 'Marie Dupont',
                'avatar' => 'default-avatar.svg',
                'rating' => 5,
                'comment' => 'Service excellent ! Mon chat adore ses nouvelles croquettes. Livraison rapide et soignée.',
                'product' => 'Croquettes Chat Premium',
                'date' => '2 jours'
            ],
            [
                'name' => 'Pierre Lambert',
                'avatar' => 'default-avatar.svg',
                'rating' => 5,
                'comment' => 'Très satisfait de ma commande. Le jouet Kong est vraiment résistant, parfait pour mon labrador.',
                'product' => 'Kong Classic',
                'date' => '5 jours'
            ],
            [
                'name' => 'Sophie Martin',
                'avatar' => 'default-avatar.svg',
                'rating' => 4,
                'comment' => 'Large choix de produits de qualité. Prix compétitifs et conseils utiles.',
                'product' => 'Accessoires Chien',
                'date' => '1 semaine'
            ]
        ];

        return $this->render('pages/home.html.twig', [
            'categories' => $categories,
            'promotions' => $mockPromotions,
            'featuredProducts' => $featuredProducts,
            'customerReviews' => $customerReviews,
        ]);
    }
}
