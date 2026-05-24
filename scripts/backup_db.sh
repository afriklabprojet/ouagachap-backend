#!/bin/bash
# =============================================================================
# OUAGA CHAP — Backup MySQL automatique
# =============================================================================
# Usage (manuel)  : bash scripts/backup_db.sh
# Usage (cron VPS): 0 2 * * * /opt/ouagachap/scripts/backup_db.sh >> /var/log/ouagachap-backup.log 2>&1
#
# Ce script :
#   1. Dump MySQL via le conteneur Docker
#   2. Compresse le dump (.sql.gz)
#   3. Conserve les 14 derniers backups locaux
#   4. Upload vers Hetzner Object Storage (S3-compatible) si configuré
#
# Variables requises dans /opt/ouagachap/.env (root docker-compose) :
#   DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_ROOT_PASSWORD
#   BACKUP_S3_ENDPOINT, BACKUP_S3_BUCKET, BACKUP_S3_KEY_ID, BACKUP_S3_SECRET  (optionnel)
# =============================================================================
set -euo pipefail

# ── Configuration ─────────────────────────────────────────────────────────────
APP_DIR="${APP_DIR:-/opt/ouagachap}"
BACKUP_DIR="${BACKUP_DIR:-/backups/ouagachap}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="ouagachap_${DATE}.sql.gz"
LOG_PREFIX="[BACKUP $(date '+%Y-%m-%d %H:%M:%S')]"

# Couleurs (désactivées si pas de terminal)
if [ -t 1 ]; then
  GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
else
  GREEN=''; YELLOW=''; RED=''; NC=''
fi

# ── Fonctions ─────────────────────────────────────────────────────────────────
log()  { echo -e "${LOG_PREFIX} $*"; }
ok()   { echo -e "${LOG_PREFIX} ${GREEN}✓${NC} $*"; }
warn() { echo -e "${LOG_PREFIX} ${YELLOW}⚠${NC} $*"; }
fail() { echo -e "${LOG_PREFIX} ${RED}✗${NC} $*" >&2; exit 1; }

# ── Vérifications préalables ──────────────────────────────────────────────────
cd "$APP_DIR" || fail "Répertoire $APP_DIR introuvable"

if [ ! -f .env ]; then
  fail ".env absent de $APP_DIR — exécutez d'abord bash scripts/rotate_secrets.sh"
fi

# Charger les variables d'environnement (DB_PASSWORD, DB_USERNAME, etc.)
set -a
# shellcheck disable=SC1091
source .env
set +a

DB_NAME="${DB_DATABASE:-ouaga_chap}"
DB_USER="${DB_USERNAME:-ouagachap}"
DB_PASS="${DB_PASSWORD:-}"
DB_ROOT_PASS="${DB_ROOT_PASSWORD:-}"

if [ -z "$DB_PASS" ] && [ -z "$DB_ROOT_PASS" ]; then
  fail "DB_PASSWORD et DB_ROOT_PASSWORD tous les deux vides dans .env"
fi

# Préférer le user applicatif, fallback root
if [ -n "$DB_PASS" ]; then
  MYSQL_USER="$DB_USER"
  MYSQL_PASS="$DB_PASS"
else
  warn "DB_PASSWORD absent — utilisation de root"
  MYSQL_USER="root"
  MYSQL_PASS="$DB_ROOT_PASS"
fi

# ── Création du répertoire de backup ─────────────────────────────────────────
mkdir -p "$BACKUP_DIR"

# ── Vérifier que le conteneur MySQL tourne ────────────────────────────────────
if ! docker compose ps mysql 2>/dev/null | grep -q "running\|Up"; then
  fail "Conteneur MySQL non démarré — impossible de faire le backup"
fi

# ── Dump MySQL ────────────────────────────────────────────────────────────────
log "Démarrage du backup de '$DB_NAME'..."

BACKUP_PATH="${BACKUP_DIR}/${BACKUP_FILE}"

docker compose exec -T mysql mysqldump \
  --user="$MYSQL_USER" \
  --password="$MYSQL_PASS" \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --set-gtid-purged=OFF \
  "$DB_NAME" | gzip -9 > "$BACKUP_PATH"

if [ ! -s "$BACKUP_PATH" ]; then
  fail "Backup vide ou échec mysqldump — vérifiez les logs Docker"
fi

BACKUP_SIZE=$(du -sh "$BACKUP_PATH" | cut -f1)
ok "Backup créé : $BACKUP_PATH ($BACKUP_SIZE)"

# ── Upload S3 (optionnel — Hetzner Object Storage ou AWS S3) ─────────────────
S3_ENDPOINT="${BACKUP_S3_ENDPOINT:-}"
S3_BUCKET="${BACKUP_S3_BUCKET:-}"
S3_KEY_ID="${BACKUP_S3_KEY_ID:-}"
S3_SECRET="${BACKUP_S3_SECRET:-}"

if [ -n "$S3_ENDPOINT" ] && [ -n "$S3_BUCKET" ] && [ -n "$S3_KEY_ID" ]; then
  if command -v aws &>/dev/null; then
    log "Upload vers S3 : s3://$S3_BUCKET/backups/$BACKUP_FILE"
    AWS_ACCESS_KEY_ID="$S3_KEY_ID" \
    AWS_SECRET_ACCESS_KEY="$S3_SECRET" \
    aws s3 cp "$BACKUP_PATH" "s3://$S3_BUCKET/backups/$BACKUP_FILE" \
      --endpoint-url "$S3_ENDPOINT" \
      --quiet
    ok "Upload S3 réussi"
  elif command -v rclone &>/dev/null; then
    log "Upload via rclone : ouagachap-s3:$S3_BUCKET/backups/$BACKUP_FILE"
    rclone copy "$BACKUP_PATH" "ouagachap-s3:$S3_BUCKET/backups/" --quiet
    ok "Upload rclone réussi"
  else
    warn "S3 configuré mais ni 'aws' ni 'rclone' n'est installé — upload ignoré"
    warn "  Installer : apt install awscli  ou  https://rclone.org/install/"
  fi
else
  warn "Backup S3 non configuré (BACKUP_S3_ENDPOINT/BUCKET/KEY_ID non définis)"
  warn "  Données conservées localement uniquement — recommandé : configurer S3"
fi

# ── Rotation : supprimer les backups > RETENTION_DAYS jours ──────────────────
log "Rotation : suppression des backups de plus de ${RETENTION_DAYS} jours..."
DELETED=$(find "$BACKUP_DIR" -name "ouagachap_*.sql.gz" -mtime "+${RETENTION_DAYS}" -print -delete | wc -l)
if [ "$DELETED" -gt 0 ]; then
  ok "$DELETED ancien(s) backup(s) supprimé(s)"
fi

# ── Résumé ────────────────────────────────────────────────────────────────────
TOTAL=$(find "$BACKUP_DIR" -name "ouagachap_*.sql.gz" | wc -l)
ok "Backup terminé. Total backups locaux : $TOTAL"
echo ""
echo "  Fichier  : $BACKUP_PATH"
echo "  Taille   : $BACKUP_SIZE"
echo "  Rétention: ${RETENTION_DAYS} jours"
echo ""
echo "Pour restaurer :"
echo "  gunzip -c $BACKUP_PATH | docker compose exec -T mysql mysql -u$MYSQL_USER -p'<PASS>' $DB_NAME"
