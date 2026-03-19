<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\SubCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-database',
    description: 'Synchronise la base de données avec les catégories et sous-catégories standard',
)]
class SyncDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronisation de la base de données');

        // Définir les catégories et sous-catégories standard
        $categoriesData = [
            'Chiens' => [
                'Laisses & Colliers',
                'Os & Jouets', 
                'Croquettes pour chiens',
                'Gamelles & Accessoires',
                'Vêtements pour chiens',
                'Soins & Hygiène',
                'Friandises'
            ],
            'Chats' => [
                'Litières & Bacs',
                'Herbe à chat',
                'Croquettes pour chats',
                'Jouets pour chats',
                'Arbres à chat',
                'Soins & Hygiène',
                'Friandises'
            ],
            'Oiseaux' => [
                'Graines & Alimentation',
                'Cages & Volières',
                'Jouets & Accessoires',
                'Perchoirs',
                'Soins & Vitamines',
                'Nids & Reproduction'
            ],
            'Lézards' => [
                'Alimentation',
                'Chauffage & UV',
                'Terrariums',
                'Décoration',
                'Soins'
            ]
        ];

        foreach ($categoriesData as $categoryName => $subCategories) {
            // Chercher ou créer la catégorie
            $category = $this->entityManager->getRepository(Category::class)
                ->findOneBy(['name' => $categoryName]);

            if (!$category) {
                $category = new Category();
                $category->setName($categoryName);
                $category->setCreatedAt(new \DateTime());
                $this->entityManager->persist($category);
                $io->info("Catégorie créée: {$categoryName}");
            } else {
                $io->comment("Catégorie existante: {$categoryName} (ID: {$category->getId()})");
            }

            // Créer les sous-catégories
            foreach ($subCategories as $subCategoryName) {
                $subCategory = $this->entityManager->getRepository(SubCategory::class)
                    ->findOneBy(['name' => $subCategoryName, 'category' => $category]);

                if (!$subCategory) {
                    $subCategory = new SubCategory();
                    $subCategory->setName($subCategoryName);
                    $subCategory->setCategory($category);
                    $subCategory->setCreatedAt(new \DateTime());
                    $this->entityManager->persist($subCategory);
                    $io->info("  └─ Sous-catégorie créée: {$subCategoryName}");
                } else {
                    $io->comment("  └─ Sous-catégorie existante: {$subCategoryName} (ID: {$subCategory->getId()})");
                }
            }
        }

        $this->entityManager->flush();

        $io->success('Synchronisation terminée !');

        // Afficher un récapitulatif des IDs
        $io->section('Récapitulatif des IDs');
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        
        foreach ($categories as $category) {
            $io->writeln("{$category->getName()} (ID: {$category->getId()})");
            foreach ($category->getSubCategories() as $subCategory) {
                $io->writeln("  └─ {$subCategory->getName()} (ID: {$subCategory->getId()})");
            }
        }

        return Command::SUCCESS;
    }
}
