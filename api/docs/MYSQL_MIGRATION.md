# Guide de Migration vers MySQL - Production

## 1. Prérequis

- MySQL 8.0+ ou MariaDB 10.5+
- PHP 8.2+ avec extension `pdo_mysql`

## 2. Création de la base de données

```sql
-- Se connecter à MySQL en tant que root
mysql -u root -p

-- Créer la base de données
CREATE DATABASE ouaga_chap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer l'utilisateur
CREATE USER 'ouaga_chap_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise123!';

-- Accorder les privilèges
GRANT ALL PRIVILEGES ON ouaga_chap.* TO 'ouaga_chap_user'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Configuration .env

```env
# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ouaga_chap
DB_USERNAME=ouaga_chap_user
DB_PASSWORD=VotreMotDePasseSecurise123!

# Cache & Sessions (recommandé en production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (si utilisé)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 4. Migration

```bash
# Nettoyer le cache
php artisan config:clear
php artisan cache:clear

# Exécuter les migrations
php artisan migrate --force

# Optimiser pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

## 5. Optimisations MySQL

Ajoutez ces configurations dans `/etc/mysql/mysql.conf.d/mysqld.cnf` :

```ini
[mysqld]
# Performance
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connexions
max_connections = 200
wait_timeout = 600
interactive_timeout = 600

# Query cache (désactivé en MySQL 8.0+)
# query_cache_type = 1
# query_cache_size = 64M

# Logs lents (debug)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

## 6. Index de performance

La migration `2026_01_20_180000_add_mysql_performance_indexes.php` ajoute automatiquement des index optimisés pour :

- Recherche de commandes par statut et date
- Recherche géographique des coursiers
- Statistiques de paiements et retraits

## 7. Backup automatique

Script de backup (`/scripts/backup_mysql.sh`) :

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/var/backups/ouaga_chap
mkdir -p $BACKUP_DIR

mysqldump -u ouaga_chap_user -p'VotreMotDePasse' ouaga_chap | gzip > $BACKUP_DIR/ouaga_chap_$DATE.sql.gz

# Garder les 7 derniers jours
find $BACKUP_DIR -type f -mtime +7 -delete
```

Crontab :
```cron
0 2 * * * /scripts/backup_mysql.sh
```

## 8. Monitoring

Requêtes utiles pour le monitoring :

```sql
-- Nombre de connexions actives
SHOW STATUS LIKE 'Threads_connected';

-- Requêtes lentes
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- Taille de la base
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'ouaga_chap'
GROUP BY table_schema;
```

## 9. Checklist de déploiement

- [ ] Base de données MySQL créée
- [ ] Utilisateur avec privilèges configuré
- [ ] Variables .env mises à jour
- [ ] Migrations exécutées avec succès
- [ ] Index de performance appliqués
- [ ] Cache Laravel optimisé
- [ ] Backup automatique configuré
- [ ] SSL/TLS activé pour les connexions MySQL distantes
