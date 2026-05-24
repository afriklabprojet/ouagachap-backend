#!/bin/bash
# =============================================================================
# OUAGA CHAP — Déploiement API depuis macOS vers VPS
# Usage: bash scripts/push_api.sh
# =============================================================================
set -euo pipefail

VPS_IP="204.168.212.156"
SSH_KEY="$HOME/.ssh/ouagachap_deploy"
REMOTE_USER="deploy"
REMOTE_DIR="/opt/ouagachap"
ARCHIVE="/tmp/ouagachap_api_$(date +%Y%m%d_%H%M%S).tar.gz"

echo "============================================="
echo "  OUAGA CHAP — Push API → VPS"
echo "============================================="

# 1. Créer l'archive de l'API uniquement (runtime/build requis seulement)
echo "[1/5] Création de l'archive API-only (sans métadonnées macOS)..."
cd "$(dirname "$0")/.."

COPYFILE_DISABLE=1 tar \
  --exclude="._*" \
  --exclude=".DS_Store" \
  -czf "$ARCHIVE" \
  docker-compose.yml \
  api/app \
  api/artisan \
  api/bootstrap \
  api/composer.json \
  api/composer.lock \
  api/config \
  api/database \
  api/docker \
  api/Dockerfile \
  api/.dockerignore \
  api/public \
  api/resources \
  api/routes

echo "   Archive : $ARCHIVE ($(du -sh "$ARCHIVE" | cut -f1))"

# 2. Upload
echo "[2/5] Upload vers le VPS..."
scp -i "$SSH_KEY" "$ARCHIVE" "$REMOTE_USER@$VPS_IP:/tmp/ouagachap_api.tar.gz"

# 3. Nettoyage VPS + extraction API-only
echo "[3/5] Nettoyage VPS et extraction API-only..."
ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<'REMOTE'
set -e
cd /opt/ouagachap

echo "  → Nettoyage racine (on garde uniquement l'API, docker-compose, .env et les fichiers de vérification domaine)..."
find . -maxdepth 1 -mindepth 1 \
  ! -name 'api' \
  ! -name 'docker-compose.yml' \
  ! -name 'docker' \
  ! -name 'storage' \
  ! -name '.env' \
  ! -name 'tax4oj4isrz1l06z0t5xagydig3zb0.html' \
  ! -name 'x4v355djp8zz07w0g6bo7cl303fjxv.html' \
  -exec rm -rf {} + 2>/dev/null || true

echo "  → Nettoyage du dossier api/ (on garde uniquement .env et storage)..."
mkdir -p api storage api/storage
find api -maxdepth 1 -mindepth 1 \
  ! -name '.env' \
  ! -name 'storage' \
  -exec rm -rf {} +

echo "  → Extraction..."

tar -xzf /tmp/ouagachap_api.tar.gz

# Sécurité : supprimer tout ._* restant (au cas où)
FOUND=$(find . -name "._*" 2>/dev/null | wc -l | tr -d ' ')
if [ "$FOUND" -gt 0 ]; then
  echo "  ⚠  $FOUND fichier(s) ._* trouvé(s), suppression..."
  find . -name "._*" -delete
  echo "  ✅ Supprimés"
else
  echo "  ✅ Aucun fichier macOS parasite"
fi

rm -f /tmp/ouagachap_api.tar.gz
echo "  → Archive temporaire supprimée"
REMOTE

# 4. Rebuild image Docker + redémarrage
echo "[4/5] Rebuild image Docker (le code est bakée dans l'image)..."
ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<'REMOTE'
set -e
cd /opt/ouagachap
echo "  → docker compose build api..."
docker compose build api
echo "  → docker compose up -d api..."
docker compose up -d api
echo "  → Attente démarrage (20s)..."
sleep 20
echo "  → Migrations production..."
docker compose exec -T api php artisan migrate --force --no-interaction
echo "  ✅ Image rebuil et container redémarré"
REMOTE

# 5. Cache Filament uniquement (l'entrypoint gère déjà config/route/view/event)
# Note: optimize:clear est intentionnellement ABSENT ici — il efface le config cache
# que l'entrypoint vient de générer, et peut causer des 500 en cas d'échec partiel.
echo "[5/5] Cache Filament components..."
ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<'REMOTE'
cd /opt/ouagachap
docker compose exec -T api php artisan filament:cache-components && echo "FILAMENT CACHE OK" || echo "⚠ filament:cache-components non disponible (ignoré)"
REMOTE

# 5. Vérification finale
echo ""
echo "[Check] Vérification HTTP..."
sleep 2
STATUS=$(curl -sk -o /dev/null -w "%{http_code}" -m 10 https://ouagachap.pro/api/v1/health)
if [ "$STATUS" = "200" ]; then
  echo "  ✅ https://ouagachap.pro/api/v1/health → HTTP $STATUS"
else
  echo "  ⚠  HTTP $STATUS — vérifiez les logs :"
  echo "  ssh -i $SSH_KEY $REMOTE_USER@$VPS_IP 'cd /opt/ouagachap && docker compose logs api --tail=30'"
fi

# Nettoyage local
rm -f "$ARCHIVE"

echo ""
echo "============================================="
echo "  ✅ Déploiement terminé !"
echo "============================================="
