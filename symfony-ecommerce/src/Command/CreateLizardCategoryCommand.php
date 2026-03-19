<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\SubCategory;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-lizard-category',
    description: 'Crée la catégorie Lézards avec ses sous-catégories et produits'
)]
class CreateLizardCategoryCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(' Création de la catégorie Lézards pour Clochette&CO');

        // Vérifier si la catégorie existe déjà
        $existingCategory = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'Lézards']);
        if ($existingCategory) {
            $io->warning('La catégorie Lézards existe déjà !');
            return Command::SUCCESS;
        }

        // Créer la catégorie Lézards
        $category = new Category();
        $category->setName('Lézards');
        $category->setDescription('Tout pour vos reptiles et lézards');
        $category->setImage('category-lizard.png');

        $this->entityManager->persist($category);
        $io->success(' Catégorie "Lézards" créée');

        // Créer les sous-catégories
        $subcategories = [
            'Alimentation' => 'Nourriture vivante et suppléments pour reptiles',
            'Chauffage & UV' => 'Lampes UV, chauffage et thermostats',
            'Terrariums' => 'Terrariums et vivariums de toutes tailles',
            'Décoration' => 'Roches, plantes et décorations pour terrariums',
            'Soins' => 'Produits de soins et hygiène pour reptiles'
        ];

        $subCategoryEntities = [];
        foreach ($subcategories as $name => $description) {
            $subCategory = new SubCategory();
            $subCategory->setName($name);
            $subCategory->setDescription($description);
            $subCategory->setCategory($category);

            $this->entityManager->persist($subCategory);
            $subCategoryEntities[$name] = $subCategory;
            $io->writeln("  Sous-catégorie \"$name\" créée");
        }

        // Créer des produits pour chaque sous-catégorie
        $this->createProducts($subCategoryEntities, $io);

        // Sauvegarder tout
        $this->entityManager->flush();

        $io->newLine();
        $io->success('Catégorie Lézards créée avec succès avec toutes ses sous-catégories et produits !');
        $io->note('Vous pouvez maintenant tester les liens de navigation dans la navbar.');

        return Command::SUCCESS;
    }

    private function createProducts(array $subCategories, SymfonyStyle $io): void
    {
        $products = [
            'Alimentation' => [
                ['Grillons vivants (boîte de 50)', 'Grillons frais pour l\'alimentation de vos reptiles', 12.99],
                ['Vers de farine séchés 500g', 'Vers de farine riches en protéines', 8.50],
                ['Supplément calcium + D3', 'Complément alimentaire essentiel pour reptiles', 15.90],
                ['Granulés pour gecko léopard', 'Alimentation complète spécialement formulée', 18.75],
                ['Fruits séchés pour iguane', 'Mélange de fruits adaptés aux iguanes', 11.20]
            ],
            'Chauffage & UV' => [
                ['Lampe UV 10.0 25W', 'Lampe UVB essentielle pour la synthèse de vitamine D3', 45.90],
                ['Tapis chauffant 20W', 'Chauffage par le sol pour terrarium', 32.50],
                ['Thermostat digital', 'Contrôle précis de la température', 89.99],
                ['Lampe chauffante céramique 100W', 'Chauffage infrarouge sans lumière', 28.75],
                ['Minuteur pour éclairage', 'Programmateur jour/nuit automatique', 25.60]
            ],
            'Terrariums' => [
                ['Terrarium verre 60x40x40cm', 'Terrarium en verre avec aération optimale', 159.99],
                ['Terrarium grillagé 80x50x50cm', 'Parfait pour caméléons et reptiles arboricoles', 189.50],
                ['Kit terrarium débutant', 'Tout le nécessaire pour commencer', 249.99],
                ['Terrarium corner 40x40x60cm', 'Modèle d\'angle optimisant l\'espace', 145.80],
                ['Grande serre tropicale 120x60x80cm', 'Pour reptiles de grande taille', 399.99]
            ],
            'Décoration' => [
                ['Rocher cache XL', 'Cachette naturelle en résine', 34.90],
                ['Branche de vigne naturelle', 'Décoration et perchoir naturel', 22.50],
                ['Plantes artificielles jungle', 'Set de 3 plantes tropicales', 19.75],
                ['Substrat fibre de coco 10L', 'Substrat naturel et absorbant', 14.30],
                ['Cascade avec pompe', 'Point d\'eau décoratif avec circulation', 78.90]
            ],
            'Soins' => [
                ['Spray hydratant pour reptiles', 'Maintient l\'humidité de la peau', 16.80],
                ['Désinfectant terrarium 500ml', 'Nettoyage et désinfection sécurisés', 12.45],
                ['Complément vitamines reptiles', 'Vitamines essentielles en gouttes', 21.90],
                ['Pommade anti-parasites', 'Traitement préventif naturel', 18.60],
                ['Kit de premiers secours reptile', 'Trousse de soins d\'urgence complète', 45.75]
            ]
        ];

        foreach ($products as $subcategoryName => $productList) {
            $subCategory = $subCategories[$subcategoryName];
            $io->writeln(" Création des produits pour \"$subcategoryName\":");

            foreach ($productList as [$name, $description, $price]) {
                $product = new Product();
                $product->setName($name);
                $product->setDescription($description);
                $product->setPrice($price);
                $product->setStock(rand(5, 50));
                $product->setWeight(rand(50, 2000) / 100); // Poids en kg
                $product->setIsPromo(rand(0, 10) === 0); // 10% de chance d'être en promo
                $product->setIsNew(rand(0, 5) === 0); // 20% de chance d'être nouveau
                $product->setSubCategory($subCategory);

                if ($product->isPromo()) {
                    $product->setPromoPercentage(rand(10, 30));
                }

                $this->entityManager->persist($product);
                $io->writeln(" $name - {$price}€");
            }
        }
    }
}
