# 🚀 Guide de Collaboration Git - Équipe E-commerce

## ⚠️ PROBLÈME ACTUEL
- Trop de branches parallèles (30+ branches)
- Merges qui perdent des features
- Code qui marche que sur l'ordinateur de celui qui l'a écrit

## ✅ NOUVELLES RÈGLES D'ÉQUIPE

### 1. 🔄 AVANT de commencer une nouvelle feature
```bash
# Toujours exécuter ce script AVANT de travailler
./scripts/sync-team.sh
```

### 2. 📝 Nommage des branches
```
feature/nom-court-explicite    # Pour nouvelles fonctionnalités
fix/probleme-specifique        # Pour corrections de bugs
chore/maintenance             # Pour tâches techniques
```

### 3. 🌊 Workflow de développement
```bash
# 1. Se synchroniser
./scripts/sync-team.sh

# 2. Créer une nouvelle branche DEPUIS develop
git checkout -b feature/ma-nouvelle-feature

# 3. Travailler sur la feature (commits réguliers)
git add .
git commit -m "[+] Feature: Description claire"

# 4. AVANT de push, re-synchroniser avec develop
git checkout develop
git pull origin develop
git checkout feature/ma-nouvelle-feature
git rebase develop  # ⚠️ IMPORTANT: rebase au lieu de merge

# 5. Push et créer la Pull Request
git push origin feature/ma-nouvelle-feature
```

### 4. 🔀 Pull Requests obligatoires
- ❌ PAS de push direct sur develop
- ✅ TOUJOURS passer par une PR
- ✅ Demander une review à 1 autre personne
- ✅ Tester localement APRÈS avoir rebased sur develop

### 5. 🗂️ Gestion des fichiers sensibles
Ces fichiers ne doivent JAMAIS être committés:
- `.env` (chacun a sa config locale)
- `var/cache/*`
- `var/log/*`
- `vendor/*` (géré par composer)

### 6. 🧹 Nettoyage des branches
```bash
# Supprimer une branche locale après merge
git branch -d feature/ma-feature-terminee

# Supprimer les branches remote supprimées
git remote prune origin
```

## 🚨 RÈGLES D'URGENCE pour corriger le chaos actuel

### Étape 1: Identifier les branches importantes
```bash
git branch -a | grep "feature/" | wc -l  # Compter les branches feature
```

### Étape 2: Faire un "Grand Nettoyage"
1. Merger les features PRIORITAIRES une par une
2. Supprimer les branches obsolètes/abandonnées
3. Redémarrer proprement

### Étape 3: Synchronisation des bases de données
```bash
# Backup de la DB avant merge
pg_dump ecommerce > backup_avant_merge.sql

# Après merge, mettre à jour
php bin/console doctrine:migrations:migrate
```

## 💡 BONNES PRATIQUES

### Commits descriptifs
```
[+] Feature: Ajout du système de panier
[*] Update: Amélioration de la navbar
[-] Remove: Suppression du code obsolète
[!] Fix: Correction du bug de connexion
```

### Tests avant merge
```bash
# Vérifier que tout fonctionne
php bin/console doctrine:schema:validate
php bin/console cache:clear
# Tester les pages principales
```

## 🎯 OBJECTIF
- 1 seule version stable sur develop
- Chaque feature fonctionne pour TOUS
- Pas de perte de code lors des merges
