# Installation et Configuration

## Prérequis

- PHP >= 8.2
- Composer
- MySQL 8.0+ ou SQLite
- Node.js >= 18 (pour les assets)
- Redis (optionnel, pour le cache et les queues)

## Installation

### 1. Cloner le repository

```bash
git clone https://github.com/afriklabprojet/ouagachap-backend.git
cd ouagachap-backend/api
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurer le fichier .env

```env
# Application
APP_NAME="OUAGA CHAP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ouagachap
DB_USERNAME=root
DB_PASSWORD=

# Pour développement rapide avec SQLite
# DB_CONNECTION=sqlite
# DB_DATABASE=/chemin/absolu/vers/database.sqlite

# SMS (Twilio)
TWILIO_SID=your_sid
TWILIO_TOKEN=your_token
TWILIO_FROM=+226XXXXXXXX

# Firebase (Push notifications)
FIREBASE_CREDENTIALS=storage/firebase-credentials.json

# Paiement Jeko
JEKO_API_URL=https://api.jfranco.com
JEKO_API_KEY=your_api_key
JEKO_MERCHANT_ID=your_merchant_id
```

### 5. Initialiser la base de données

```bash
php artisan migrate --seed
```

### 6. Créer le lien de stockage

```bash
php artisan storage:link
```

### 7. Démarrer le serveur

```bash
php artisan serve
```

L'API est accessible sur `http://127.0.0.1:8000`

## Accès Admin

- **URL**: http://127.0.0.1:8000/admin
- **Email**: admin@ouagachap.com
- **Mot de passe**: password (à changer en production!)

## Services additionnels

### Queue Worker (pour les notifications)

```bash
php artisan queue:work
```

### WebSockets (pour le temps réel)

```bash
php artisan reverb:start
```

### Scheduler (pour les tâches planifiées)

```bash
php artisan schedule:work
```

## Mode développement

En mode `APP_ENV=local`:
- L'OTP `123456` fonctionne toujours
- Les paiements sont simulés (mock)
- Les SMS ne sont pas envoyés réellement

## Vérification de l'installation

```bash
# Vérifier la santé de l'API
curl http://127.0.0.1:8000/api/v1/health

# Lancer les tests
php artisan test
```
