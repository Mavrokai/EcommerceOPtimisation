<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

final class HistoriqueController extends AbstractController
{
    #[Route('/historique', name: 'app_historique', methods: ['GET'])]
    public function historique(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $orders = $em->getRepository(Order::class)->findAllOrdersForUser($user);

        return $this->render('profile/historique.html.twig', [
            'orders' => $orders,
        ]);
    }
    #[Route('/historique/{id}', name: 'app_historique_detail', methods: ['GET'])]
    public function detail(int $id, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        // Vérifier que l'utilisateur a accès à cette commande 
        if ($order->getUser() !== $user && ($order->getGuestEmail() !== $user->getEmail() || $order->getUser() !== null)) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        return $this->render('profile/historique_detail.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/historique/{id}/facture', name: 'app_historique_facture', methods: ['GET'])]
    public function facture(int $id, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        // Vérifier si l'utilisateur connecté est propriétaire de la commande
        if ($user && $order->getUser() === $user) {
        } 
        elseif (!$order->getUser() && $this->isGuestOrderAccessible($order)) {
        }
        elseif ($user && $order->getGuestEmail() === $user->getEmail() && $order->getUser() === null) {
        }
        else {
            throw $this->createNotFoundException('Accès non autorisé à cette commande');
        }

        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('profile/facture.html.twig', [
            'order' => $order,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Retour de la réponse PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture-' . $order->getId() . '.pdf"'
            ]
        );
    }

    #[Route('/historique/link-guest-orders', name: 'app_historique_link_guest_orders', methods: ['POST'])]
    public function linkGuestOrders(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $linkedOrdersCount = $em->getRepository(Order::class)->linkGuestOrdersToUser($user);
        $em->flush();

        if ($linkedOrdersCount > 0) {
            $this->addFlash('success', sprintf(
                '%d commande(s) ont été associée(s) à votre compte.',
                $linkedOrdersCount
            ));
        } else {
            $this->addFlash('info', 'Aucune commande d\'invité avec votre email n\'a été trouvée.');
        }

        return $this->redirectToRoute('app_historique');
    }

    #[Route('/historique/{id}/cancel', name: 'app_historique_cancel', methods: ['POST'])]
    public function cancel(int $id, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        // Vérifier que l'utilisateur a accès à cette commande
        if ($order->getUser() !== $user && ($order->getGuestEmail() !== $user->getEmail() || $order->getUser() !== null)) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        // Vérifier que la commande peut être annulée en utilisant la méthode de l'entité
        if (!$order->canBeModified()) {
            $this->addFlash('error', 'Cette commande ne peut plus être annulée car elle a déjà été expédiée ou est dans un état final.');
            return $this->redirectToRoute('app_historique_detail', ['id' => $id]);
        }

        // Annuler la commande
        $order->setStatus('annulee');
        $order->setUpdatedAt(new \DateTime()); 
        $em->flush();

        $this->addFlash('success', 'Votre commande a été annulée avec succès.');
        return $this->redirectToRoute('app_historique_detail', ['id' => $id]);
    }

    private function isGuestOrderAccessible(Order $order): bool
    {
        $session = $this->container->get('request_stack')->getCurrentRequest()?->getSession();
        
        if (!$session) {
            return false;
        }
        $accessibleOrders = $session->get('accessible_guest_orders', []);
        
        return in_array($order->getId(), $accessibleOrders);
    }

}
