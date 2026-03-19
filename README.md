
# 📦 Installation Symfony 6.4 avec Twig et PostgreSQL (Mac, Windows, Linux)

Ce guide décrit comment installer un projet Symfony 6.4 avec Twig et PostgreSQL, sur **macOS, Linux et Windows**, à partir de zéro.

---

## ✅ PRÉREQUIS

### 1. PHP 8.2 ou 8.3

Symfony 6.4 nécessite PHP >= 8.1.99.

#### ➤ macOS
```bash
brew install php
```

#### ➤ Linux (Debian/Ubuntu)
```bash
sudo apt update
sudo apt install php php-cli php-mbstring php-xml php-intl php-pgsql unzip curl -y
```

#### ➤ Windows
- Installer XAMPP ou PHP pour Windows
- Ajouter PHP au PATH

---

### 2. Composer

Composer est le gestionnaire de dépendances PHP.

#### ➤ macOS / Linux
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### ➤ Windows
- Télécharger l'installateur : https://getcomposer.org/download/

**Vérification** :
```bash
composer --version
```

---

## ⚙️ INSTALLATION DE SYMFONY

### 1. Installer le CLI Symfony (recommandé)
```bash
curl -sS https://get.symfony.com/cli/installer | bash
mv ~/.symfony*/bin/symfony /usr/local/bin/symfony
```

#### ➤ Windows
- Télécharger depuis : https://symfony.com/download

**Vérification** :
```bash
symfony check:requirements
```

---

## 🚀 CRÉATION D’UN PROJET SYMFONY 6.4

```bash
symfony new mon_projet --version=6.4 --webapp
cd mon_projet
```

OU (si sans CLI Symfony) :
```bash
composer create-project symfony/skeleton mon_projet
cd mon_projet
composer require twig
composer require symfony/orm-pack
composer require symfony/maker-bundle --dev
composer require symfony/asset
composer require annotations
composer require symfony/runtime
```

---

## INSTALLATION DE POSTGRESQL

### 1. Installer PostgreSQL

#### ➤ macOS
```bash
brew install postgresql
brew services start postgresql
```

#### ➤ Linux (Ubuntu/Debian)
```bash
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
```

#### ➤ Windows
- Télécharger depuis : https://www.postgresql.org/download/windows/


Caractéristiques principales :
✅ Annotations Doctrine complètes pour PostgreSQL
✅ Tous les getters et setters
✅ Relations bidirectionnelles avec Collections
✅ Hooks de cycle de vie (PrePersist, PreUpdate)
✅ Interface UserInterface pour l'authentification
✅ Noms des colonnes mappés en snake_case pour PostgreSQL
✅ Types de données appropriés (DECIMAL, TEXT, DATETIME)
Entités créées :

User - Utilisateurs avec authentification Symfony
Category - Catégories principales
SubCategory - Sous-catégories
Product - Produits avec variantes
ProductVariant - Variantes de produits (couleur, taille)
GuestCart - Panier invité
UserCart - Panier utilisateur connecté
CartItem - Articles dans les paniers
Order - Commandes
OrderItem - Articles des commandes
Review - Avis clients
DeliveryRate - Tarifs de livraison
Address - Adresses utilisateurs
Invoice - Factures

composer require symfony/asset
composer require symfony/mime   