<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserAdminType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_USER')]
class UserAdminController extends AbstractController
{
    use AdminSecurityTrait;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $search = $request->query->get('search', '');
        $isAdmin = $request->query->get('isAdmin', '');

        $criteria = [];
        if ($isAdmin !== '') {
            $criteria['isAdmin'] = $isAdmin === '1';
        }

        if ($search) {
            $users = $this->userRepository->findBySearchCriteria($search, $criteria, $page, $limit);
            $totalUsers = $this->userRepository->countBySearchCriteria($search, $criteria);
        } else {
            $users = $this->userRepository->findBy($criteria, ['createdAt' => 'DESC'], $limit, ($page - 1) * $limit);
            $totalUsers = $this->userRepository->count($criteria);
        }

        $totalPages = ceil($totalUsers / $limit);

        $stats = [
            'total' => $this->userRepository->count([]),
            'admins' => $this->userRepository->count(['isAdmin' => true]),
            'verified' => $this->userRepository->count(['isVerified' => true]),
            'recent' => $this->userRepository->countRecentUsers(30), // Derniers 30 jours
        ];

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'search' => $search,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserAdminType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Définition des valeurs par défaut
            $user->setIsVerified(true); 
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès !');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserAdminType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe si modifié
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Mise à jour de la date de modification
            $user->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès !');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Vérifier qu'on ne supprime pas le dernier admin
            if ($user->isAdmin()) {
                $adminCount = $this->userRepository->count(['isAdmin' => true]);
                if ($adminCount <= 1) {
                    $this->addFlash('error', 'Impossible de supprimer le dernier administrateur !');
                    return $this->redirectToRoute('admin_user_index');
                }
            }

            // Vérifier qu'on ne se supprime pas soi-même
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
                return $this->redirectToRoute('admin_user_index');
            }

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/toggle-admin', name: 'admin_user_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('toggle-admin'.$user->getId(), $request->request->get('_token'))) {
            // Vérifier qu'on ne retire pas les droits au dernier admin
            if ($user->isAdmin()) {
                $adminCount = $this->userRepository->count(['isAdmin' => true]);
                if ($adminCount <= 1) {
                    $this->addFlash('error', 'Impossible de retirer les droits au dernier administrateur !');
                    return $this->redirectToRoute('admin_user_index');
                }
            }

            // Vérifier qu'on ne se retire pas les droits à soi-même
            if ($user === $this->getUser() && $user->isAdmin()) {
                $this->addFlash('error', 'Vous ne pouvez pas retirer vos propres droits d\'administrateur !');
                return $this->redirectToRoute('admin_user_index');
            }

            $user->setIsAdmin(!$user->isAdmin());
            $user->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $message = $user->isAdmin() ? 'Droits administrateur accordés' : 'Droits administrateur retirés';
            $this->addFlash('success', $message . ' avec succès !');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/toggle-verified', name: 'admin_user_toggle_verified', methods: ['POST'])]
    public function toggleVerified(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('toggle-verified'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsVerified(!$user->isVerified());
            $user->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $message = $user->isVerified() ? 'Compte vérifié' : 'Vérification du compte annulée';
            $this->addFlash('success', $message . ' avec succès !');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
