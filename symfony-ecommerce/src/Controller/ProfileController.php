<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Address;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use App\Form\AddressType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Affiche et modifie les informations personnelles + mot de passe.
     */
    #[Route('/', name: 'app_profile')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe si modifié
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Mise à jour date de modification 
            if (method_exists($user, 'setUpdatedAt')) {
                $user->setUpdatedAt(new \DateTimeImmutable());
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/addresses', name: 'app_profile_addresses')]
    public function addresses(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/addresses.html.twig', [
            'addresses' => $user->getAddresses(),
        ]);
    }

    #[Route('/addresses/new', name: 'app_profile_address_new')]
    public function newAddress(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = new Address();
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $address->setUser($user);
            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'Adresse ajoutée avec succès !');
            return $this->redirectToRoute('app_profile_addresses');
        }

        return $this->render('profile/newAddress.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}