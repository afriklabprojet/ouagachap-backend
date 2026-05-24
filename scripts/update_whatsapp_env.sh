#!/bin/bash
# =============================================================================
# OUAGA CHAP — Mise à jour des credentials WhatsApp sur VPS + cPanel
# Usage: bash scripts/update_whatsapp_env.sh
# =============================================================================
set -euo pipefail

VPS_IP="204.168.212.156"
SSH_KEY="$HOME/.ssh/ouagachap_deploy"
REMOTE_USER="deploy"
REMOTE_DIR="/opt/ouagachap"

LOCAL_ENV_PRODUCTION="$(dirname "$0")/../api/.env.production"
LOCAL_ENV_CPANEL="$(dirname "$0")/../api/.env.cpanel"

echo "============================================="
echo "  OUAGA CHAP — Mise à jour WhatsApp OTP"
echo "============================================="

# -----------------------------------------------------------------------
# 1. Vérifier que la clé SSH existe
# -----------------------------------------------------------------------
if [ ! -f "$SSH_KEY" ]; then
  echo "❌ Clé SSH introuvable : $SSH_KEY"
  echo "   Adaptez la variable SSH_KEY dans ce script."
  exit 1
fi

# -----------------------------------------------------------------------
# 2. Upload .env.production → VPS (remplace api/.env sur le serveur)
# -----------------------------------------------------------------------
echo ""
echo "[1/3] Upload de .env.production → VPS (api/.env)..."
scp -i "$SSH_KEY" "$LOCAL_ENV_PRODUCTION" "$REMOTE_USER@$VPS_IP:$REMOTE_DIR/api/.env"
echo "  ✅ Fichier envoyé"

# -----------------------------------------------------------------------
# 3. Vider le cache de config dans le container Docker
# -----------------------------------------------------------------------
echo ""
echo "[2/3] Nettoyage du cache sur le VPS..."
ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" bash <<'REMOTE'
set -e
cd /opt/ouagachap
echo "  → config:clear..."
docker compose exec -T api php artisan config:clear
echo "  → cache:clear..."
docker compose exec -T api php artisan cache:clear
echo "  → config:cache (rechargement)..."
docker compose exec -T api php artisan config:cache
echo "  ✅ Cache rechargé"
REMOTE

# -----------------------------------------------------------------------
# 4. Vérification rapide du nouveau token WhatsApp via l'API Meta
# -----------------------------------------------------------------------
echo ""
echo "[3/3] Vérification du nouveau token WhatsApp..."
NEW_TOKEN=$(grep "^WHATSAPP_CLOUD_ACCESS_TOKEN=" "$LOCAL_ENV_PRODUCTION" | cut -d= -f2-)
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" \
  "https://graph.facebook.com/v23.0/me?access_token=${NEW_TOKEN}" --max-time 10)

if [ "$RESPONSE" = "200" ]; then
  echo "  ✅ Token WhatsApp VALIDE (HTTP 200)"
else
  echo "  ⚠  Token WhatsApp → HTTP $RESPONSE (vérifiez le token dans Meta Business)"
fi

# -----------------------------------------------------------------------
# INSTRUCTIONS cPanel (manuel — pas de SSH disponible sur cPanel standard)
# -----------------------------------------------------------------------
echo ""
echo "============================================="
echo "  ⚠  ACTION MANUELLE REQUISE — cPanel"
echo "============================================="
echo ""
echo "  1. Connectez-vous à : https://ouagachap.com/cpanel"
echo "  2. Ouvrez : Gestionnaire de fichiers → public_html/api/ (ou votre dossier Laravel)"
echo "  3. Remplacez le fichier .env par le contenu de :"
echo "       $(realpath "$LOCAL_ENV_CPANEL")"
echo "  4. Dans cPanel → Terminal (ou SSH) :"
echo "       php artisan config:clear && php artisan cache:clear"
echo ""
echo "============================================="
echo "  ✅ VPS mis à jour — cPanel : action manuelle"
echo "============================================="
