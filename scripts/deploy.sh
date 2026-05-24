#!/bin/bash
# =============================================================================
# OUAGA CHAP — Déploiement manuel API-only
# Usage: script de référence pour un shell déjà ouvert sur le VPS.
# Le flux standard reste: bash scripts/push_api.sh depuis le poste local.
# =============================================================================
set -euo pipefail

APP_DIR="/opt/ouagachap"
VPS_IP="204.168.212.156"
ACTION="${1:-first}"

cd "$APP_DIR"

echo "============================================="
echo "  OUAGA CHAP — Déploiement [$ACTION]"
echo "============================================="

# Vérifier que .env existe
if [ ! -f ./api/.env ]; then
  echo "❌ Fichier ./api/.env manquant !"
  echo "   Copiez api/.env.production vers api/.env et adaptez les valeurs."
  exit 1
fi

if [ "$ACTION" = "first" ]; then
  echo "[1/5] Build image API..."
  docker compose build --no-cache api

  echo "[2/5] Démarrage des services..."
  docker compose up -d

  echo "[3/5] Attente que MySQL soit prêt (max 60s)..."
  for i in $(seq 1 12); do
    docker compose exec mysql mysqladmin ping -u root -p"$(grep DB_ROOT_PASSWORD api/.env | cut -d= -f2)" --silent 2>/dev/null && break
    echo "  Tentative $i/12..."
    sleep 5
  done

  echo "[4/5] Migrations et configuration..."
  docker compose exec api php artisan migrate --force
  docker compose exec api php artisan db:seed --class=PermissionSeeder --force 2>/dev/null || true
  docker compose exec api php artisan storage:link 2>/dev/null || true
  docker compose exec api php artisan config:cache
  docker compose exec api php artisan route:cache
  docker compose exec api php artisan view:cache

else
  echo "[1/4] Mise à jour de l'image API..."
  docker compose build api

  echo "[2/4] Redémarrage avec zéro downtime..."
  docker compose up -d

  echo "[3/4] Nettoyage fichiers macOS parasites (._*)..."
  FOUND=$(find api/app/ api/routes/ -name "._*" 2>/dev/null | wc -l | tr -d ' ')
  if [ "$FOUND" -gt 0 ]; then
    echo "  ⚠  $FOUND fichier(s) macOS détecté(s) → suppression"
    find api/ -name "._*" -delete
    find api/ -name ".DS_Store" -delete
    echo "  ✅ Supprimés"
  else
    echo "  ✅ Aucun fichier macOS parasite"
  fi

  echo "[4/4] Migrations..."
  docker compose exec api php artisan migrate --force
  docker compose exec api php artisan config:cache
  docker compose exec api php artisan route:cache
  docker compose exec api php artisan filament:cache-components 2>/dev/null || true
fi

echo "[Final] Vérification santé API..."
sleep 5
STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" https://ouagachap.pro/api/v1/health)
if [ "$STATUS" = "200" ]; then
  echo "✅ API répond HTTP 200"
else
  echo "⚠ API répond HTTP $STATUS — vérifiez: docker compose logs api"
fi

echo ""
echo "============================================="
echo "  ✅ Déploiement terminé !"
echo "  API: https://ouagachap.pro/api/v1/health"
echo "  Logs: docker compose logs -f api"
echo "  Status: docker compose ps"
echo "============================================="
