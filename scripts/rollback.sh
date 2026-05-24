#!/bin/bash
# =============================================================================
# OUAGA CHAP — Rollback vers la version précédente
# =============================================================================
# Usage:
#   bash scripts/rollback.sh                    # rollback API vers image précédente
#   bash scripts/rollback.sh --list             # lister les images disponibles
#   bash scripts/rollback.sh --image sha256:abc # rollback vers une image spécifique
#   bash scripts/rollback.sh --db-only          # rollback DB uniquement (rollback migration)
#
# Prérequis:
#   - Être connecté au VPS ou exécuter depuis le VPS directement
#   - Docker et docker compose installés
#   - Se trouver dans /opt/ouagachap/
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/ouagachap}"
SSH_KEY="${HOME}/.ssh/ouagachap_deploy"
REMOTE_USER="deploy"
VPS_IP="204.168.212.156"

# Couleurs
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

log()  { echo -e "${BLUE}[ROLLBACK]${NC} $*"; }
ok()   { echo -e "${GREEN}✓${NC} $*"; }
warn() { echo -e "${YELLOW}⚠${NC} $*"; }
fail() { echo -e "${RED}✗${NC} $*" >&2; exit 1; }

ACTION="${1:-rollback}"

# ── Mode local (exécuté depuis le VPS) ou distant (depuis le poste dev) ───────
if [ -d "$APP_DIR" ] && command -v docker &>/dev/null; then
  # On est sur le VPS
  RUN_LOCAL=true
else
  # On est sur le poste de dev — tunnel SSH
  RUN_LOCAL=false
fi

# ── Lister les images disponibles ─────────────────────────────────────────────
if [ "$ACTION" = "--list" ]; then
  echo ""
  echo "Images API disponibles pour rollback :"
  echo ""
  if $RUN_LOCAL; then
    cd "$APP_DIR"
    docker images ouagachap-api --format "table {{.Tag}}\t{{.ID}}\t{{.CreatedAt}}\t{{.Size}}" 2>/dev/null || \
    docker images | grep -E "ouaga|api" | head -20
  else
    ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" \
      "cd $APP_DIR && docker images | grep -E 'ouaga|api' | head -20"
  fi
  echo ""
  echo "Pour rollback vers une image spécifique :"
  echo "  bash scripts/rollback.sh --image <IMAGE_ID>"
  exit 0
fi

# ── Rollback DB uniquement ─────────────────────────────────────────────────────
if [ "$ACTION" = "--db-only" ]; then
  warn "Rollback de la dernière migration uniquement..."
  echo ""
  read -rp "  Confirmer le rollback de migration ? (yes/no) : " CONFIRM
  [ "$CONFIRM" = "yes" ] || { echo "Abandonné."; exit 0; }

  if $RUN_LOCAL; then
    cd "$APP_DIR"
    docker compose exec -T api php artisan migrate:rollback --step=1 --force
  else
    ssh -i "$SSH_KEY" "$REMOTE_USER@$VPS_IP" \
      "cd $APP_DIR && docker compose exec -T api php artisan migrate:rollback --step=1 --force"
  fi
  ok "Migration rollback effectué"
  exit 0
fi

# ── Rollback complet ───────────────────────────────────────────────────────────
echo ""
echo "============================================================="
echo -e "  ${RED}OUAGA CHAP — ROLLBACK${NC}"
echo "============================================================="
echo ""

TARGET_IMAGE="${2:-}"

# Confirmation obligatoire
warn "Cette opération va redémarrer l'API avec la version précédente."
warn "Les requêtes en cours seront interrompues (~5 secondes)."
echo ""
read -rp "Confirmer le rollback ? (yes/no) : " CONFIRM
[ "$CONFIRM" = "yes" ] || { echo "Abandonné."; exit 0; }

# ── Exécution sur le VPS ───────────────────────────────────────────────────────
do_rollback() {
  cd "$APP_DIR"

  # 1. Identifier l'image courante
  CURRENT_IMAGE=$(docker compose images api --format json 2>/dev/null \
    | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('Image',''))" 2>/dev/null \
    || docker compose ps api | grep -oE 'sha256:[a-f0-9]+' | head -1 \
    || echo "unknown")

  log "Image courante : $CURRENT_IMAGE"

  # 2. Trouver l'image précédente si pas spécifiée
  if [ -z "$TARGET_IMAGE" ]; then
    IMAGES=$(docker images ouagachap_api --format "{{.ID}}" 2>/dev/null \
      || docker images | grep "ouagachap" | grep "api\|ouaga" | awk '{print $3}')
    PREVIOUS_IMAGE=$(echo "$IMAGES" | sed -n '2p')

    if [ -z "$PREVIOUS_IMAGE" ]; then
      # Fallback : image Git précédente via tag
      PREVIOUS_TAG=$(git log --oneline -2 api/ 2>/dev/null | tail -1 | cut -d' ' -f1 || echo "")
      if [ -n "$PREVIOUS_TAG" ]; then
        log "Tentative de rebuild depuis le commit $PREVIOUS_TAG..."
        git stash 2>/dev/null || true
        git checkout "$PREVIOUS_TAG" -- api/ 2>/dev/null || fail "Impossible de checkout $PREVIOUS_TAG"
        docker compose build --no-cache api
        PREVIOUS_IMAGE=$(docker images ouagachap_api --format "{{.ID}}" | head -1)
        git checkout HEAD -- api/ 2>/dev/null || true
        git stash pop 2>/dev/null || true
      fi
    fi

    if [ -z "$PREVIOUS_IMAGE" ]; then
      fail "Aucune image précédente trouvée. Spécifiez --image <ID>"
    fi
  else
    PREVIOUS_IMAGE="$TARGET_IMAGE"
  fi

  log "Rollback vers image : $PREVIOUS_IMAGE"

  # 3. Backup rapide de la DB avant rollback
  log "Backup DB pré-rollback..."
  bash scripts/backup_db.sh 2>/dev/null \
    && ok "Backup DB effectué" \
    || warn "Backup DB échoué — rollback quand même (risque: perte si migration incompatible)"

  # 4. Mettre à jour docker-compose pour utiliser l'image précédente
  log "Arrêt de l'API actuelle..."
  docker compose stop api

  # 5. Forcer docker compose à utiliser l'image précédente
  # En taguant l'image précédente comme 'latest'
  docker tag "$PREVIOUS_IMAGE" ouagachap_api:rollback 2>/dev/null || true

  log "Redémarrage avec l'image précédente..."
  COMPOSE_API_IMAGE="$PREVIOUS_IMAGE" docker compose up -d api

  sleep 15

  # 6. Rollback de la dernière migration si nécessaire
  echo ""
  read -rp "  Rollback de la dernière migration également ? (yes/no) : " MIGRATE_ROLLBACK
  if [ "$MIGRATE_ROLLBACK" = "yes" ]; then
    docker compose exec -T api php artisan migrate:rollback --step=1 --force
    ok "Migration rollback effectué"
  fi

  # 7. Vider les caches
  docker compose exec -T api php artisan optimize:clear --no-interaction 2>/dev/null || true
  docker compose exec -T api php artisan config:cache
  docker compose exec -T api php artisan route:cache

  # 8. Vérification
  sleep 5
  STATUS=$(curl -sk -o /dev/null -w "%{http_code}" -m 10 "https://ouagachap.pro/api/v1/health" || echo "000")

  if [ "$STATUS" = "200" ]; then
    ok "API opérationnelle après rollback (HTTP $STATUS)"
  else
    warn "API répond HTTP $STATUS — vérifiez les logs :"
    docker compose logs api --tail=50
  fi

  echo ""
  echo "============================================================="
  echo -e "${GREEN}  ✅ Rollback terminé${NC}"
  echo "============================================================="
  echo ""
  echo "  Image rollback : $PREVIOUS_IMAGE"
  echo "  Image précédente (pour re-deploy) : $CURRENT_IMAGE"
  echo ""
  echo "  Pour re-déployer la version courante :"
  echo "    bash scripts/push_api.sh"
  echo ""
  echo "  Pour inspecter les logs :"
  echo "    docker compose logs api --tail=100 -f"
}

if $RUN_LOCAL; then
  do_rollback
else
  # Exécuter le rollback sur le VPS via SSH
  log "Connexion SSH vers $REMOTE_USER@$VPS_IP..."
  ssh -i "$SSH_KEY" -t "$REMOTE_USER@$VPS_IP" \
    "APP_DIR=$APP_DIR bash $APP_DIR/scripts/rollback.sh $ACTION ${TARGET_IMAGE:-}"
fi
