<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_USER')]
class AdminController extends AbstractController
{
    use AdminSecurityTrait;

    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        CategoryRepository $categoryRepository,
        SubCategoryRepository $subCategoryRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        UserRepository $userRepository
    ): Response {

        $stats = [
            'categories' => $categoryRepository->count([]),
            'subCategories' => $subCategoryRepository->count([]),
            'products' => $productRepository->count([]),
            'orders' => $orderRepository->count([]),
            'orders_pending' => $orderRepository->count(['status' => 'en_attente']),
            'users' => $userRepository->count([]),
            'admins' => $userRepository->count(['isAdmin' => true]),
            'verified_users' => $userRepository->count(['isVerified' => true]),
        ];

        $recentCategories = $categoryRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentProducts = $productRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentOrders = $orderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentCategories' => $recentCategories,
            'recentProducts' => $recentProducts,
            'recentOrders' => $recentOrders,
            'recentUsers' => $recentUsers,
        ]);
    }
}
