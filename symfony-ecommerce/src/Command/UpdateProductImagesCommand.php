<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-product-images',
    description: 'Update existing products with your images',
)]
class UpdateProductImagesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Updating product images with your existing images...');

        // Récupérer tous les produits existants
        $products = $this->productRepository->findAll();
        
        if (empty($products)) {
            $output->writeln('❌ No products found! Please run app:create-categories and app:create-products first.');
            return Command::FAILURE;
        }

        $imageMapping = $this->getImageMapping();
        $updatedCount = 0;

        foreach ($products as $product) {
            $productName = $product->getName();
            
            // Chercher une image correspondante
            $newImage = $this->findImageForProduct($productName, $imageMapping);
            
            if ($newImage && $newImage !== $product->getImageUrl()) {
                $product->setImageUrl($newImage);
                $output->writeln("Updated: {$productName} -> {$newImage}");
                $updatedCount++;
            } else {
                $output->writeln(" No change: {$productName}");
            }
        }

        $this->entityManager->flush();
        
        $output->writeln("\n Successfully updated {$updatedCount} product images!");
        $output->writeln("Make sure your images are in public/assets/images/");
        
        return Command::SUCCESS;
    }

    private function getImageMapping(): array
    {
        // Mapping basé sur les images que vous avez dans votre dossier
        return [
            'Laisse Biothane Super Grip' => 'laisse-biothane-super-grip--removebg-preview.png',
            'Collier anti-puces naturel' => 'Collier-anti-puces-naturel-removebg-preview-68a6fb6c4e9f8.png',
            'Laisse rétractable 5m' => 'Laisse-retractable-5m-removebg-preview-68a6fc3a7c267.png',
            'Collier lumineux LED' => 'Collier-lumineux-LED-removebg-preview-68a70f09b1504.png',
            'Harnais de sécurité' => 'Harnais-de-securite-removebg-preview-68a6fc519458b.png',
            
            'Os à mâcher naturel' => 'Os-a-macher-naturel-removebg-preview-68a70c56c49c4.png',
            'Balle rebondissante' => 'Balle-rebondissante-removebg-preview-68a6fb25ca090.png',
            'Corde à nœuds' => 'Corde-a-noeuds-removebg-preview-68a6fb4b507de.png',
            'Kong Classic' => 'Kong-Classic-removebg-preview-68a70c2501e55.png',
            'Frisbee pour chien' => 'Frisbee-pour-chien-removebg-preview-68a70c459095b.png',
            
            'Croquettes Premium Adult' => 'Croquettes-Premium-Adult-removebg-preview-68a70cfd3f1d9.png',
            'Croquettes Puppy' => 'Croquettes-Puppy-removebg-preview-68a70c7acd02b.png',
            'Croquettes Senior 7+' => 'Croquettes-Senior-7-removebg-preview-68a70d125e2eb.png',
            'Croquettes Sans Céréales' => 'Croquettes-Sans-Cereales-removebg-preview-68a70d26f25ba.png',
            'Croquettes Grandes Races' => 'Croquettes-Grandes-Races-removebg-preview-68a70ce280de0.png',
            
            'Gamelle inox double' => 'Gamelle-inox-double-removebg-preview-68a70d3acc082.png',
            'Fontaine à eau automatique' => 'Fontaine-a-eau-automatique-removebg-preview-68a70e01ee523.png',
            'Tapis de gamelle antidérapant' => 'Tapis-de-gamelle-antiderapant-removebg-preview-68a70d5762171.png',
            'Gamelle surélevée ajustable' => 'Gamelle-surelevee-ajustable-removebg-preview-68a70d686137f.png',
            'Distributeur de croquettes' => 'Distributeur-de-croquettes-removebg-preview-68a70f178a493.png',
            
            'Manteau d\'hiver imperméable' => 'Manteau-d-hiver-impermeable-removebg-preview-68a70e38ce4eb.png',
            'Pull en laine douce' => 'Pull-en-laine-douce-removebg-preview-68a70da853154.png',
            'Imperméable transparent' => 'Impermeable-transparent-removebg-preview-68a6fb8041502.png',
            'T-shirt été respirant' => 'T-shirt-ete-respirant-removebg-preview-68a70de57278d.png',
            'Chaussons de protection' => 'Chaussons-de-protection-removebg-preview-68a6fb5a3d8e3.png',
            
            'Shampoing hypoallergénique' => 'Shampoing-hypoallergenique-removebg-preview-68a6fb0106e5b.png',
            'Brosse démêlante' => 'Brosse-demelante-removebg-preview-68a70db5aa856.png',
            'Dentifrice enzymatique' => 'Dentifrice-enzymatique-removebg-preview-68a70e2ae4d9f.png',
            'Lingettes nettoyantes' => 'Lingettes-nettoyantes-removebg-preview-68a6fade70eb6.png',
            'Coupe-griffes professionnel' => 'Coupe-griffes-professionnel-removebg-preview-68a70e419b8c5.png',
            
            'Biscuits au saumon' => 'Biscuits-au-saumon-removebg-preview-68a70c9b7f621.png',
            'Friandises pour chien' => 'friandise_pour_chien-removebg-preview.png',
            'Lamelles de canard séchées' => 'Lamelles-de-canard-sechees-removebg-preview-68a6fc0c8db7c.png',
            'Bâtonnets au bœuf' => 'Batonnets-au-boeuf-removebg-preview-68a6fb11395e6.png',
            'Mix training treats' => 'Mix-training-treats-removebg-preview-68a6fb35d2af5.png',
            
            // Images par défaut pour les catégories
            'category-dog' => 'category-dog.png',
            'category-cat' => 'category-cat.png',
            'category-bird' => 'category-bird.png',
            'default' => 'hero-image.png',
        ];
    }

    private function findImageForProduct(string $productName, array $imageMapping): ?string
    {
        // Recherche exacte
        if (isset($imageMapping[$productName])) {
            return $imageMapping[$productName];
        }

        // Recherche par mots-clés
        $productLower = strtolower($productName);
        
        foreach ($imageMapping as $key => $image) {
            $keyLower = strtolower($key);
            
            // Si le nom du produit contient des mots-clés de l'image
            if (str_contains($productLower, $keyLower) || str_contains($keyLower, $productLower)) {
                return $image;
            }
        }

        // Images par défaut selon le type de produit
        if (str_contains($productLower, 'chat') || str_contains($productLower, 'litière') || str_contains($productLower, 'herbe')) {
            return 'category-cat.png';
        }
        
        if (str_contains($productLower, 'oiseau') || str_contains($productLower, 'graine') || str_contains($productLower, 'cage') || str_contains($productLower, 'perchoir')) {
            return 'category-bird.png';
        }
        
        if (str_contains($productLower, 'chien') || str_contains($productLower, 'croquette') || str_contains($productLower, 'laisse')) {
            return 'category-dog.png';
        }

        // Image par défaut
        return 'hero-image.png';
    }
}
