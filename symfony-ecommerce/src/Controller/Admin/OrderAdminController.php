<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_USER')]
class OrderAdminController extends AbstractController
{
    #[Route('/', name: 'admin_orders_index')]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $allOrders = $orderRepository->findBy([], ['createdAt' => 'DESC']);
        
        $orders = $orderRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );

        $totalOrders = count($allOrders);
        $totalPages = ceil($totalOrders / $limit);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'allOrders' => $allOrders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalOrders' => $totalOrders,
        ]);
    }

    #[Route('/{id}', name: 'admin_orders_show', requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_orders_update_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        $newStatus = $request->request->get('status');
        
        $validStatuses = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
        if ($newStatus && in_array($newStatus, $validStatuses)) {
            $order->setStatus($newStatus);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le statut de la commande a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }
        
        return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
    }
}