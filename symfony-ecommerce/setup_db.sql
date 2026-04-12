-- Création de l'utilisateur et de la base pour le projet ecommerce
-- Exécuter avec: sudo -u postgres psql -f setup_db.sql

CREATE USER ecommerce_user WITH PASSWORD 'ecommerce_dev_2024';
CREATE DATABASE ecommerce OWNER ecommerce_user;
GRANT ALL PRIVILEGES ON DATABASE ecommerce TO ecommerce_user;
\c ecommerce
GRANT ALL ON SCHEMA public TO ecommerce_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ecommerce_user;
