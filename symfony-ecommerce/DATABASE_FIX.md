# 🔧 Fix pour la navigation navbar - Problème de base de données

## 🚨 Problème identifié

Les liens de navigation dans la navbar (ex: `http://127.0.0.1:8000/product?category=chiens&subcategory=alimentation`) ne fonctionnent pas de manière cohérente entre les différents environnements de développement.

**Cause:** Chaque développeur a sa propre base de données locale avec des IDs différents pour les catégories et sous-catégories.

## ✅ Solution implémentée

### 1. Mapping dynamique (dans `templates/pages/productList.html.twig`)

Au lieu d'utiliser des IDs hardcodés, le JavaScript génère maintenant dynamiquement les mappings à partir des vraies données de la base de données via Twig.

### 2. Commande de synchronisation

Une nouvelle commande a été créée pour synchroniser la base de données :

```bash
php bin/console app:sync-database
```

Cette commande :
- ✅ Crée les catégories et sous-catégories manquantes
- ✅ Affiche les IDs existants  
- ✅ Ne supprime rien (safe)
- ✅ Peut être exécutée plusieurs fois sans problème

## 🚀 Instructions pour les développeurs

### Si les liens navbar ne fonctionnent pas chez vous :

1. **Synchroniser la base de données :**
   ```bash
   cd symfony-ecommerce
   php bin/console app:sync-database
   ```

2. **Vider le cache :**
   ```bash
   php bin/console cache:clear
   ```

3. **Redémarrer le serveur :**
   ```bash
   symfony serve
   ```

4. **Tester les liens :**
   - http://127.0.0.1:8000/product?category=chiens
   - http://127.0.0.1:8000/product?category=chiens&subcategory=alimentation

### Structure attendue de la base de données :

```
📁 Chiens
  └─ Laisses & Colliers
  └─ Os & Jouets
  └─ Croquettes pour chiens
  └─ Gamelles & Accessoires
  └─ Vêtements pour chiens
  └─ Soins & Hygiène
  └─ Friandises

📁 Chats
  └─ Litières & Bacs
  └─ Herbe à chat
  └─ Croquettes pour chats
  └─ Jouets pour chats
  └─ Arbres à chat
  └─ Soins & Hygiène
  └─ Friandises

📁 Oiseaux
  └─ Graines & Alimentation
  └─ Cages & Volières
  └─ Jouets & Accessoires
  └─ Perchoirs
  └─ Soins & Vitamines
  └─ Nids & Reproduction

📁 Lézards
  └─ Alimentation
  └─ Chauffage & UV
  └─ Terrariums
  └─ Décoration
  └─ Soins
```

## 🔍 Debug

Pour vérifier les IDs dans votre base :

```bash
# Catégories
php bin/console doctrine:query:sql "SELECT id, name FROM category ORDER BY id"

# Sous-catégories
php bin/console doctrine:query:sql "SELECT id, name, category_id FROM sub_category ORDER BY category_id, id"
```

## 🎯 Avantages de cette solution

- ✅ **Dynamique :** S'adapte automatiquement aux IDs de chaque base de données
- ✅ **Robuste :** Plus de hardcodage d'IDs
- ✅ **Synchronisé :** Tous les développeurs peuvent avoir la même structure
- ✅ **Sûr :** La commande ne supprime jamais de données existantes
