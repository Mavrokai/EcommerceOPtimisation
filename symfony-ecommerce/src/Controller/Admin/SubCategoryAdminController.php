<?php

namespace App\Controller\Admin;

use App\Entity\SubCategory;
use App\Form\SubCategoryType;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/sub-categories')]
class SubCategoryAdminController extends AbstractController
{
    public function __construct(
        private SubCategoryRepository $subCategoryRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'admin_subcategory_index', methods: ['GET'])]
    public function index(): Response
    {
        $subCategories = $this->subCategoryRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/subcategory/index.html.twig', [
            'subCategories' => $subCategories,
        ]);
    }

    #[Route('/new', name: 'admin_subcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $subCategory = new SubCategory();
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($subCategory);
            $this->entityManager->flush();

            $this->addFlash('success', 'Sous-catégorie créée avec succès !');

            return $this->redirectToRoute('admin_subcategory_index');
        }

        return $this->render('admin/subcategory/new.html.twig', [
            'subCategory' => $subCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_subcategory_show', methods: ['GET'])]
    public function show(SubCategory $subCategory): Response
    {
        return $this->render('admin/subcategory/show.html.twig', [
            'subCategory' => $subCategory,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SubCategory $subCategory): Response
    {
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Sous-catégorie modifiée avec succès !');

            return $this->redirectToRoute('admin_subcategory_index');
        }

        return $this->render('admin/subcategory/edit.html.twig', [
            'subCategory' => $subCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_subcategory_delete', methods: ['POST'])]
    public function delete(Request $request, SubCategory $subCategory): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subCategory->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($subCategory);
            $this->entityManager->flush();

            $this->addFlash('success', 'Sous-catégorie supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_subcategory_index');
    }
}
