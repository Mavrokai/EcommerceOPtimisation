#!/bin/bash

echo "🏥 SAUVETAGE DU PROJET - Création d'un environnement de référence"

# 1. Créer une branche de sauvegarde
git checkout develop
git checkout -b backup-before-cleanup-$(date +%Y%m%d)
git push origin backup-before-cleanup-$(date +%Y%m%d)
echo "✅ Backup créé"

# 2. Retour sur develop propre
git checkout develop
git pull origin develop

# 3. Identifier les fichiers en conflit potentiel
echo "📋 Fichiers critiques à vérifier :"
echo "- composer.json/composer.lock"
echo "- migrations/"
echo "- .env.example"
echo "- src/Entity/"

# 4. Créer un dump de la DB de référence
echo "🗄️ Création d'un dump de référence..."
php bin/console doctrine:schema:create --dump-sql > reference_schema.sql

# 5. Export des variables d'environnement de référence
cp .env .env.reference
echo "✅ Configuration de référence sauvegardée"

echo ""
echo "🎯 PROCHAINES ÉTAPES pour l'équipe :"
echo "1. Chaque membre doit récupérer cette version de référence"
echo "2. Merger UNE SEULE branche à la fois"
echo "3. Tester après chaque merge"
echo "4. Supprimer les branches obsolètes"
