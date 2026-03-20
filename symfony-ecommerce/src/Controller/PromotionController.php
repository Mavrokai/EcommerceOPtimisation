<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Page vitrine des promotions (config/routes.yaml : /promotions, /promotion/{slug}).
 */
class PromotionController extends AbstractController
{
    public function index(ProductRepository $productRepository): Response
    {
        $promoProducts = $productRepository->findBy(['isPromo' => true], ['id' => 'ASC'], 8);

        if (\count($promoProducts) < 8) {
            $extraProducts = $productRepository->findBy([], ['id' => 'ASC'], 8 - \count($promoProducts));
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
            'Soins Vétérinaires',
        ];
        $promoDescriptions = [
            'Nutrition premium à prix réduit',
            'Tout pour le confort de vos animaux',
            'Repas stylés pour vos compagnons',
            'Hygiène complète à petit prix',
            'Amusement garanti pour vos pets',
            'Sécurité et style réunis',
            'Récompenses saines et savoureuses',
            'Santé et bien-être prioritaires',
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
            'VÉTO',
        ];

        foreach ($promoProducts as $index => $product) {
            $mockPromotions[] = [
                'title' => $promoTitles[$index] ?? 'Offre Spéciale',
                'description' => $promoDescriptions[$index] ?? 'Découvrez nos meilleures promotions',
                'imageUrl' => $product->getImageUrl() ?: 'promo-image.png',
                'buttonText' => 'Découvrir',
                'buttonUrl' => $this->generateUrl('product_detail', ['id' => $product->getId()]),
                'discountPercent' => $product->isPromo() ? random_int(15, 35) : random_int(10, 30),
                'size' => $promoSizes[$index] ?? 'medium',
                'productName' => $product->getName(),
                'productPrice' => $product->getPrice(),
                'badge' => $promoBadges[$index] ?? null,
            ];
        }

        return $this->render('promotion/index.html.twig', [
            'promotions' => $mockPromotions,
        ]);
    }

    public function show(string $slug, ProductRepository $productRepository, SluggerInterface $slugger): Response
    {
        $product = null;
        if (ctype_digit($slug)) {
            $product = $productRepository->find((int) $slug);
        }

        if (!$product instanceof Product) {
            foreach ($productRepository->findAll() as $p) {
                $nameSlug = strtolower((string) $slugger->slug($p->getName() ?? ''));
                if ($nameSlug === $slug) {
                    $product = $p;
                    break;
                }
            }
        }

        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Offre ou produit introuvable.');
        }

        return $this->redirectToRoute('product_detail', ['id' => $product->getId()], Response::HTTP_FOUND);
    }
}
