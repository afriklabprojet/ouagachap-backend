# OUAGA CHAP - Documentation API

> Documentation complète du backend Laravel pour OUAGA CHAP

## 📚 Table des matières

### Configuration & Déploiement
1. [Installation](./INSTALLATION.md) - Guide d'installation et configuration
2. [Déploiement](./DEPLOYMENT.md) - Guide de mise en production

### Architecture
3. [Architecture](./ARCHITECTURE.md) - Structure du projet et patterns
4. [Base de données](./DATABASE.md) - Schéma et migrations

### API & Authentification
5. [API Reference](./API_REFERENCE.md) - Documentation des endpoints
6. [Authentification](./AUTHENTICATION.md) - Système OTP et tokens

### Fonctionnalités
7. [Paiement](./PAYMENT.md) - Intégration Mobile Money (Jeko)
8. [Notifications](./NOTIFICATIONS.md) - SMS et Push notifications (Firebase)
9. [Temps Réel](./REALTIME.md) - WebSockets et suivi de position

### Administration
10. [Panneau Admin](./FILAMENT_ADMIN.md) - Interface Filament 3

### Applications Mobiles
11. [Flutter Apps](./FLUTTER_APPS.md) - Applications client et coursier

### Qualité & Support
12. [Tests](./TESTING.md) - Stratégie de tests (PHPUnit & Flutter)
13. [Dépannage](./TROUBLESHOOTING.md) - Problèmes courants et solutions

## 🚀 Démarrage rapide

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## 🔗 Liens utiles

- **Admin Panel**: `/admin`
- **API Documentation**: `/docs`
- **Health Check**: `/api/v1/health`

## 📱 Applications associées

- [ouagachap-client](https://github.com/afriklabprojet/ouagachap-client) - App Client Flutter
- [ouagachap-coursier](https://github.com/afriklabprojet/ouagachap-coursier) - App Coursier Flutter
