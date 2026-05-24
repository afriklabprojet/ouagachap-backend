#!/bin/bash
# =============================================================================
# OUAGA CHAP — Monitoring santé système + alertes
# =============================================================================
# Usage:
#   bash scripts/monitor_health.sh              # check unique
#   bash scripts/monitor_health.sh --watch      # boucle toutes les 60s
#   bash scripts/monitor_health.sh --report     # rapport JSON vers Betterstack
#
# Cron recommandé (toutes les 5 min) :
#   */5 * * * * /opt/ouagachap/scripts/monitor_health.sh --report >> /var/log/ouagachap-monitor.log 2>&1
#
# Variables requises dans /opt/ouagachap/.env :
#   BETTERSTACK_HEARTBEAT_URL  — URL du heartbeat monitor (Better Stack → Monitors)
#   LOGTAIL_SOURCE_TOKEN       — pour envoyer les métriques dans les logs
#   ALERT_WEBHOOK_URL          — (optionnel) URL webhook Slack/Discord pour alertes
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/ouagachap}"
LOG_PREFIX="[MONITOR $(date '+%Y-%m-%d %H:%M:%S')]"
ACTION="${1:---check}"

# Seuils d'alerte
CPU_WARN_THRESHOLD=70      # % CPU
CPU_CRIT_THRESHOLD=90
MEM_WARN_THRESHOLD=75      # % RAM
MEM_CRIT_THRESHOLD=90
DISK_WARN_THRESHOLD=80     # % disque
DISK_CRIT_THRESHOLD=95
API_RESPONSE_WARN_MS=2000  # ms
API_RESPONSE_CRIT_MS=5000

# Couleurs
if [ -t 1 ]; then
  GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
else
  GREEN=''; YELLOW=''; RED=''; NC=''
fi

# ── Charger .env ──────────────────────────────────────────────────────────────
if [ -f "$APP_DIR/.env" ]; then
  set -a
  # shellcheck disable=SC1091
  source "$APP_DIR/.env" 2>/dev/null || true
  set +a
fi

BETTERSTACK_HEARTBEAT_URL="${BETTERSTACK_HEARTBEAT_URL:-}"
LOGTAIL_SOURCE_TOKEN="${LOGTAIL_SOURCE_TOKEN:-}"
ALERT_WEBHOOK_URL="${ALERT_WEBHOOK_URL:-}"

# ── Collecter les métriques système ───────────────────────────────────────────
collect_metrics() {
  # CPU (moyenne 1 min)
  CPU_USAGE=$(awk '{print int($1 * 100 / $(nproc --all 2>/dev/null || echo 1))}' \
    /proc/loadavg 2>/dev/null || echo "0")
  # Alternative plus précise
  CPU_USAGE=$(top -bn1 2>/dev/null | grep "Cpu(s)" | awk '{print int($2)}' || echo "$CPU_USAGE")

  # RAM
  MEM_INFO=$(free 2>/dev/null || echo "")
  if [ -n "$MEM_INFO" ]; then
    MEM_TOTAL=$(echo "$MEM_INFO" | awk '/^Mem:/{print $2}')
    MEM_USED=$(echo "$MEM_INFO"  | awk '/^Mem:/{print $3}')
    MEM_USAGE=$(awk "BEGIN{print int($MEM_USED * 100 / $MEM_TOTAL)}")
    MEM_USED_MB=$((MEM_USED / 1024))
    MEM_TOTAL_MB=$((MEM_TOTAL / 1024))
  else
    MEM_USAGE=0; MEM_USED_MB=0; MEM_TOTAL_MB=0
  fi

  # Disque (partition principale)
  DISK_USAGE=$(df / 2>/dev/null | awk 'NR==2{print int($5)}' || echo "0")
  DISK_FREE_GB=$(df -BG / 2>/dev/null | awk 'NR==2{print $4}' | tr -d 'G' || echo "0")

  # Conteneurs Docker
  API_RUNNING=$(docker compose -f "$APP_DIR/docker-compose.yml" ps api 2>/dev/null | grep -c "Up\|running" || echo "0")
  MYSQL_RUNNING=$(docker compose -f "$APP_DIR/docker-compose.yml" ps mysql 2>/dev/null | grep -c "Up\|running" || echo "0")
  REDIS_RUNNING=$(docker compose -f "$APP_DIR/docker-compose.yml" ps redis 2>/dev/null | grep -c "Up\|running" || echo "0")

  # Réponse API
  API_START=$(date +%s%3N)
  API_STATUS=$(curl -sk -o /dev/null -w "%{http_code}" -m 10 "https://ouagachap.pro/api/v1/health" 2>/dev/null || echo "000")
  API_DURATION=$(($(date +%s%3N) - API_START))

  # Queue Redis — jobs en attente
  QUEUE_PENDING=0
  if [ "$REDIS_RUNNING" = "1" ]; then
    QUEUE_PENDING=$(docker compose -f "$APP_DIR/docker-compose.yml" exec -T redis \
      redis-cli -a "${REDIS_PASSWORD:-}" llen "queues:default" 2>/dev/null || echo "0")
    QUEUE_FAILED=$(docker compose -f "$APP_DIR/docker-compose.yml" exec -T redis \
      redis-cli -a "${REDIS_PASSWORD:-}" llen "queues:failed" 2>/dev/null || echo "0")
  else
    QUEUE_FAILED=0
  fi
}

# ── Évaluer le niveau d'alerte ────────────────────────────────────────────────
evaluate_alerts() {
  ALERTS=()
  ALERT_LEVEL="ok"  # ok | warn | critical

  # CPU
  if [ "$CPU_USAGE" -ge "$CPU_CRIT_THRESHOLD" ] 2>/dev/null; then
    ALERTS+=("CPU CRITIQUE: ${CPU_USAGE}% (seuil: ${CPU_CRIT_THRESHOLD}%)")
    ALERT_LEVEL="critical"
  elif [ "$CPU_USAGE" -ge "$CPU_WARN_THRESHOLD" ] 2>/dev/null; then
    ALERTS+=("CPU AVERTISSEMENT: ${CPU_USAGE}% (seuil: ${CPU_WARN_THRESHOLD}%)")
    [ "$ALERT_LEVEL" = "ok" ] && ALERT_LEVEL="warn"
  fi

  # RAM
  if [ "$MEM_USAGE" -ge "$MEM_CRIT_THRESHOLD" ] 2>/dev/null; then
    ALERTS+=("RAM CRITIQUE: ${MEM_USAGE}% (${MEM_USED_MB}MB / ${MEM_TOTAL_MB}MB)")
    ALERT_LEVEL="critical"
  elif [ "$MEM_USAGE" -ge "$MEM_WARN_THRESHOLD" ] 2>/dev/null; then
    ALERTS+=("RAM AVERTISSEMENT: ${MEM_USAGE}% (${MEM_USED_MB}MB / ${MEM_TOTAL_MB}MB)")
    [ "$ALERT_LEVEL" = "ok" ] && ALERT_LEVEL="warn"
  fi

  # Disque
  if [ "$DISK_USAGE" -ge "$DISK_CRIT_THRESHOLD" ] 2>/dev/null; then
    ALERTS+=("DISQUE CRITIQUE: ${DISK_USAGE}% utilisé (${DISK_FREE_GB}GB libre)")
    ALERT_LEVEL="critical"
  elif [ "$DISK_USAGE" -ge "$DISK_WARN_THRESHOLD" ] 2>/dev/null; then
    ALERTS+=("DISQUE AVERTISSEMENT: ${DISK_USAGE}% utilisé")
    [ "$ALERT_LEVEL" = "ok" ] && ALERT_LEVEL="warn"
  fi

  # Conteneurs
  [ "$API_RUNNING" != "1" ] && { ALERTS+=("CONTENEUR API DOWN"); ALERT_LEVEL="critical"; }
  [ "$MYSQL_RUNNING" != "1" ] && { ALERTS+=("CONTENEUR MYSQL DOWN"); ALERT_LEVEL="critical"; }
  [ "$REDIS_RUNNING" != "1" ] && { ALERTS+=("CONTENEUR REDIS DOWN"); ALERT_LEVEL="critical"; }

  # API response
  if [ "$API_STATUS" != "200" ]; then
    ALERTS+=("API hors service: HTTP $API_STATUS")
    ALERT_LEVEL="critical"
  elif [ "$API_DURATION" -ge "$API_RESPONSE_CRIT_MS" ] 2>/dev/null; then
    ALERTS+=("API lente CRITIQUE: ${API_DURATION}ms (seuil: ${API_RESPONSE_CRIT_MS}ms)")
    ALERT_LEVEL="critical"
  elif [ "$API_DURATION" -ge "$API_RESPONSE_WARN_MS" ] 2>/dev/null; then
    ALERTS+=("API lente: ${API_DURATION}ms")
    [ "$ALERT_LEVEL" = "ok" ] && ALERT_LEVEL="warn"
  fi

  # Jobs échoués
  if [ "$QUEUE_FAILED" -gt 0 ] 2>/dev/null; then
    ALERTS+=("QUEUE: ${QUEUE_FAILED} job(s) en échec")
    [ "$ALERT_LEVEL" = "ok" ] && ALERT_LEVEL="warn"
  fi
}

# ── Afficher le rapport ───────────────────────────────────────────────────────
print_report() {
  local color="$GREEN"
  [ "$ALERT_LEVEL" = "warn" ]     && color="$YELLOW"
  [ "$ALERT_LEVEL" = "critical" ] && color="$RED"

  echo ""
  echo "${color}══════════════════════════════════════════${NC}"
  echo "${color}  OUAGA CHAP — Santé système${NC}"
  echo "${color}══════════════════════════════════════════${NC}"
  echo ""
  printf "  CPU        : %3d%%\n" "$CPU_USAGE"
  printf "  RAM        : %3d%% (%dMB / %dMB)\n" "$MEM_USAGE" "$MEM_USED_MB" "$MEM_TOTAL_MB"
  printf "  Disque     : %3d%% (%sGB libres)\n" "$DISK_USAGE" "$DISK_FREE_GB"
  echo "  API        : HTTP $API_STATUS (${API_DURATION}ms)"
  echo "  Conteneurs : API=$API_RUNNING MySQL=$MYSQL_RUNNING Redis=$REDIS_RUNNING"
  echo "  Queue      : ${QUEUE_PENDING} en attente, ${QUEUE_FAILED} échecs"
  echo ""

  if [ ${#ALERTS[@]} -gt 0 ]; then
    echo "  ${RED}ALERTES :${NC}"
    for alert in "${ALERTS[@]}"; do
      echo "    ${RED}⚠ $alert${NC}"
    done
  else
    echo "  ${GREEN}✓ Tous les indicateurs sont normaux${NC}"
  fi
  echo ""
}

# ── Envoyer le heartbeat Betterstack ─────────────────────────────────────────
send_heartbeat() {
  if [ -n "$BETTERSTACK_HEARTBEAT_URL" ] && [ "$ALERT_LEVEL" = "ok" ]; then
    curl -sk "$BETTERSTACK_HEARTBEAT_URL" -o /dev/null --max-time 5 || true
  fi
}

# ── Envoyer les métriques vers Logtail ───────────────────────────────────────
send_to_logtail() {
  if [ -z "$LOGTAIL_SOURCE_TOKEN" ]; then return; fi

  local level="info"
  [ "$ALERT_LEVEL" = "warn" ]     && level="warning"
  [ "$ALERT_LEVEL" = "critical" ] && level="error"

  local payload
  payload=$(cat <<JSON
{
  "dt": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "level": "$level",
  "message": "System health check: $ALERT_LEVEL",
  "channel": "monitor",
  "app": "OUAGA CHAP",
  "env": "production",
  "ctx_cpu_pct": $CPU_USAGE,
  "ctx_mem_pct": $MEM_USAGE,
  "ctx_disk_pct": $DISK_USAGE,
  "ctx_api_status": "$API_STATUS",
  "ctx_api_ms": $API_DURATION,
  "ctx_queue_pending": $QUEUE_PENDING,
  "ctx_queue_failed": $QUEUE_FAILED,
  "ctx_alert_level": "$ALERT_LEVEL",
  "ctx_alerts": $([ ${#ALERTS[@]} -gt 0 ] && printf '"%s"' "${ALERTS[*]}" || echo '""')
}
JSON
)

  curl -s -X POST "https://in.logs.betterstack.com" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $LOGTAIL_SOURCE_TOKEN" \
    -d "$payload" \
    --max-time 5 \
    -o /dev/null || true
}

# ── Envoyer une alerte webhook (Slack / Discord) ──────────────────────────────
send_webhook_alert() {
  if [ -z "$ALERT_WEBHOOK_URL" ] || [ "$ALERT_LEVEL" = "ok" ]; then return; fi

  local emoji="⚠️"
  [ "$ALERT_LEVEL" = "critical" ] && emoji="🚨"

  local alerts_text
  alerts_text=$(printf "• %s\n" "${ALERTS[@]}")

  local payload
  payload=$(cat <<JSON
{
  "text": "$emoji *OUAGA CHAP — Alerte $ALERT_LEVEL*\n\n$alerts_text\n\nCPU: ${CPU_USAGE}% | RAM: ${MEM_USAGE}% | Disk: ${DISK_USAGE}% | API: ${API_STATUS} (${API_DURATION}ms)"
}
JSON
)

  curl -s -X POST "$ALERT_WEBHOOK_URL" \
    -H "Content-Type: application/json" \
    -d "$payload" \
    --max-time 5 \
    -o /dev/null || true
}

# ── Main ──────────────────────────────────────────────────────────────────────
run_check() {
  collect_metrics
  evaluate_alerts
  print_report

  echo "${LOG_PREFIX} level=$ALERT_LEVEL cpu=${CPU_USAGE}% mem=${MEM_USAGE}% disk=${DISK_USAGE}% api=${API_STATUS}(${API_DURATION}ms) q_failed=${QUEUE_FAILED}"

  if [ "$ACTION" = "--report" ]; then
    send_heartbeat
    send_to_logtail
    send_webhook_alert
  fi
}

case "$ACTION" in
  --watch)
    while true; do
      run_check
      sleep 60
    done
    ;;
  *)
    run_check
    ;;
esac
