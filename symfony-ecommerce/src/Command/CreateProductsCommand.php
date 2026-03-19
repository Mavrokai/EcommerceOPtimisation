<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-products',
    description: 'Create 5 products for each subcategory in the pet store (structure authentique des camarades)',
)]
class CreateProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubCategoryRepository $subCategoryRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Nettoyer les produits existants et leurs références
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\UserCart')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\GuestCart')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $output->writeln('Cleaned existing products and related data');

        // Récupérer toutes les sous-catégories
        $subCategories = $this->subCategoryRepository->findAll();
        
        if (empty($subCategories)) {
            $output->writeln('No subcategories found! Please run app:create-categories first.');
            return Command::FAILURE;
        }

        $totalProducts = 0;

        foreach ($subCategories as $subCategory) {
            $output->writeln("\n Creating products for: " . $subCategory->getName());
            
            $products = $this->getProductsForSubCategory($subCategory->getName());
            
            foreach ($products as $productData) {
                $product = new Product();
                $product->setName($productData['name']);
                $product->setDescription($productData['description']);
                $product->setPrice($productData['price']);
                $product->setImageUrl($productData['image']);
                $product->setStock($productData['stock']);
                $product->setWeight($productData['weight']);
                $product->setIsPromo($productData['isPromo']);
                $product->setIsNew($productData['isNew']);
                $product->setSubCategory($subCategory);

                $this->entityManager->persist($product);
                $output->writeln("  DONE :" . $productData['name'] . " - " . $productData['price'] . "€");
                $totalProducts++;
            }
        }

        $this->entityManager->flush();
        $output->writeln("\n🎉 Successfully created $totalProducts products across " . count($subCategories) . " subcategories!");
        
        return Command::SUCCESS;
    }

    private function getProductsForSubCategory(string $subCategoryName): array
    {
        // Tous les produits authentiques seront définis dans cette méthode
        return $this->getAllProducts()[$subCategoryName] ?? [];
    }

    private function getAllProducts(): array
    {
        return [
            // CHIENS (8 sous-catégories) - Structure authentique des camarades
            'Os & Jouets' => [
                ['name' => 'Os à mâcher naturel', 'description' => 'Os de bœuf naturel pour l\'hygiène dentaire', 'price' => '12.99', 'image' => 'Os-a-macher-naturel-removebg-preview-68a70c56c49c4.png', 'stock' => 100, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Balle rebondissante', 'description' => 'Balle en caoutchouc ultra-résistant', 'price' => '8.99', 'image' => 'Balle-rebondissante-removebg-preview-68a6fb25ca090.png', 'stock' => 80, 'weight' => '0.1', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Kong Classic', 'description' => 'Jouet Kong rouge ultra-résistant', 'price' => '16.99', 'image' => 'Kong-Classic-removebg-preview-68a70c2501e55.png', 'stock' => 45, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Corde à nœuds', 'description' => 'Jouet en corde naturelle pour tirer et mâcher', 'price' => '9.99', 'image' => 'Corde-a-noeuds-removebg-preview-68a6fb4b507de.png', 'stock' => 60, 'weight' => '0.15', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Frisbee pour chien', 'description' => 'Disque volant souple et sécurisé', 'price' => '11.99', 'image' => 'Frisbee-pour-chien-removebg-preview-68a70c459095b.png', 'stock' => 35, 'weight' => '0.12', 'isPromo' => true, 'isNew' => false],
            ],
            'Alimentation Chiens' => [
                ['name' => 'Croquettes Premium Adult', 'description' => 'Alimentation complète pour chiens adultes', 'price' => '45.99', 'image' => 'Croquettes-Premium-Adult-removebg-preview-68a70cfd3f1d9.png', 'stock' => 20, 'weight' => '12.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Croquettes Puppy', 'description' => 'Nutrition spéciale pour chiots en croissance', 'price' => '39.99', 'image' => 'Croquettes-Puppy-removebg-preview-68a70c7acd02b.png', 'stock' => 25, 'weight' => '10.0', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Croquettes Senior 7+', 'description' => 'Formule adaptée aux chiens âgés', 'price' => '48.99', 'image' => 'Croquettes-Senior-7-removebg-preview-68a70d125e2eb.png', 'stock' => 18, 'weight' => '12.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Croquettes Sans Céréales', 'description' => 'Alimentation hypoallergénique grain-free', 'price' => '52.99', 'image' => 'Croquettes-Sans-Cereales-removebg-preview-68a70d26f25ba.png', 'stock' => 15, 'weight' => '11.0', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Croquettes Grandes Races', 'description' => 'Spécialement formulées pour grands chiens', 'price' => '49.99', 'image' => 'Croquettes-Grandes-Races-removebg-preview-68a70ce280de0.png', 'stock' => 22, 'weight' => '15.0', 'isPromo' => true, 'isNew' => false],
            ],
            'Accessoires Chiens' => [
                ['name' => 'Laisse Biothane Super Grip', 'description' => 'Laisse Biothane ultra-résistante avec grip renforcé', 'price' => '29.99', 'image' => 'laisse-biothane-super-grip--removebg-preview.png', 'stock' => 50, 'weight' => '0.3', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Collier anti-puces naturel', 'description' => 'Protection naturelle contre les puces et tiques', 'price' => '15.99', 'image' => 'Collier-anti-puces-naturel-removebg-preview-68a6fb6c4e9f8.png', 'stock' => 75, 'weight' => '0.1', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Laisse rétractable 5m', 'description' => 'Laisse automatique avec freinage sécurisé', 'price' => '24.99', 'image' => 'Laisse-retractable-5m-removebg-preview-68a6fc3a7c267.png', 'stock' => 30, 'weight' => '0.4', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Collier lumineux LED', 'description' => 'Collier avec éclairage LED pour sorties nocturnes', 'price' => '19.99', 'image' => 'Collier-lumineux-LED-removebg-preview-68a70f09b1504.png', 'stock' => 40, 'weight' => '0.15', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Harnais de sécurité', 'description' => 'Harnais ergonomique avec réflecteurs', 'price' => '34.99', 'image' => 'Harnais-de-securite-removebg-preview-68a6fc519458b.png', 'stock' => 25, 'weight' => '0.5', 'isPromo' => true, 'isNew' => false],
            ],
            'Gamelles Chiens' => [
                ['name' => 'Gamelle inox double', 'description' => 'Set de 2 gamelles en acier inoxydable', 'price' => '18.99', 'image' => 'Gamelle-inox-double-removebg-preview-68a70d3acc082.png', 'stock' => 40, 'weight' => '0.8', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Fontaine à eau automatique', 'description' => 'Distributeur d\'eau avec filtration', 'price' => '35.99', 'image' => 'Fontaine-a-eau-automatique-removebg-preview-68a70e01ee523.png', 'stock' => 20, 'weight' => '2.5', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Tapis de gamelle antidérapant', 'description' => 'Tapis en silicone pour protéger le sol', 'price' => '12.99', 'image' => 'Tapis-de-gamelle-antiderapant-removebg-preview-68a70d5762171.png', 'stock' => 50, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Gamelle surélevée ajustable', 'description' => 'Support ergonomique à hauteur réglable', 'price' => '42.99', 'image' => 'Gamelle-surelevee-ajustable-removebg-preview-68a70d686137f.png', 'stock' => 15, 'weight' => '3.0', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Distributeur de croquettes', 'description' => 'Réservoir automatique programmable', 'price' => '89.99', 'image' => 'Distributeur-de-croquettes-removebg-preview-68a70f178a493.png', 'stock' => 8, 'weight' => '4.5', 'isPromo' => true, 'isNew' => false],
            ],
            'Vêtements Chiens' => [
                ['name' => 'Manteau d\'hiver imperméable', 'description' => 'Protection chaude et étanche', 'price' => '32.99', 'image' => 'Manteau-d-hiver-impermeable-removebg-preview-68a70e38ce4eb.png', 'stock' => 30, 'weight' => '0.4', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Pull en laine douce', 'description' => 'Confort et chaleur pour petits chiens', 'price' => '19.99', 'image' => 'Pull-en-laine-douce-removebg-preview-68a70da853154.png', 'stock' => 45, 'weight' => '0.2', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Imperméable transparent', 'description' => 'Protection contre la pluie légère', 'price' => '15.99', 'image' => 'Impermeable-transparent-removebg-preview-68a6fb8041502.png', 'stock' => 35, 'weight' => '0.15', 'isPromo' => false, 'isNew' => false],
                ['name' => 'T-shirt été respirant', 'description' => 'Vêtement léger anti-UV', 'price' => '14.99', 'image' => 'T-shirt-ete-respirant-removebg-preview-68a70de57278d.png', 'stock' => 50, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Chaussons de protection', 'description' => 'Protection des pattes contre le froid', 'price' => '24.99', 'image' => 'Chaussons-de-protection-removebg-preview-68a6fb5a3d8e3.png', 'stock' => 25, 'weight' => '0.3', 'isPromo' => true, 'isNew' => false],
            ],
            'Friandises Chiens' => [
                ['name' => 'Biscuits au saumon', 'description' => 'Friandises riches en oméga-3', 'price' => '6.99', 'image' => 'Biscuits-au-saumon-removebg-preview-68a70c9b7f621.png', 'stock' => 80, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Friandises pour chien', 'description' => 'Délicieuses récompenses naturelles', 'price' => '8.99', 'image' => 'friandise_pour_chien-removebg-preview.png', 'stock' => 60, 'weight' => '0.15', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Lamelles de canard séchées', 'description' => 'Snack 100% naturel sans additifs', 'price' => '12.99', 'image' => 'Lamelles-de-canard-sechees-removebg-preview-68a6fc0c8db7c.png', 'stock' => 45, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Bâtonnets au bœuf', 'description' => 'Friandises à mâcher longue durée', 'price' => '9.99', 'image' => 'Batonnets-au-boeuf-removebg-preview-68a6fb11395e6.png', 'stock' => 55, 'weight' => '0.12', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Mix training treats', 'description' => 'Petites récompenses pour éducation', 'price' => '5.99', 'image' => 'Mix-training-treats-removebg-preview-68a6fb35d2af5.png', 'stock' => 90, 'weight' => '0.08', 'isPromo' => true, 'isNew' => true],
            ],
            'Soins Chiens' => [
                ['name' => 'Shampoing hypoallergénique', 'description' => 'Nettoyage doux pour peaux sensibles', 'price' => '16.99', 'image' => 'Shampoing-hypoallergenique-removebg-preview-68a6fb0106e5b.png', 'stock' => 40, 'weight' => '0.5', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Brosse démêlante', 'description' => 'Brosse ergonomique anti-nœuds', 'price' => '13.99', 'image' => 'Brosse-demelante-removebg-preview-68a70db5aa856.png', 'stock' => 60, 'weight' => '0.2', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Dentifrice enzymatique', 'description' => 'Hygiène dentaire sans rinçage', 'price' => '9.99', 'image' => 'Dentifrice-enzymatique-removebg-preview-68a70e2ae4d9f.png', 'stock' => 35, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Lingettes nettoyantes', 'description' => 'Paquet de 80 lingettes parfumées', 'price' => '7.99', 'image' => 'Lingettes-nettoyantes-removebg-preview-68a6fade70eb6.png', 'stock' => 70, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Coupe-griffes professionnel', 'description' => 'Outil de précision sécurisé', 'price' => '22.99', 'image' => 'Coupe-griffes-professionnel-removebg-preview-68a70e419b8c5.png', 'stock' => 30, 'weight' => '0.15', 'isPromo' => true, 'isNew' => true],
            ],
            'Hygiène Chiens' => [
                ['name' => 'Spray démêlant pelage', 'description' => 'Facilite le brossage et démêle', 'price' => '14.99', 'image' => 'spray-demelant.png', 'stock' => 45, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Shampooing anti-puces naturel', 'description' => 'Protection naturelle contre parasites', 'price' => '18.99', 'image' => 'shampooing-antipuces.png', 'stock' => 35, 'weight' => '0.5', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Baume pour coussinets', 'description' => 'Protection et hydratation des pattes', 'price' => '11.99', 'image' => 'baume-coussinets.png', 'stock' => 50, 'weight' => '0.1', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Serviettes microfibre', 'description' => 'Séchage rapide et efficace', 'price' => '12.99', 'image' => 'serviettes-microfibre.png', 'stock' => 40, 'weight' => '0.4', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Kit toilettage complet', 'description' => 'Tout le nécessaire pour le toilettage', 'price' => '45.99', 'image' => 'kit-toilettage.png', 'stock' => 20, 'weight' => '1.2', 'isPromo' => true, 'isNew' => false],
            ],

            // CHATS (5 sous-catégories) - Structure authentique des camarades
            'Litières & Bacs' => [
                ['name' => 'Litière agglomérante premium', 'description' => 'Litière naturelle ultra-absorbante', 'price' => '18.99', 'image' => 'litiere-agglomerante.png', 'stock' => 50, 'weight' => '10.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Bac à litière XXL', 'description' => 'Bac extra-large avec rebords hauts', 'price' => '29.99', 'image' => 'bac-litiere-xxl.png', 'stock' => 20, 'weight' => '2.5', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Pelle à litière ergonomique', 'description' => 'Pelle avec manche antidérapant', 'price' => '8.99', 'image' => 'pelle-litiere.png', 'stock' => 60, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Tapis de propreté chat', 'description' => 'Tapis pour éviter la dispersion', 'price' => '15.99', 'image' => 'tapis-proprete.png', 'stock' => 35, 'weight' => '0.8', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Désodorisant litière naturel', 'description' => 'Neutralise les odeurs naturellement', 'price' => '9.99', 'image' => 'desodorisant-litiere.png', 'stock' => 45, 'weight' => '0.4', 'isPromo' => true, 'isNew' => false],
            ],
            'Herbe à chat' => [
                ['name' => 'Herbe à chat fraîche bio', 'description' => 'Herbe cultivée biologiquement', 'price' => '4.99', 'image' => 'herbe-chat-fraiche.png', 'stock' => 80, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Spray herbe à chat', 'description' => 'Attractif liquide pour jouets', 'price' => '9.99', 'image' => 'spray-herbe-chat.png', 'stock' => 60, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Graines herbe à chat', 'description' => 'Pour faire pousser votre propre herbe', 'price' => '6.99', 'image' => 'graines-herbe-chat.png', 'stock' => 50, 'weight' => '0.05', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Jouet herbe à chat rechargeable', 'description' => 'Souris avec compartiment herbe', 'price' => '12.99', 'image' => 'jouet-herbe-rechargeable.png', 'stock' => 40, 'weight' => '0.15', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Pot d\'herbe à chat décoratif', 'description' => 'Pot en céramique avec herbe fraîche', 'price' => '16.99', 'image' => 'pot-herbe-decoratif.png', 'stock' => 25, 'weight' => '0.6', 'isPromo' => true, 'isNew' => false],
            ],
            'Croquettes Chats' => [
                ['name' => 'Croquettes Chat Adult', 'description' => 'Alimentation équilibrée chat adulte', 'price' => '42.99', 'image' => 'croquettes-chat-adult.png', 'stock' => 25, 'weight' => '10.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Croquettes Chaton', 'description' => 'Nutrition spéciale croissance', 'price' => '38.99', 'image' => 'croquettes-chaton.png', 'stock' => 30, 'weight' => '8.0', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Croquettes Chat Stérilisé', 'description' => 'Contrôle du poids et système urinaire', 'price' => '44.99', 'image' => 'croquettes-sterilise.png', 'stock' => 22, 'weight' => '10.0', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Croquettes Chat Senior', 'description' => 'Formule adaptée aux chats âgés', 'price' => '46.99', 'image' => 'croquettes-senior-chat.png', 'stock' => 18, 'weight' => '9.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Croquettes Sans Céréales Chat', 'description' => 'Alimentation hypoallergénique', 'price' => '52.99', 'image' => 'croquettes-sans-cereales-chat.png', 'stock' => 15, 'weight' => '8.5', 'isPromo' => true, 'isNew' => true],
            ],
            'Jouets Chats' => [
                ['name' => 'Souris en peluche vibrante', 'description' => 'Jouet qui bouge tout seul', 'price' => '12.99', 'image' => 'souris-vibrante.png', 'stock' => 50, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Plume au bout d\'une canne', 'description' => 'Jouet interactif avec plumes', 'price' => '8.99', 'image' => 'canne-plume.png', 'stock' => 60, 'weight' => '0.15', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Balle distributrice de friandises', 'description' => 'Stimulation mentale et récompense', 'price' => '14.99', 'image' => 'balle-friandises.png', 'stock' => 35, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Tunnel de jeu pliable', 'description' => 'Tunnel en tissu avec bruit craquant', 'price' => '16.99', 'image' => 'tunnel-jeu.png', 'stock' => 25, 'weight' => '0.5', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Circuit de jeu avec balles', 'description' => 'Plateau circulaire avec 3 balles', 'price' => '19.99', 'image' => 'circuit-balles.png', 'stock' => 30, 'weight' => '0.8', 'isPromo' => true, 'isNew' => false],
            ],
            'Arbres à chat' => [
                ['name' => 'Arbre à chat 120cm', 'description' => 'Structure à 3 niveaux avec griffoir', 'price' => '89.99', 'image' => 'arbre-chat-120.png', 'stock' => 8, 'weight' => '15.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Griffoir vertical design', 'description' => 'Poteau griffoir moderne en sisal', 'price' => '34.99', 'image' => 'griffoir-vertical.png', 'stock' => 20, 'weight' => '5.0', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Hamac fenêtre ventouses', 'description' => 'Couchage avec vue sur l\'extérieur', 'price' => '24.99', 'image' => 'hamac-fenetre.png', 'stock' => 30, 'weight' => '2.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Maison cube modulable', 'description' => 'Cachette avec coussins amovibles', 'price' => '45.99', 'image' => 'maison-cube.png', 'stock' => 15, 'weight' => '8.0', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Échelle murale sisal', 'description' => 'Parcours mural modulaire', 'price' => '129.99', 'image' => 'echelle-murale.png', 'stock' => 5, 'weight' => '20.0', 'isPromo' => true, 'isNew' => false],
            ],

           
            // Produits pour OISEAUX
            'Graines & Alimentation' => [
                ['name' => 'Mélange graines premium', 'description' => 'Assortiment équilibré toutes espèces', 'price' => '14.99', 'image' => 'melange-graines.png', 'stock' => 40, 'weight' => '2.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Graines de tournesol bio', 'description' => 'Graines biologiques haute énergie', 'price' => '8.99', 'image' => 'graines-tournesol.png', 'stock' => 60, 'weight' => '1.5', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Pâtée aux œufs premium', 'description' => 'Complément nutritionnel riche', 'price' => '12.99', 'image' => 'patee-oeufs.png', 'stock' => 35, 'weight' => '1.0', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Biscuits aux fruits', 'description' => 'Friandises naturelles vitaminées', 'price' => '6.99', 'image' => 'biscuits-fruits.png', 'stock' => 50, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Bloc minéral calcium', 'description' => 'Apport essentiel en minéraux', 'price' => '4.99', 'image' => 'bloc-mineral.png', 'stock' => 70, 'weight' => '0.2', 'isPromo' => true, 'isNew' => true],
            ],
            'Cages & Volières' => [
                ['name' => 'Cage perruche 60x40cm', 'description' => 'Habitat spacieux avec accessoires', 'price' => '79.99', 'image' => 'cage-perruche.png', 'stock' => 10, 'weight' => '8.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Volière extérieure 2m', 'description' => 'Grande volière jardin galvanisée', 'price' => '299.99', 'image' => 'voliere-exterieure.png', 'stock' => 3, 'weight' => '45.0', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Cage transport sécurisée', 'description' => 'Boîte de transport ventilée', 'price' => '24.99', 'image' => 'cage-transport.png', 'stock' => 25, 'weight' => '1.5', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Mangeoire extérieure suspendue', 'description' => 'Distributeur pour oiseaux sauvages', 'price' => '16.99', 'image' => 'mangeoire-suspendue.png', 'stock' => 30, 'weight' => '0.8', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Abreuvoir automatique', 'description' => 'Distributeur d\'eau à niveau constant', 'price' => '12.99', 'image' => 'abreuvoir-auto.png', 'stock' => 40, 'weight' => '0.5', 'isPromo' => true, 'isNew' => false],
            ],
            'Jouets & Accessoires' => [
                ['name' => 'Balançoire en bois naturel', 'description' => 'Perchoir oscillant amusant', 'price' => '9.99', 'image' => 'balancoire-bois.png', 'stock' => 50, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Miroir avec clochette', 'description' => 'Compagnon avec son stimulant', 'price' => '7.99', 'image' => 'miroir-clochette.png', 'stock' => 60, 'weight' => '0.1', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Échelle corde colorée', 'description' => 'Parcours d\'agilité souple', 'price' => '11.99', 'image' => 'echelle-corde.png', 'stock' => 35, 'weight' => '0.2', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Balle distributrice graines', 'description' => 'Jouet intelligent stimulant', 'price' => '13.99', 'image' => 'balle-graines.png', 'stock' => 25, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Carillon musical', 'description' => 'Sons apaisants et mélodieux', 'price' => '18.99', 'image' => 'carillon-musical.png', 'stock' => 20, 'weight' => '0.5', 'isPromo' => true, 'isNew' => true],
            ],
            'Perchoirs' => [
                ['name' => 'Perchoir bois naturel 20cm', 'description' => 'Branche naturelle non traitée', 'price' => '5.99', 'image' => 'perchoir-bois-20.png', 'stock' => 80, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Perchoir calcium spiralé', 'description' => 'Apport minéral et usure du bec', 'price' => '8.99', 'image' => 'perchoir-calcium.png', 'stock' => 50, 'weight' => '0.15', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Perchoir chauffant 14W', 'description' => 'Confort thermique régulé', 'price' => '29.99', 'image' => 'perchoir-chauffant.png', 'stock' => 15, 'weight' => '0.8', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Set 3 perchoirs variés', 'description' => 'Diamètres différents pour exercice', 'price' => '16.99', 'image' => 'set-perchoirs.png', 'stock' => 30, 'weight' => '0.6', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Perchoir ventouse fenêtre', 'description' => 'Fixation sans perçage', 'price' => '12.99', 'image' => 'perchoir-ventouse.png', 'stock' => 25, 'weight' => '0.4', 'isPromo' => true, 'isNew' => true],
            ],
            'Soins & Vitamines' => [
                ['name' => 'Complexe vitaminé liquide', 'description' => 'Supplément complet A,D,E', 'price' => '11.99', 'image' => 'vitamines-liquide.png', 'stock' => 40, 'weight' => '0.1', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Spray antiparasitaire naturel', 'description' => 'Protection douce aux huiles', 'price' => '14.99', 'image' => 'spray-antiparasitaire.png', 'stock' => 30, 'weight' => '0.2', 'isPromo' => true, 'isNew' => true],
                ['name' => 'Poudre probiotique digestive', 'description' => 'Équilibre de la flore intestinale', 'price' => '16.99', 'image' => 'probiotique-poudre.png', 'stock' => 25, 'weight' => '0.05', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Baume patte réparateur', 'description' => 'Soin pour pattes abîmées', 'price' => '9.99', 'image' => 'baume-patte.png', 'stock' => 35, 'weight' => '0.03', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Désinfectant cage bio', 'description' => 'Nettoyant sans danger pour oiseaux', 'price' => '8.99', 'image' => 'desinfectant-cage.png', 'stock' => 45, 'weight' => '0.5', 'isPromo' => true, 'isNew' => false],
            ],
            'Nids & Reproduction' => [
                ['name' => 'Nichoir bois exotique', 'description' => 'Maison de reproduction naturelle', 'price' => '22.99', 'image' => 'nichoir-bois.png', 'stock' => 20, 'weight' => '1.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Matériaux nidification naturels', 'description' => 'Fibres de coco et mousse', 'price' => '6.99', 'image' => 'materiaux-nid.png', 'stock' => 50, 'weight' => '0.1', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Pondoir canari standard', 'description' => 'Nid spécialement conçu pour canaris', 'price' => '12.99', 'image' => 'pondoir-canari.png', 'stock' => 30, 'weight' => '0.3', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Œufs factices lot de 6', 'description' => 'Pour limiter la ponte excessive', 'price' => '8.99', 'image' => 'oeufs-factices.png', 'stock' => 40, 'weight' => '0.1', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Pâtée élevage enrichie', 'description' => 'Nutrition spéciale reproduction', 'price' => '15.99', 'image' => 'patee-elevage.png', 'stock' => 25, 'weight' => '1.0', 'isPromo' => true, 'isNew' => true],
            ],

            // LÉZARDS (5 sous-catégories) - Structure authentique des camarades
            'Alimentation Lézards' => [
                ['name' => 'Grillons vivants (boîte de 50)', 'description' => 'Grillons frais pour l\'alimentation de vos reptiles', 'price' => '12.99', 'image' => 'grillons-vivants.png', 'stock' => 25, 'weight' => '0.2', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Vers de farine séchés 500g', 'description' => 'Vers de farine riches en protéines', 'price' => '8.50', 'image' => 'vers-farine.png', 'stock' => 40, 'weight' => '0.5', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Supplément calcium + D3', 'description' => 'Complément alimentaire essentiel pour reptiles', 'price' => '15.90', 'image' => 'calcium-d3.png', 'stock' => 30, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Granulés pour gecko léopard', 'description' => 'Alimentation complète spécialement formulée', 'price' => '18.75', 'image' => 'granules-gecko.png', 'stock' => 20, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Fruits séchés pour iguane', 'description' => 'Mélange de fruits adaptés aux iguanes', 'price' => '11.20', 'image' => 'fruits-iguane.png', 'stock' => 35, 'weight' => '0.25', 'isPromo' => true, 'isNew' => true],
            ],
            'Chauffage & UV' => [
                ['name' => 'Lampe UV 10.0 25W', 'description' => 'Lampe UVB essentielle pour la synthèse de vitamine D3', 'price' => '45.90', 'image' => 'lampe-uv-25w.png', 'stock' => 15, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Tapis chauffant 20W', 'description' => 'Chauffage par le sol pour terrarium', 'price' => '32.50', 'image' => 'tapis-chauffant.png', 'stock' => 25, 'weight' => '0.5', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Thermostat digital', 'description' => 'Contrôle précis de la température', 'price' => '89.99', 'image' => 'thermostat-digital.png', 'stock' => 10, 'weight' => '0.4', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Lampe chauffante céramique 100W', 'description' => 'Chauffage infrarouge sans lumière', 'price' => '28.75', 'image' => 'lampe-ceramique.png', 'stock' => 20, 'weight' => '0.25', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Minuteur pour éclairage', 'description' => 'Programmateur jour/nuit automatique', 'price' => '25.60', 'image' => 'minuteur-eclairage.png', 'stock' => 18, 'weight' => '0.2', 'isPromo' => true, 'isNew' => true],
            ],
            'Terrariums' => [
                ['name' => 'Terrarium verre 60x40x40cm', 'description' => 'Terrarium en verre avec aération optimale', 'price' => '159.99', 'image' => 'terrarium-verre-60.png', 'stock' => 8, 'weight' => '12.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Terrarium grillagé 80x50x50cm', 'description' => 'Parfait pour caméléons et reptiles arboricoles', 'price' => '189.50', 'image' => 'terrarium-grillage.png', 'stock' => 5, 'weight' => '15.0', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Kit terrarium débutant', 'description' => 'Tout le nécessaire pour commencer', 'price' => '249.99', 'image' => 'kit-terrarium.png', 'stock' => 12, 'weight' => '18.0', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Terrarium corner 40x40x60cm', 'description' => 'Modèle d\'angle optimisant l\'espace', 'price' => '145.80', 'image' => 'terrarium-corner.png', 'stock' => 6, 'weight' => '10.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Grande serre tropicale 120x60x80cm', 'description' => 'Pour reptiles de grande taille', 'price' => '399.99', 'image' => 'serre-tropicale.png', 'stock' => 3, 'weight' => '35.0', 'isPromo' => true, 'isNew' => true],
            ],
            'Décoration' => [
                ['name' => 'Rocher cache XL', 'description' => 'Cachette naturelle en résine', 'price' => '34.90', 'image' => 'rocher-cache-xl.png', 'stock' => 20, 'weight' => '2.5', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Branche de vigne naturelle', 'description' => 'Décoration et perchoir naturel', 'price' => '22.50', 'image' => 'branche-vigne.png', 'stock' => 30, 'weight' => '1.0', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Plantes artificielles jungle', 'description' => 'Set de 3 plantes tropicales', 'price' => '19.75', 'image' => 'plantes-jungle.png', 'stock' => 25, 'weight' => '0.8', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Substrat fibre de coco 10L', 'description' => 'Substrat naturel et absorbant', 'price' => '14.30', 'image' => 'substrat-coco.png', 'stock' => 40, 'weight' => '5.0', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Cascade avec pompe', 'description' => 'Point d\'eau décoratif avec circulation', 'price' => '78.90', 'image' => 'cascade-pompe.png', 'stock' => 8, 'weight' => '3.5', 'isPromo' => true, 'isNew' => true],
            ],
            'Soins Lézards' => [
                ['name' => 'Spray hydratant pour reptiles', 'description' => 'Maintient l\'humidité de la peau', 'price' => '16.80', 'image' => 'spray-hydratant.png', 'stock' => 30, 'weight' => '0.3', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Désinfectant terrarium 500ml', 'description' => 'Nettoyage et désinfection sécurisés', 'price' => '12.45', 'image' => 'desinfectant-terrarium.png', 'stock' => 35, 'weight' => '0.5', 'isPromo' => true, 'isNew' => false],
                ['name' => 'Complément vitamines reptiles', 'description' => 'Vitamines essentielles en gouttes', 'price' => '21.90', 'image' => 'vitamines-reptiles.png', 'stock' => 25, 'weight' => '0.1', 'isPromo' => false, 'isNew' => true],
                ['name' => 'Pommade anti-parasites', 'description' => 'Traitement préventif naturel', 'price' => '18.60', 'image' => 'pommade-antiparasites.png', 'stock' => 20, 'weight' => '0.05', 'isPromo' => false, 'isNew' => false],
                ['name' => 'Kit de premiers secours reptile', 'description' => 'Trousse de soins d\'urgence complète', 'price' => '45.75', 'image' => 'kit-secours-reptile.png', 'stock' => 12, 'weight' => '0.8', 'isPromo' => true, 'isNew' => true],
            ],
        ];
    }
}
