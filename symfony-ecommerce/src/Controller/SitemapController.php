<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/robots.txt', name: 'robots', priority: 10)]
    public function robots(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $content = <<<ROBOTS
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /cart/
Disallow: /profile/
Disallow: /panier/
Disallow: /_profiler/
Disallow: /_wdt/

Sitemap: {$baseUrl}/sitemap.xml
ROBOTS;

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    #[Route('/sitemap.xml', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $baseUrl = $request->getSchemeAndHttpHost();

        $staticUrls = [
            ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $baseUrl . '/product', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => $baseUrl . '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . '/about', 'priority' => '0.4', 'changefreq' => 'monthly'],
        ];

        $categories = $categoryRepository->findAll();
        $categoryUrls = [];
        foreach ($categories as $category) {
            $categoryUrls[] = [
                'loc' => $baseUrl . '/product?category=' . urlencode(strtolower($category->getName())),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        $products = $productRepository->findAll();
        $productUrls = [];
        foreach ($products as $product) {
            $productUrls[] = [
                'loc' => $baseUrl . '/product/' . $product->getId(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        $response = new Response(
            $this->renderView('sitemap/index.xml.twig', [
                'urls' => array_merge($staticUrls, $categoryUrls, $productUrls),
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml']
        );

        return $response;
    }
}
