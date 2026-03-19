<?php

namespace App\Controller;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommandsController extends AbstractController
{
    private const TVA_RATE = 0.20; // 20% de TVA
    #[Route('/command', name: 'app_command')]
    public function index(): Response
    {
        return $this->render('cart/payment.html.twig', [
            'controller_name' => 'CommandController',
        ]);
    }
 #[Route('/command/create', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $em, SessionInterface $session): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $order = new Order();
    
    // Gestion de l'utilisateur connecté ou invité
    $currentUser = $this->getUser();
    if ($currentUser) {
        $order->setUser($currentUser);
        $order->setGuestEmail(null);
    } else {
        // Utilisateur invité
        $guestEmail = $data['guest_email'] ?? null;
        
        if ($guestEmail) {
            $existingUser = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $guestEmail]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'Cette adresse email est déjà associée à un compte utilisateur. Veuillez vous connecter ou utiliser une autre adresse email.'
                ], 400);
            }
        }
        
        $order->setUser(null);
        $order->setGuestEmail($guestEmail);
    }
    
    $order->setStatus($data['status'] ?? 'en_attente');
    $order->setCreatedAt(new \DateTime());
    $order->setUpdatedAt(new \DateTime());
    $order->setTotal($data['total'] ?? 0);
    $order->setDeliveryAddress($data['delivery_address'] ?? null);

    $em->persist($order);

    // ADD orderItems to order table(new table)
    if (!empty($data['products'])) {
        foreach ($data['products'] as $item) {
            $product = $em->getRepository(Product::class)->find($item['product_id']);

            if (!$product) {
                return $this->json(['error' => 'Product not found: ID ' . $item['product_id']], 400);
            }

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice($item['unit_price']); 
            
            $orderItem->saveProductInfo();

            if (!empty($item['variant_id'])) {
                $variant = $em->getRepository(ProductVariant::class)->find($item['variant_id']);
                if ($variant) {
                    $orderItem->setVariant($variant);
                }
            }

            $em->persist($orderItem);
        }
    }

    $em->flush();
    
    if (!$order->getUser()) {
        $accessibleOrders = $session->get('accessible_guest_orders', []);
        $accessibleOrders[] = $order->getId();
        $session->set('accessible_guest_orders', $accessibleOrders);
    }
    
    // Vider le panier après la commande réussie
    $session->remove('cart');

    return $this->json([
        'message' => 'Order and items created successfully',
        'orderId' => $order->getId()
    ], 201);
}

    #[Route('/command/{id}', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
            }
            return $this->json($order, 200);
            }
    #[Route('/command/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $order = $em->getRepository(Order::class)->find($id);
        $em->flush();
        return $this->json(['message' => 'Order updated successfully'], 200);
    }
    #[Route('/command/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
            }
        $em->remove($order);
        $em->flush();
        return $this->json(['message' => 'Order deleted successfully'], 200);
}
#[Route('/confirmation/{id}', name: 'app_confirmation')]
public function confirmation(int $id, EntityManagerInterface $em): Response
{
    $order = $em->getRepository(Order::class)->find($id);
    
    if (!$order) {
        throw $this->createNotFoundException('Order not found');
    }
    
    // Calculs TVA pour la confirmation
    $totalTTC = $order->getTotal();
    $totalHT = $totalTTC / (1 + self::TVA_RATE);
    $montantTVA = $totalTTC - $totalHT;
    
    return $this->render('cart/confirmation.html.twig', [
        'order' => $order,
        'totalHT' => $totalHT,
        'montantTVA' => $montantTVA,
        'tauxTVA' => self::TVA_RATE * 100,
    ]);
}
}