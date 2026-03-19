<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\SubCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-categories',
    description: 'Create the basic categories and subcategories for the pet store',
)]
class CreateCategoriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Nettoyer les données existantes 
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\UserCart')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\GuestCart')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\SubCategory')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Category')->execute();
        
        $categoriesData = [
            'Chiens' => [
                'Os & Jouets',
                'Alimentation Chiens',
                'Accessoires Chiens',
                'Gamelles Chiens',
                'Vêtements Chiens',
                'Friandises Chiens',
                'Soins Chiens',
                'Hygiène Chiens',
            ],
            'Chats' => [
                'Litières & Bacs',
                'Herbe à chat',
                'Croquettes Chats',
                'Jouets Chats',
                'Arbres à chat',
            ],
            'Oiseaux' => [
                'Graines & Alimentation',
                'Cages & Volières',
                'Jouets & Accessoires',
                'Perchoirs',
                'Soins & Vitamines',
                'Nids & Reproduction',
                        ],
            'Lézards' => [
                'Alimentation Lézards',
                'Chauffage & UV',
                'Terrariums',
                'Décoration',
                'Soins Lézards',
            ]
        ];
        
        foreach ($categoriesData as $categoryName => $subCategories) {
            // Créer la catégorie principale
            $category = new Category();
            $category->setName($categoryName);
            $category->setDescription("Tout pour vos " . strtolower($categoryName));
            
            // Définir l'image selon la catégorie
            switch ($categoryName) {
                case 'Chiens':
                    $category->setImage('category-dog.png');
                    break;
                case 'Chats':
                    $category->setImage('category-cat.png');
                    break;
                case 'Oiseaux':
                    $category->setImage('category-bird.png');
                    break;
                case 'Lézards':
                    $category->setImage('category-lizard.png');
                    break;
            }
            
            $this->entityManager->persist($category);
            $output->writeln("✅ Created category: $categoryName");
            
            // Créer les sous-catégories
            foreach ($subCategories as $subCategoryName) {
                $subCategory = new SubCategory();
                $subCategory->setName($subCategoryName);
                $subCategory->setCategory($category);
                $subCategory->setDescription("Découvrez notre sélection de " . strtolower($subCategoryName));
                
                $this->entityManager->persist($subCategory);
                $output->writeln("  📁 Created subcategory: $subCategoryName");
            }
        }
        
        $this->entityManager->flush();
        $output->writeln('🎉 All categories and subcategories created successfully!');
        
        return Command::SUCCESS;
    }
}
