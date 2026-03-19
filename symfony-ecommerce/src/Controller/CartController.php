<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use App\Service\ShippingCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/cart')]
class CartController extends AbstractController
{
    private const TVA_RATE = 0.20; 
    #[Route('', name : 'app_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository, ShippingCalculator $shippingCalculator): Response
    {
        $cart = $session->get('cart', []);
        $cartItems = [];
        $totalPanier = 0;
        $totalWeight = 0;

        $allProducts = $productRepository->findAll();
        foreach ($cart as $productId => $quantity)
        {
            $product = $productRepository->find($productId);
            $totalLigne = $product->getPrice() * $quantity;

            if($product) {
                $prixUnitaire = $product->getPromotionalPrice() ?? $product->getPrice();
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'prix_unitaire' => $prixUnitaire,
                    'total_ligne' => $totalLigne
                ];

                $totalPanier += $totalLigne;
                
                                if (method_exists($product, 'getWeight')) {
                    $totalWeight += $product->getWeight() * $quantity;
                }
            }
        }

        $fraisLivraison = 5;
        if (!empty($cart)) {            $allTransporters = $shippingCalculator->getAllTransporters();
            $defaultTransporter = null;
            
            // Chercher PrestaColis en premier 
            foreach ($allTransporters as $transporter) {
                if (strpos($transporter['name'], 'PrestaColis') !== false && $totalWeight <= $transporter['maxWeight']) {
                    $defaultTransporter = $transporter;
                    break;
                }
            }
            
            if (!$defaultTransporter && !empty($allTransporters)) {
                $defaultTransporter = $allTransporters[0];
            }
            
            $fraisLivraison = $defaultTransporter['price'] ?? 5.0;
            
            if ($totalPanier > 200) {
                $fraisLivraison = 0;
            }
        }

        $totalFinal = $totalPanier + $fraisLivraison;
        $totalHT = $totalFinal / (1 + self::TVA_RATE);
        $montantTVA = $totalFinal - $totalHT;        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'totalPanier' => $totalPanier, 
            'totalFinal' => $totalFinal,
            'totalHT' => $totalHT, 
            'montantTVA' => $montantTVA, 
            'tauxTVA' => self::TVA_RATE * 100,
            'fraisLivraison' => $fraisLivraison,
            'allProducts' => $allProducts,
            'debug' => 'Produits Trouvés : ' . count($cartItems) . ' | Total : ' . $totalPanier . '€'
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, SessionInterface $session): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity < 1) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Quantité invalide'], 400);
            }
            return $this->redirectToRoute('app_cart');
        }

        $cart = $session->get('cart', []);
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
            $action = 'mis à jour dans';
        } else {
            $cart[$productId] = $quantity;
            $action = 'ajouté au';
        }

        $session->set('cart', $cart);
        
        // Si c'est une requête AJAX, retourner JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true, 
                'message' => sprintf('"%s" %s panier', $product->getName(), $action),
                'cartCount' => array_sum($cart)
            ]);
        }
        
        $referer = $request->headers->get('referer');
        if ($referer && !str_contains($referer, '/cart')) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'app_cart_clear')]
    public function clearTest(SessionInterface $session): Response
    {
        $session->remove('cart');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Product $product, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $productId = $product->getId();
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $session->set('cart', $cart);
        } 
        
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/update-quantity/{id}', name: 'app_cart_update_quantity', methods: ['POST'])]
    public function updateQuantity(Product $product, Request $request, SessionInterface $session): Response
    {
        $newQuantity = (int) $request->request->get('quantity', 1);
        $cart = $session->get('cart', []);
        $productId = $product->getId();
        
        if ($newQuantity <= 0) {
            if (isset($cart[$productId])) {
                unset($cart[$productId]);
            }
        } else {
            $cart[$productId] = $newQuantity;
        }
        
        $session->set('cart', $cart);
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/checkout', name: 'app_cart_checkout')]
    public function checkout(Request $request, SessionInterface $session, ProductRepository $productRepository, \App\Service\ShippingCalculator $shippingCalculator): Response
    {
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }
        $cartItems = [];
        $totalPanier = 0;
        $totalWeight = 0;
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $prixUnitaire = $product->getPromotionalPrice() ?? $product->getPrice();
                $totalLigne = $prixUnitaire * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'prix_unitaire' => $prixUnitaire,
                    'total_ligne' => $totalLigne
                ];
                $totalPanier += $totalLigne;
                if (method_exists($product, 'getWeight')) {
                    $totalWeight += $product->getWeight() * $quantity;
                }
            }
        }
        $totalHT = $totalPanier / (1 + self::TVA_RATE); 
        $montantTVA = $totalPanier - $totalHT; 

        // all transporters
        $allTransporters = $shippingCalculator->getAllTransporters();
        $prestaColis = null;
        foreach ($allTransporters as $t) {
            if (strpos($t['name'], 'PrestaColis') !== false && $totalWeight <= $t['maxWeight']) {
                $prestaColis = $t;
                break;
            }
        }

        $otherTransporters = array_filter($allTransporters, function($t) {
            return strpos($t['name'], 'PrestaColis') === false;
        });
        $displayTransporters = [];
        if ($prestaColis) {
            $displayTransporters[] = $prestaColis;
        }
        $displayTransporters = array_merge($displayTransporters, $otherTransporters);

        $shipping = $prestaColis ?? $otherTransporters[0] ?? null;
        $fraisLivraison = $shipping['price'] ?? 0;
        $transporteur = $shipping['name'] ?? 'Aucun disponible';
        $giftWrapPrice = 2.50;
        $giftWrapSelected = $request->request->get('gift_wrap');

        if ($totalPanier > 200) {
            $fraisLivraison = 0;
        }

        $totalFinal = $totalPanier + $fraisLivraison;
        if ($giftWrapSelected) {
            $totalFinal += $giftWrapPrice;
        }

        $totalProduitsHT = $totalPanier / (1 + self::TVA_RATE);
        $totalFinalHT = $totalFinal / (1 + self::TVA_RATE);
        $montantTVAFinal = $totalFinal - $totalFinalHT;

        return $this->render('cart/payment.html.twig', [
            'cartItems' => $cartItems,
            'totalPanier' => $totalPanier, 
            'totalProduitsHT' => $totalProduitsHT,
            'totalHT' => $totalFinalHT, 
            'montantTVA' => $montantTVAFinal, 
            'tauxTVA' => self::TVA_RATE * 100, 
            'fraisLivraison' => $fraisLivraison,
            'transporteur' => $transporteur,
            'giftWrapSelected' => $giftWrapSelected,
            'giftWrapPrice' => $giftWrapPrice,
            'totalFinal' => $totalFinal,
            'transporteurs' => $displayTransporters,
        ]);
    }
    
    #[Route('/cart-count', name: 'app_cart_count', methods: ['GET'])]
    public function getCartCount(SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);
        $totalItems = 0;
        
        foreach ($cart as $quantity) {
            $totalItems += $quantity;
        }
        
        return new JsonResponse(['count' => $totalItems]);
    }
    
    #[Route('/checkout/process', name: 'app_cart_process', methods: ['POST'])]
    public function processCheckout(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }
        
        // Récupération des données du formulaire
        $orderData = [
            'email' => $request->get('email'),
            'prenom' => $request->get('prenom'),
            'nom' => $request->get('nom'),
            'telephone' => $request->get('telephone'),
            'delivery' => $request->get('delivery'),
            'payment' => $request->get('payment'),
        ];
        
        // Vérification pour les invités
        $currentUser = $this->getUser();
        if (!$currentUser && !empty($orderData['email'])) {
            $existingUser = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $orderData['email']]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà associée à un compte utilisateur. Veuillez vous connecter ou utiliser une autre adresse email.');
                return $this->redirectToRoute('app_cart_checkout');
            }
        }
        
        $session->set('order_data', $orderData);
        $session->set('order_cart', $cart);
        
        // Rediriger vers la page de paiement
        return $this->redirectToRoute('app_payment_page');
    }
    
    #[Route('/payment', name: 'app_payment_page')]
    public function paymentPage(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $orderData = $session->get('order_data');
        $cart = $session->get('order_cart');
        
        if (!$orderData || !$cart) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer votre commande.');
            return $this->redirectToRoute('app_cart');
        }
        
        $cartItems = [];
        $totalPanier = 0;
        
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $prixUnitaire = $product->getPromotionalPrice() ?? $product->getPrice();
                $totalLigne = $prixUnitaire * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'prix_unitaire' => $prixUnitaire,
                    'total_ligne' => $totalLigne
                ];
                $totalPanier += $totalLigne;
            }
        }
        
        $fraisLivraison = 5;
        
        if (isset($orderData['delivery'])) {
            $shippingCalculator = new \App\Service\ShippingCalculator();
            $allTransporters = $shippingCalculator->getAllTransporters();
            
            foreach ($allTransporters as $transporter) {
                if ($transporter['name'] === $orderData['delivery']) {
                    $fraisLivraison = $transporter['price'];
                    break;
                }
            }
        }

        if ($totalPanier > 200) {
            $fraisLivraison = 0;
        }

        $totalFinal = $totalPanier + $fraisLivraison;

        $totalHT = $totalFinal / (1 + self::TVA_RATE);
        $montantTVA = $totalFinal - $totalHT;        return $this->render('cart/payment_final.html.twig', [
            'orderData' => $orderData,
            'cartItems' => $cartItems,
            'totalPanier' => $totalPanier,
            'fraisLivraison' => $fraisLivraison,
            'totalFinal' => $totalFinal,
            'totalHT' => $totalHT,
            'montantTVA' => $montantTVA,
        ]);
    }
    
    #[Route('/payment/confirm', name: 'app_payment_confirm', methods: ['POST'])]
    public function confirmPayment(SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $orderData = $session->get('order_data');
        $cart = $session->get('order_cart');
        
        if (!$orderData || !$cart) {
            $this->addFlash('error', 'Session expirée.');
            return $this->redirectToRoute('app_cart');
        }
        
        // Vérification supplémentaire pour les invités
        $currentUser = $this->getUser();
        if (!$currentUser && !empty($orderData['email'])) {
            $existingUser = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $orderData['email']]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà associée à un compte utilisateur. Veuillez vous connecter ou utiliser une autre adresse email.');
                return $this->redirectToRoute('app_cart_checkout');
            }
        }
        
        // Créer une vraie commande en base de données
        $order = new Order();
        $order->setGuestEmail($orderData['email']);
        $order->setStatus('confirmee');
        $order->setDeliveryAddress($orderData['prenom'] . ' ' . $orderData['nom']);
        $order->setCreatedAt(new \DateTime());
        
        if ($this->getUser()) {
            $order->setUser($this->getUser());
        }
        
        $totalPanier = 0;
        
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $prixUnitaire = $product->getPromotionalPrice() ?? $product->getPrice();
                $totalLigne = $prixUnitaire * $quantity;
                $totalPanier += $totalLigne;
                
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setUnitPrice($prixUnitaire);
                $orderItem->setOrder($order);
                $orderItem->setProductName($product->getName());
                
                $order->addOrderItem($orderItem);
            }
        }
        
        $order->setTotal($totalPanier);
        
        $em->persist($order);
        $em->flush();
        
        $cartItems = [];
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $prixUnitaire = $product->getPromotionalPrice() ?? $product->getPrice();
                $totalLigne = $prixUnitaire * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'prix_unitaire' => $prixUnitaire,
                    'total_ligne' => $totalLigne
                ];
            }
        }
        
        $fraisLivraison = 0;
        if (isset($orderData['delivery']) && $totalPanier < 200) {
            $shippingCalculator = new ShippingCalculator();
            $transporters = $shippingCalculator->getAllTransporters();
            
            foreach ($transporters as $transporter) {
                if ($transporter['name'] === $orderData['delivery']) {
                    $fraisLivraison = $transporter['price'];
                    break;
                }
            }
        }

        $totalFinal = $totalPanier + $fraisLivraison;

        $totalHT = $totalFinal / (1 + self::TVA_RATE);
        $montantTVA = $totalFinal - $totalHT;
        
        $this->addFlash('success', 'Votre commande a été validée et payée avec succès !');
        
        $session->set('confirmation_data', [
            'orderData' => $orderData,
            'cartItems' => $cartItems,
            'totalPanier' => $totalPanier,
            'totalFinal' => $totalFinal,
            'fraisLivraison' => $fraisLivraison,
            'totalHT' => $totalHT,
            'montantTVA' => $montantTVA,
            'orderId' => $order->getId(),
        ]);
        
        $accessibleOrders = $session->get('accessible_guest_orders', []);
        $accessibleOrders[] = $order->getId();
        $session->set('accessible_guest_orders', $accessibleOrders);
        
        $session->remove('cart');
        $session->remove('order_data');
        $session->remove('order_cart');
        
        return $this->redirectToRoute('app_cart_confirmation');
    }
    
    #[Route('/confirmation', name: 'app_cart_confirmation')]
    public function confirmation(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $confirmationData = $session->get('confirmation_data');
        
        if (!$confirmationData) {
            $this->addFlash('error', 'Aucune commande récente trouvée.');
            return $this->redirectToRoute('homepage');
        }
        
        $order = null;
        if (isset($confirmationData['orderId'])) {
            $order = $em->getRepository(Order::class)->find($confirmationData['orderId']);
        }
        
        $session->remove('confirmation_data');
        
        return $this->render('cart/confirmation.html.twig', [
            'order' => $order,
            'cartItems' => $confirmationData['cartItems'],
            'totalPanier' => $confirmationData['totalPanier'],
            'totalFinal' => $confirmationData['totalFinal'] ?? $confirmationData['totalPanier'],
            'fraisLivraison' => $confirmationData['fraisLivraison'] ?? 0,
            'totalHT' => $confirmationData['totalHT'],
            'montantTVA' => $confirmationData['montantTVA'],
            'tauxTVA' => self::TVA_RATE * 100,
        ]);
    }
}
