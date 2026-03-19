<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250827094932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mise à jour des images des produits avec les vraies images disponibles';
    }

    public function up(Schema $schema): void
    {
        $productImageMapping = [
            'Croquettes Premium Adult' => 'Croquettes-Premium-Adult-removebg-preview-68a70cfd3f1d9.png',
            'Croquettes Puppy' => 'Croquettes-Puppy-removebg-preview-68a70c7acd02b.png',
            'Croquettes Senior 7+' => 'Croquettes-Senior-7-removebg-preview-68a70d125e2eb.png',
            'Croquettes Sans Céréales' => 'Croquettes-Sans-Cereales-removebg-preview-68a70d26f25ba.png',
            'Croquettes Grandes Races' => 'Croquettes-Grandes-Races-removebg-preview-68a70ce280de0.png',
            'Laisse rétractable 5m' => 'Laisse-retractable-5m-removebg-preview-68a6fc3a7c267.png',
            'Collier anti-puces naturel' => 'Collier-anti-puces-naturel-removebg-preview-68a6fb6c4e9f8.png',
            'Harnais de sécurité' => 'Harnais-de-securite-removebg-preview-68a6fc519458b.png',
            'Kong Classic' => 'Kong-Classic-removebg-preview-68a70c2501e55.png',
            'Frisbee pour chien' => 'Frisbee-pour-chien-removebg-preview-68a70c459095b.png',
            'Balle rebondissante' => 'Balle-rebondissante-removebg-preview-68a6fb25ca090.png',
            'Corde à nœuds' => 'Corde-a-noeuds-removebg-preview-68a6fb4b507de.png',
            'Os à mâcher naturel' => 'Os-a-macher-naturel-removebg-preview-68a70c56c49c4.png',
            'Bâtonnets au bœuf' => 'Batonnets-au-boeuf-removebg-preview-68a6fb11395e6.png',
            'Lamelles de canard séchées' => 'Lamelles-de-canard-sechees-removebg-preview-68a6fc0c8db7c.png',
            'Mix training treats' => 'Mix-training-treats-removebg-preview-68a6fb35d2af5.png',
            'Biscuits au saumon' => 'Biscuits-au-saumon-removebg-preview-68a70c9b7f621.png',
            'Manteau d\'hiver imperméable' => 'Manteau-d-hiver-impermeable-removebg-preview-68a70e38ce4eb.png',
            'Pull en laine douce' => 'Pull-en-laine-douce-removebg-preview-68a70da853154.png',
            'T-shirt été respirant' => 'T-shirt-ete-respirant-removebg-preview-68a70de57278d.png',
            'Imperméable transparent' => 'Impermeable-transparent-removebg-preview-68a6fb8041502.png',
            'Chaussons de protection' => 'Chaussons-de-protection-removebg-preview-68a6fb5a3d8e3.png',
            'Shampoing hypoallergénique' => 'Shampoing-hypoallergenique-removebg-preview-68a6fb0106e5b.png',
            'Brosse démêlante' => 'Brosse-demelante-removebg-preview-68a70db5aa856.png',
            'Coupe-griffes professionnel' => 'Coupe-griffes-professionnel-removebg-preview-68a70e419b1c5.png',
            'Dentifrice enzymatique' => 'Dentifrice-enzymatique-removebg-preview-68a70e2ae4d9f.png',
            'Lingettes nettoyantes' => 'Lingettes-nettoyantes-removebg-preview-68a6fade70eb6.png',
            'Gamelle inox double' => 'Gamelle-inox-double-removebg-preview-68a70d3acc082.png',
            'Gamelle surélevée ajustable' => 'Gamelle-surelevee-ajustable-removebg-preview-68a70d686137f.png',
            'Fontaine à eau automatique' => 'Fontaine-a-eau-automatique-removebg-preview-68a70e01ee523.png',
            'Distributeur de croquettes' => 'Distributeur-de-croquettes-removebg-preview-68a70f178a493.png',
            'Tapis de gamelle antidérapant' => 'Tapis-de-gamelle-antiderapant-removebg-preview-68a70d5762171.png',
            'Collier lumineux LED' => 'Collier-lumineux-LED-removebg-preview-68a70f09b1504.png',

            // Chats
            'Croquettes Chat Adult' => 'Croquettes-Chat-Adult-68ac160b5e720.png',
            'Croquettes Chat Junior' => 'Croquettes-Chat-Junior-68ac18758b041.jpg',
            'Croquettes Chat Senior' => 'Croquettes-Chat-Senior-68ac161b076e1.jpg',
            'Croquettes Chat Sensible' => 'Croquettes-Chat-Sensible-68ac29e793fa4.webp',
            'Croquettes Chat Stérilisé' => 'Croquettes-Chat-steriliseLjpg-68ac2b2b5cde9.jpg',
            'Litière agglomérante premium' => 'litiereaglomerante-68ac156aca377.jpg',
            'Pelle à litière ergonomique' => 'Pelle-a-litiere-ergonomique-68ac159c75c1f.jpg',
            'Désodorisant litière naturel' => 'Desodorisant-litiere-naturel-68ac18641ec8c.jpg',
            'Herbe à chat sécher' => 'herbeachatsecher-68ac15fd44dcf.jpg',
            'Graines herbe à chat' => 'grainesherbeachat-68ac2b19dfd15.jpg',
            'Spray herbe à chat' => 'sprayherbeachat-68ac2b0c0e96a.jpg',
            'Arbres à chat' => 'Arbres-a-chat-68ac1648d58a4.webp',
            'Griffoir vertical design' => 'Griffoir-vertical-design-68ac188e99109.webp',
            'Hamac fenêtre ventouses' => 'Hamac-fenetre-ventouses-68ac16589c053.jpg',
            'Maison cube modulable' => 'Maison-cube-modulable-68ac188154761.jpg',
            'Coussin herbe à chat' => 'Coussinherbea-hat-68ac184ef380c.jpg',
            'Échelle murale sisal' => 'Echelle-murale-sisal-68ac1899a98fc.jpg',
            'Plume au bout d\'une canne' => 'Plume-au-bout-d-une-canne-68ac18c87e909.webp',
            'Souris en peluche vibrante' => 'Souris-en-peluche-vibrante-68ac2b40d43d3.webp',
            'Baume patte réparateur' => 'Baume-patte-reparateur-68ac1a7d0987b.jpg',
            'Lingettes nettoyante' => 'lingettenettoyante-68ac2d203f094.jpg',
            'Coupe griffes pro' => 'coupegriffespro-68ac2d7e11452.jpg',
            'Sticks dentaires' => 'Sticks-dentaires-removebg-preview-68ac2df912968.png',

            // Oiseaux
            'Mélange graines premium' => 'Melange-graines-premium-68ac194f079bc.jpg',
            'Graines de tournesol bio' => 'Graines-de-tournesol-bio-68ac1959f1e65.webp',
            'Pâtée aux œufs premium' => 'Patee-aux-oeufs-premium-68ac1971cc69b.webp',
            'Pâtée élevage enrichie' => 'Patee-elevage-enrichie-68ac2e984d9ec.jpg',
            'Fruit sécher' => 'fruitsecher-68ac2effae984.jpg',
            'Biscuits aux fruits' => 'Biscuits-aux-fruits-68ac197d8262c.webp',
            'Cage perruche 60x40cm' => 'Cage-perruche-60x40cm-68ac279f94936.jpg',
            'Cage transport sécurisée' => 'Cage-transport-securisee-68ac198d11c5d.jpg',
            'Volière extérieure 2m' => 'Voliere-exterieure-2m-68ac2f527e5b1.webp',
            'Mangeoire suspendue' => 'Mangeoire-suspendue-68ac199bd9ea2.jpg',
            'Abreuvoir automatique' => 'Abreuvoir-automatique-68ac19a8f1e2a.webp',
            'Balle distributrice graines' => 'Balle-distributrice-graines-68ac19e2d3d0f.jpg',
            'Perchoir bois 20cm' => 'Perchoir-bois-20cm-68ac2ab355a9a.webp',
            'Perchoir calcium spirale' => 'Perchoir-calcium-spirale-68ac1a2010277.jpg',
            'Perchoir chauffant' => 'Perchoir-chauffant-68ac1a2d1672c.jpg',
            'Perchoir ventouse fenêtre' => 'Perchoir-ventouse-fenetre-68ac1a6b3cc16.webp',
            'Balançoire en bois' => 'Balancoire-en-bois-68ac19b7c533c.webp',
            'Échelle corde colorée' => 'Echelle-corde-coloree-68ac19d83f968.jpg',
            'Miroir avec cloche' => 'miroir-avec-cloche-68ac19cce9c5c.webp',
            'Nichoir bois exotique' => 'Nichoir-bois-exotique-68ac2a9fa451c.webp',
            'Œufs factices' => 'OEufs-factices-68ac27d417ae6.webp',
            'Branches naturelles' => 'Branches-naturelles-68ac205b2a138.jpg',
            'Bloc minéral calcium' => 'Bloc-mineral-calcium-68ac2a6c694da.jpg',
            'Supplément calcium avec vitamine D3' => 'Supplement-calcium-avec-vitamine-D3-68ac1b4b20e0b.webp',
            'Complexe vitamine liquide' => 'omplexe-vitamine-liquide-68ac2eaf0d15b.jpg',
            'Spray mue facilitée' => 'Spray-mue-facilitee-68ac2dca2f2de.webp',
            'Minuteur' => 'minuteur-68ac29cd862ce.webp',

            // Lézards
            'Granules pour gecko léopard' => 'Granules-pour-gecko-leopard-68ac298930a0a.jpg',
            'Grillons vivants' => 'grillonsvivants-68ac28d0b4c4c.webp',
            'Vers de farine séché' => 'versdefarineseche-68ac28938a3ff.jpg',
            'Kit terrarium starter 45cm' => 'Kit-terrarium-starter-45cm-68ac26e8687b5.jpg',
            'Terrarium corner 40x40x60cm' => 'Terrarium-corner-40x40x60cm-68ac30b2b936e.jpg',
            'Terrarium grillage caméléon' => 'Terrarium-grillage-cameleon-68ac26ba4017e.jpg',
            'Terrarium 60x40x40' => 'terrarium604040-68ac302c29678.jpg',
            'Cachette roche XL' => 'Cachette-roche-XL-68ac26fdd1b9f.jpg',
            'Branche de vigne' => 'branchedevigne-68ac2c4c50b51.webp',
            'Plante artificielles' => 'planteartificielles-68ac2be7df62f.jpg',
            'Fibre coco' => 'fibrecoco-68ac2cd45fde3.jpg',
            'Cascade avec pompe' => 'cascadeavecpompe-68ac2908d2b37.webp',
            'Émetteur céramique 100W' => 'Emetteur-ceramique-100W-68ac28223486d.jpg',
            'Tapis chauffant' => 'tapischauffant-68ac294de3b13.jpg',
            'Tube UVB 10.0 pour désertiques' => 'Tube-UVB-10-0-pour-desertiques-68ac1b2ccb8cd.webp',
            'Thermostat digital' => 'Thermostat-digital-68ac1b6f81414.webp',
            'Désinfectant terrarium' => 'Desinfectant-terrarium-68ac2767209f7.webp',
            'Antiparasitaire naturel' => 'antiparasitaire-naturel-68ac31c8aa5aa.webp',
            'Pommade antiparasites' => 'pommadeantiparasites-68ac2e7072b16.jpg',
            'Poudre probiotique digestive' => 'Poudre-probiotique-digestive-68ac32039bd07.png'
        ];

        foreach ($productImageMapping as $productName => $imagePath) {
            $this->addSql(
                'UPDATE product SET image_url = :image_url WHERE name ILIKE :product_name',
                ['image_url' => $imagePath, 'product_name' => '%' . $productName . '%']
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE product SET image_url = \'hero-image.png\' WHERE image_url IS NOT NULL');
    }
}
