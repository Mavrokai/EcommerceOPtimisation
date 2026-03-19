<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-commandes')]
#[IsGranted('ROLE_USER')]
class OrderHistoryController extends AbstractController
{
    #[Route('/', name: 'order_history_index')]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les commandes de l'utilisateur connecté
        $orders = $orderRepository->findBy(
            ['user' => $user], 
            ['createdAt' => 'DESC']
        );

        return $this->render('order_history/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'order_history_show', requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        $user = $this->getUser();
        
        // Vérifier que la commande appartient bien à l'utilisateur connecté
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        return $this->render('order_history/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/invoice', name: 'order_history_invoice', requirements: ['id' => '\d+'])]
    public function invoice(Order $order): Response
    {
        $user = $this->getUser();
        
        // Vérifier que la commande appartient bien à l'utilisateur connecté
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        return $this->render('order_history/invoice.html.twig', [
            'order' => $order,
        ]);
    }
}
