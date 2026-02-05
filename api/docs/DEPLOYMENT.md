# Déploiement en Production

## Prérequis serveur

- **OS**: Ubuntu 22.04 LTS
- **PHP**: 8.2+
- **Web Server**: Nginx
- **Database**: MySQL 8.0
- **Cache**: Redis
- **SSL**: Let's Encrypt

## 1. Configuration du serveur

### Installer les dépendances

```bash
# Mettre à jour le système
sudo apt update && sudo apt upgrade -y

# Installer PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-redis php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-bcmath -y

# Installer Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installer MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Installer Redis
sudo apt install redis-server -y

# Installer Nginx
sudo apt install nginx -y

# Installer Supervisor (pour les queues)
sudo apt install supervisor -y
```

### Créer la base de données

```bash
sudo mysql
```

```sql
CREATE DATABASE ouagachap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ouagachap'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON ouagachap.* TO 'ouagachap'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Déployer l'application

### Cloner le repository

```bash
cd /var/www
sudo git clone https://github.com/afriklabprojet/ouagachap-backend.git ouagachap
sudo chown -R www-data:www-data ouagachap
cd ouagachap
```

### Configurer l'environnement

```bash
cp .env.example .env
nano .env
```

```env
APP_NAME="OUAGA CHAP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.ouagachap.com

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ouagachap
DB_USERNAME=ouagachap
DB_PASSWORD=strong_password_here

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (optionnel)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@ouagachap.com

# SMS (Twilio)
TWILIO_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_PHONE_NUMBER=+1234567890

# Firebase
FIREBASE_CREDENTIALS=/var/www/ouagachap/storage/firebase-credentials.json

# Jeko Payment
JEKO_API_URL=https://api.jfranco.com
JEKO_API_KEY=your_api_key
JEKO_MERCHANT_ID=your_merchant_id
```

### Installer les dépendances

```bash
composer install --optimize-autoloader --no-dev
```

### Générer la clé et migrer

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
```

### Optimiser pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### Permissions

```bash
sudo chown -R www-data:www-data /var/www/ouagachap
sudo chmod -R 755 /var/www/ouagachap
sudo chmod -R 775 /var/www/ouagachap/storage
sudo chmod -R 775 /var/www/ouagachap/bootstrap/cache
```

## 3. Configuration Nginx

```bash
sudo nano /etc/nginx/sites-available/ouagachap
```

```nginx
server {
    listen 80;
    server_name api.ouagachap.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.ouagachap.com;
    
    root /var/www/ouagachap/public;
    index index.php;
    
    # SSL (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/api.ouagachap.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.ouagachap.com/privkey.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    
    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/ouagachap /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### SSL avec Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d api.ouagachap.com
```

## 4. Configuration des queues (Supervisor)

```bash
sudo nano /etc/supervisor/conf.d/ouagachap-worker.conf
```

```ini
[program:ouagachap-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ouagachap/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ouagachap/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ouagachap-worker:*
```

## 5. WebSockets (Laravel Reverb)

### Configuration Reverb

```bash
sudo nano /etc/supervisor/conf.d/ouagachap-reverb.conf
```

```ini
[program:ouagachap-reverb]
command=php /var/www/ouagachap/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ouagachap/storage/logs/reverb.log
```

### Nginx reverse proxy pour WebSockets

```nginx
# Ajouter dans le bloc server
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

## 6. Cron jobs

```bash
sudo crontab -e -u www-data
```

```cron
* * * * * cd /var/www/ouagachap && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Monitoring

### Logs

```bash
# Logs Laravel
tail -f /var/www/ouagachap/storage/logs/laravel.log

# Logs Nginx
tail -f /var/log/nginx/error.log

# Logs Supervisor
tail -f /var/www/ouagachap/storage/logs/worker.log
```

### Health check endpoint

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'error',
        'redis' => Redis::ping() ? 'connected' : 'error',
        'queue' => Queue::size() . ' jobs pending',
    ]);
});
```

## 8. Mise à jour (Deploy)

### Script de déploiement

```bash
#!/bin/bash
# deploy.sh

cd /var/www/ouagachap

# Mettre en maintenance
php artisan down

# Pull les changements
git pull origin main

# Installer les dépendances
composer install --optimize-autoloader --no-dev

# Migrer
php artisan migrate --force

# Clear et recache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Redémarrer les workers
sudo supervisorctl restart ouagachap-worker:*

# Sortir de maintenance
php artisan up

echo "Deployment completed!"
```

```bash
chmod +x deploy.sh
./deploy.sh
```

## 9. Backups

### Script de backup

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/ouagachap"

# Créer le dossier
mkdir -p $BACKUP_DIR

# Backup base de données
mysqldump -u ouagachap -p ouagachap > $BACKUP_DIR/db_$DATE.sql

# Backup fichiers uploadés
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/ouagachap/storage/app

# Supprimer les backups > 7 jours
find $BACKUP_DIR -type f -mtime +7 -delete
```

### Cron pour backup quotidien

```cron
0 2 * * * /var/www/ouagachap/backup.sh >> /var/log/backup.log 2>&1
```

## 10. Checklist de déploiement

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Clé d'application générée
- [ ] Base de données configurée
- [ ] Redis configuré
- [ ] SSL activé
- [ ] Queues configurées (Supervisor)
- [ ] Cron configuré
- [ ] Firebase credentials en place
- [ ] Twilio configuré
- [ ] Jeko configuré
- [ ] Backups automatiques
- [ ] Monitoring en place
