#!/bin/bash
# =============================================================================
# OUAGA CHAP — Rotation des secrets et redéploiement API
#
# Usage:
#   bash scripts/rotate_secrets.sh
#
# Ce script :
#   1. Vérifie que les secrets ont été mis à jour localement dans api/.env.production
#   2. Transfert api/.env.production vers /opt/ouagachap/api/.env sur le VPS
#   3. Exécute : cd /opt/ouagachap && docker compose up -d --force-recreate api
#   4. Lance les migrations et vide les caches
#   5. Vérifie que l'API répond correctement
#
# PRÉREQUIS :
#   - Clé SSH deploy dans ~/.ssh/ouagachap_deploy
#   - Avoir édité api/.env.production avec les vraies valeurs de production
#   - SENTRY_LARAVEL_DSN, GOOGLE_MAPS_API_KEY, INFOBIP_API_KEY remplis
# =============================================================================

set -euo pipefail

VPS_IP="204.168.212.156"
SSH_KEY="$HOME/.ssh/ouagachap_deploy"
REMOTE_USER="deploy"
REMOTE_DIR="/opt/ouagachap"
LOCAL_ENV="$(dirname "$0")/../api/.env.production"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "============================================================="
echo "  OUAGA CHAP — Rotation des secrets + redémarrage API"
echo "============================================================="

# ─── 1. Vérifications locales ─────────────────────────────────────────────────

echo ""
echo "[1/6] Vérification du fichier .env.production local..."

if [ ! -f "$LOCAL_ENV" ]; then
  echo -e "${RED}❌ Fichier introuvable : $LOCAL_ENV${NC}"
  exit 1
fi

# Vérifier qu'aucun placeholder CHANGE_ME ne subsiste
PLACEHOLDERS=$(grep -c "CHANGE_ME\|your_.*_here\|CHANGE_ME" "$LOCAL_ENV" 2>/dev/null || true)
if [ "$PLACEHOLDERS" -gt 0 ]; then
  echo -e "${YELLOW}⚠  $PLACEHOLDERS placeholder(s) CHANGE_ME détecté(s) dans .env.production :${NC}"
  grep "CHANGE_ME\|your_.*_here" "$LOCAL_ENV" | head -10
  echo ""
  read -rp "Continuer quand même ? (yes/no) : " CONFIRM
  if [ "$CONFIRM" != "yes" ]; then
    echo "Abandon."
    exit 1
  fi
fi

# Vérifier APP_DEBUG=false
if grep -q "APP_DEBUG=true" "$LOCAL_ENV"; then
  echo -e "${RED}❌ APP_DEBUG=true trouvé dans .env.production — dangereux en production !${NC}"
  exit 1
fi

# Vérifier CORS non wildcard
if grep -q "CORS_ALLOWED_ORIGINS=\*" "$LOCAL_ENV"; then
  echo -e "${RED}❌ CORS_ALLOWED_ORIGINS=* trouvé — ne jamais utiliser * en production !${NC}"
  exit 1
fi

# Vérifier SESSION_ENCRYPT
if grep -q "SESSION_ENCRYPT=false" "$LOCAL_ENV"; then
  echo -e "${RED}❌ SESSION_ENCRYPT=false — sessions non chiffrées en production !${NC}"
  exit 1
fi

echo -e "${GREEN}✅ Vérifications locales OK${NC}"

# ─── 2. Connexion SSH test ─────────────────────────────────────────────────────

echo ""
echo "[2/6] Test de connexion SSH vers $REMOTE_USER@$VPS_IP..."

if ! ssh -i "$SSH_KEY" -o ConnectTimeout=10 -o BatchMode=yes "$REMOTE_USER@$VPS_IP" "echo OK" > /dev/null 2>&1; then
  echo -e "${RED}❌ Impossible de se connecter à $REMOTE_USER@$VPS_IP avec la clé $SSH_KEY${NC}"
  echo "   Vérifiez que la clé est ajoutée : ssh-add $SSH_KEY"
  exit 1
fi

echo -e "${GREEN}✅ Connexion SSH OK${NC}"

# ─── 3. Sauvegarde de l'ancien .env sur le serveur ────────────────────────────

echo ""
echo "[3/6] Sauvegarde de l'ancien .env sur le serveur..."

ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<REMOTE
  if [ -f $REMOTE_DIR/api/.env ]; then
    cp $REMOTE_DIR/api/.env $REMOTE_DIR/api/.env.backup_\$(date +%Y%m%d_%H%M%S)
    echo "  → Sauvegarde créée : $REMOTE_DIR/api/.env.backup_\$(date +%Y%m%d_%H%M%S)"
  else
    echo "  → Aucun .env existant à sauvegarder"
  fi
REMOTE

echo -e "${GREEN}✅ Sauvegarde OK${NC}"

# ─── 4. Transfert du nouveau .env sur le VPS ──────────────────────────────────

echo ""
echo "[4/6] Transfert de api/.env.production → $REMOTE_DIR/api/.env..."

scp -i "$SSH_KEY" "$LOCAL_ENV" "$REMOTE_USER@$VPS_IP:$REMOTE_DIR/api/.env"
ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" "chmod 600 $REMOTE_DIR/api/.env"

# Générer $REMOTE_DIR/.env (racine docker-compose) avec les secrets partagés
# extraits de api/.env pour que les services mysql/redis les lisent aussi.
DB_PASS=$(grep '^DB_PASSWORD=' "$LOCAL_ENV" | cut -d= -f2-)
DB_ROOT_PASS=$(grep '^DB_ROOT_PASSWORD=' "$LOCAL_ENV" | cut -d= -f2-)
DB_USER=$(grep '^DB_USERNAME=' "$LOCAL_ENV" | cut -d= -f2-)
REDIS_PASS=$(grep '^REDIS_PASSWORD=' "$LOCAL_ENV" | cut -d= -f2-)
DB_NAME=$(grep '^DB_DATABASE=' "$LOCAL_ENV" | cut -d= -f2-)

# Variables S3 backup (optionnelles — laisser vides si non configuré)
BACKUP_S3_ENDPOINT=$(grep '^BACKUP_S3_ENDPOINT=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)
BACKUP_S3_BUCKET=$(grep '^BACKUP_S3_BUCKET=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)
BACKUP_S3_KEY_ID=$(grep '^BACKUP_S3_KEY_ID=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)
BACKUP_S3_SECRET=$(grep '^BACKUP_S3_SECRET=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)

# Variables monitoring (nécessaires pour monitor_health.sh qui lit le .env racine)
BETTERSTACK_HEARTBEAT_URL=$(grep '^BETTERSTACK_HEARTBEAT_URL=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)
LOGTAIL_SOURCE_TOKEN=$(grep '^LOGTAIL_SOURCE_TOKEN=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)
ALERT_WEBHOOK_URL=$(grep '^ALERT_WEBHOOK_URL=' "$LOCAL_ENV" 2>/dev/null | cut -d= -f2- || true)

ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<REMOTE_ENV
cat > $REMOTE_DIR/.env <<EOF
# Docker Compose shared secrets
DB_ROOT_PASSWORD=${DB_ROOT_PASS}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
DB_DATABASE=${DB_NAME}
REDIS_PASSWORD=${REDIS_PASS}

# Backup S3 (optionnel — Hetzner Object Storage ou AWS S3)
BACKUP_S3_ENDPOINT=${BACKUP_S3_ENDPOINT}
BACKUP_S3_BUCKET=${BACKUP_S3_BUCKET}
BACKUP_S3_KEY_ID=${BACKUP_S3_KEY_ID}
BACKUP_S3_SECRET=${BACKUP_S3_SECRET}

# Monitoring (lu par scripts/monitor_health.sh via cron)
BETTERSTACK_HEARTBEAT_URL=${BETTERSTACK_HEARTBEAT_URL}
LOGTAIL_SOURCE_TOKEN=${LOGTAIL_SOURCE_TOKEN}
ALERT_WEBHOOK_URL=${ALERT_WEBHOOK_URL}
EOF
chmod 600 $REMOTE_DIR/.env
echo "  → .env racine docker-compose créé (avec vars backup S3 + monitoring)"
REMOTE_ENV

echo -e "${GREEN}✅ Transfert OK${NC}"

# ─── 5. Recréer le conteneur API + migrations ─────────────────────────────────

echo ""
echo "[5/6] Recréation du conteneur API..."

ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<'REMOTE'
set -e
cd /opt/ouagachap

echo "  → Force-recreate des conteneurs Redis et API..."
docker compose up -d --force-recreate redis api

echo "  → Attente démarrage du conteneur (30s)..."
sleep 30

echo "  → Vérification que le conteneur est running..."
CONTAINER_STATUS=$(docker compose ps api --format json 2>/dev/null | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('State','unknown'))" 2>/dev/null || docker compose ps api | grep -c "Up" || echo "0")
if [ "$CONTAINER_STATUS" = "0" ]; then
  echo "⚠  Conteneur API non démarré — vérifiez les logs :"
  docker compose logs api --tail=50
  exit 1
fi

echo "  → Vider les anciens caches (les nouveaux secrets changent le config cache)..."
docker compose exec -T api php artisan optimize:clear --no-interaction 2>/dev/null || true

echo "  → Migrations..."
docker compose exec -T api php artisan migrate --force --no-interaction

echo "  → Reconstruire tous les caches..."
docker compose exec -T api php artisan config:cache
docker compose exec -T api php artisan route:cache
docker compose exec -T api php artisan view:cache
docker compose exec -T api php artisan event:cache
docker compose exec -T api php artisan filament:cache-components 2>/dev/null || true

echo "  → Redémarrer les workers de queue (pour prendre les nouveaux secrets)..."
docker compose exec -T api php artisan queue:restart

echo "✅ Conteneur API recréé et caches reconstruits"
REMOTE

echo -e "${GREEN}✅ Recréation API OK${NC}"

# ─── 6. Vérification finale ───────────────────────────────────────────────────

echo ""
echo "[6/6] Vérification finale de l'API..."

sleep 5

STATUS=$(curl -sk -o /dev/null -w "%{http_code}" -m 15 "https://ouagachap.pro/api/v1/health" || echo "000")

if [ "$STATUS" = "200" ]; then
  echo -e "${GREEN}✅ https://ouagachap.pro/api/v1/health → HTTP $STATUS — API opérationnelle${NC}"
else
  echo -e "${YELLOW}⚠  https://ouagachap.pro/api/v1/health → HTTP $STATUS${NC}"
  echo ""
  echo "Pour investiguer :"
  echo "  ssh -i $SSH_KEY $REMOTE_USER@$VPS_IP 'cd /opt/ouagachap && docker compose logs api --tail=50'"
fi

echo ""
echo "============================================================="
echo -e "${GREEN}  ✅ Rotation des secrets terminée !${NC}"
echo ""
echo "  IMPORTANT — Étapes manuelles restantes :"
echo ""
echo "  1. MYSQL : Changer le mot de passe DB sur le serveur :"
echo "     ssh -i $SSH_KEY $REMOTE_USER@$VPS_IP"
echo "     docker compose exec mysql mysql -u root -p"
echo "     > ALTER USER 'ouagachap'@'%' IDENTIFIED BY 'hay4F1iOQH7YPECenZpy2W8fcd3B';"
echo "     > ALTER USER 'root'@'%' IDENTIFIED BY 'APB9VS0qEtmwWAVJx9ANL2bqTuHz';"
echo "     > FLUSH PRIVILEGES;"
echo ""
echo "  2. INFOBIP : Révoquer l'ancienne clé sur https://portal.infobip.com"
echo "     Créer une nouvelle clé et mettre à jour INFOBIP_API_KEY dans .env.production"
echo "     puis relancer ce script."
echo ""
echo "  3. WHATSAPP : Révoquer le token sur Meta Business Manager"
echo "     https://business.facebook.com → Settings → System Users"
echo "     Générer un nouveau token permanent et mettre à jour WHATSAPP_CLOUD_ACCESS_TOKEN"
echo "     puis relancer ce script."
echo ""
echo "  4. GOOGLE MAPS : Révoquer l'ancienne clé sur Google Cloud Console"
echo "     Créer une nouvelle clé restreinte par IP serveur et mettre à jour GOOGLE_MAPS_API_KEY"
echo ""
echo "  5. SENTRY : Créer un projet sur sentry.io, copier le DSN dans"
echo "     SENTRY_LARAVEL_DSN= dans api/.env.production puis relancer ce script."
echo "============================================================="
