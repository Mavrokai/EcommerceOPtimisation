-- Migration pour ajouter le champ image aux catégories
-- À executer : php bin/console doctrine:migrations:diff

ALTER TABLE category ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- Données de test avec images
UPDATE category SET image_path = 'categories/chiens.jpg' WHERE name = 'Chiens';
UPDATE category SET image_path = 'categories/chats.jpg' WHERE name = 'Chats';
UPDATE category SET image_path = 'categories/oiseaux.jpg' WHERE name = 'Oiseaux';