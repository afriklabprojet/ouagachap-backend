#!/bin/bash
# =============================================================================
# OUAGA CHAP — Setup VPS Hetzner API-only (Ubuntu 22.04 / 24.04)
# Usage: bash setup_vps.sh
# À exécuter en root sur le serveur
# =============================================================================
set -euo pipefail

VPS_IP="204.168.212.156"
DEPLOY_USER="deploy"
APP_DIR="/opt/ouagachap"
SWAP_SIZE="2G"

echo "============================================="
echo "  OUAGA CHAP — Configuration VPS Hetzner"
echo "  IP: $VPS_IP"
echo "============================================="

# =============================================================================
# 1. Mise à jour système
# =============================================================================
echo "[1/7] Mise à jour du système..."
apt-get update -q && apt-get upgrade -y -q
apt-get install -y -q \
  curl git wget unzip \
  ufw fail2ban \
  htop vim tmux \
  ca-certificates gnupg lsb-release

# =============================================================================
# 2. Swap (si < 4GB RAM)
# =============================================================================
echo "[2/7] Configuration du swap ($SWAP_SIZE)..."
if [ ! -f /swapfile ]; then
  fallocate -l "$SWAP_SIZE" /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl vm.swappiness=10
  echo 'vm.swappiness=10' >> /etc/sysctl.conf
  echo "  ✓ Swap $SWAP_SIZE créé"
else
  echo "  ℹ Swap déjà présent"
fi

# =============================================================================
# 3. Utilisateur deploy
# =============================================================================
echo "[3/7] Création de l'utilisateur deploy..."
if ! id "$DEPLOY_USER" &>/dev/null; then
  useradd -m -s /bin/bash -G sudo "$DEPLOY_USER"
  echo "$DEPLOY_USER ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/"$DEPLOY_USER"
  chmod 440 /etc/sudoers.d/"$DEPLOY_USER"
  echo "  ✓ Utilisateur '$DEPLOY_USER' créé"
else
  echo "  ℹ Utilisateur '$DEPLOY_USER' existe déjà"
fi

# Copier les clés SSH de root vers deploy
if [ -d /root/.ssh ]; then
  mkdir -p /home/$DEPLOY_USER/.ssh
  cp /root/.ssh/authorized_keys /home/$DEPLOY_USER/.ssh/ 2>/dev/null || true
  chown -R $DEPLOY_USER:$DEPLOY_USER /home/$DEPLOY_USER/.ssh
  chmod 700 /home/$DEPLOY_USER/.ssh
  chmod 600 /home/$DEPLOY_USER/.ssh/authorized_keys 2>/dev/null || true
fi

# =============================================================================
# 4. Firewall UFW
# =============================================================================
echo "[4/7] Configuration du firewall UFW..."
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp   comment "SSH"
ufw allow 80/tcp   comment "HTTP"
ufw allow 443/tcp  comment "HTTPS"
ufw --force enable
echo "  ✓ Firewall configuré (SSH + HTTP + HTTPS)"

# =============================================================================
# 5. Fail2ban
# =============================================================================
echo "[5/7] Configuration fail2ban..."
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port    = ssh
logpath = %(sshd_log)s
EOF
systemctl enable fail2ban
systemctl restart fail2ban
echo "  ✓ Fail2ban actif (SSH protégé)"

# =============================================================================
# 6. Installation Docker
# =============================================================================
echo "[6/7] Installation Docker..."
if ! command -v docker &>/dev/null; then
  # Ajout du repo officiel Docker
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg

  echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
    https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
    > /etc/apt/sources.list.d/docker.list

  apt-get update -q
  apt-get install -y -q docker-ce docker-ce-cli containerd.io \
    docker-buildx-plugin docker-compose-plugin

  systemctl enable docker
  systemctl start docker

  # Ajouter deploy au groupe docker
  usermod -aG docker "$DEPLOY_USER"
  echo "  ✓ Docker $(docker --version | cut -d' ' -f3) installé"
else
  echo "  ℹ Docker déjà installé: $(docker --version)"
  usermod -aG docker "$DEPLOY_USER" || true
fi

# =============================================================================
# 7. Dossier application
# =============================================================================
echo "[7/7] Préparation du dossier application..."
mkdir -p "$APP_DIR"
mkdir -p /backups/ouagachap
chown -R "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"
chown -R "$DEPLOY_USER":"$DEPLOY_USER" /backups/ouagachap
echo "  ✓ Dossier $APP_DIR prêt"
echo "  ✓ Dossier /backups/ouagachap prêt"

# =============================================================================
# 8. Cron backup automatique
# =============================================================================
echo "[8/8] Configuration du cron backup MySQL..."

CRON_JOB="0 2 * * * $DEPLOY_USER /opt/ouagachap/scripts/backup_db.sh >> /var/log/ouagachap-backup.log 2>&1"
CRON_FILE="/etc/cron.d/ouagachap-backup"

if [ ! -f "$CRON_FILE" ]; then
  cat > "$CRON_FILE" << EOF
# OUAGA CHAP — Backup MySQL quotidien à 2h du matin
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
$CRON_JOB

# OUAGA CHAP — Monitoring santé système toutes les 5 minutes
*/5 * * * * $DEPLOY_USER /opt/ouagachap/scripts/monitor_health.sh --report >> /var/log/ouagachap-monitor.log 2>&1
EOF
  chmod 644 "$CRON_FILE"
  echo "  ✓ Cron backup créé : $CRON_FILE"
  echo "  ✓ Exécution : tous les jours à 02:00, log dans /var/log/ouagachap-backup.log"
else
  echo "  ℹ Cron backup déjà présent"
fi

# Rotation des logs backup (logrotate)
cat > /etc/logrotate.d/ouagachap-backup << 'EOF'
/var/log/ouagachap-backup.log {
    weekly
    rotate 4
    compress
    missingok
    notifempty
}
EOF
echo "  ✓ Logrotate configuré pour /var/log/ouagachap-backup.log"

# =============================================================================
# Résumé
# =============================================================================
echo ""
echo "============================================="
echo "  ✅ SETUP TERMINÉ — RÉCAPITULATIF"
echo "============================================="
echo "  VPS IP     : $VPS_IP"
echo "  User       : $DEPLOY_USER (sudo sans mdp)"
echo "  App dir    : $APP_DIR"
echo "  Docker     : $(docker --version)"
echo "  Firewall   : UFW actif (22, 80, 443)"
echo "  Fail2ban   : actif"
echo "  Backup     : cron quotidien 02:00 → /backups/ouagachap/"
echo "  Rétention  : 14 jours locaux"
echo "  Monitoring : cron toutes les 5min → /var/log/ouagachap-monitor.log"
echo ""
echo "  BACKUP S3 (optionnel — recommandé) :"
echo "  Ajouter dans /opt/ouagachap/.env :"
echo "    BACKUP_S3_ENDPOINT=https://fsn1.your-objectstorage.com"
echo "    BACKUP_S3_BUCKET=ouagachap-backups"
echo "    BACKUP_S3_KEY_ID=<clé Hetzner Object Storage>"
echo "    BACKUP_S3_SECRET=<secret Hetzner Object Storage>"
echo "  Puis installer awscli : apt install awscli"
echo ""
echo "  RISQUE 7 — Single Point of Failure :"
echo "  Solution recommandée pour phase de croissance :"
echo "    • Hetzner Managed Database (MySQL répliqué) → éliminer MySQL Docker"
echo "    • Snapshot VPS quotidien (Hetzner Console → Backups)"
echo "    • 2ème VPS en standby avec même docker-compose + restore backup"
echo "  À planifier une fois le trafic > 500 commandes/jour."
echo ""
echo "  PROCHAINE ÉTAPE:"
echo "  → Connectez-vous en tant que '$DEPLOY_USER':"
echo "    ssh $DEPLOY_USER@$VPS_IP"
echo "  → Puis lancez le déploiement depuis votre poste local:"
echo "    cd <repo>/OUAGA_CHAP && bash scripts/push_api.sh"
echo "============================================="
