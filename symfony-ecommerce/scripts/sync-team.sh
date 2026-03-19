#!/bin/bash

# 🔄 Script de synchronisation équipe
# À exécuter AVANT de commencer à travailler sur une nouvelle feature

echo "🚀 Synchronisation avec l'équipe..."

# 1. Récupérer les dernières modifications
git fetch origin

# 2. Se mettre sur develop
git checkout develop

# 3. Récupérer les derniers changements
git pull origin develop

# 4. Nettoyer les branches locales supprimées sur le remote
git remote prune origin

# 5. Mettre à jour la base de données
echo "🗄️ Mise à jour de la base de données..."
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Vider le cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear

# 7. Installer les nouvelles dépendances
echo "📦 Mise à jour des dépendances..."
composer install --no-dev --optimize-autoloader

echo "✅ Synchronisation terminée ! Vous pouvez créer votre nouvelle branche."
echo "💡 Commande suivante : git checkout -b feature/votre-nouvelle-feature"
