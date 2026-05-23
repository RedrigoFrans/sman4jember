#!/bin/bash
# ================================================================
# Setup Script — DevoraTeam VPS
# Jalankan di VPS: bash setup-vps.sh
# OS Target: Ubuntu 22.04 / Debian
# ================================================================

set -e  # Stop jika ada error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
info() { echo -e "${BLUE}[→]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }

echo ""
echo "=================================================="
echo "   DevoraTeam — VPS Setup Script"
echo "   Docker + Jenkins CI/CD"
echo "=================================================="
echo ""

# ── 1. Update sistem ─────────────────────────────────────────────
info "Updating system packages..."
apt-get update -qq
apt-get upgrade -y -qq
log "System updated"

# ── 2. Install dependencies dasar ────────────────────────────────
info "Installing base dependencies..."
apt-get install -y -qq \
    curl \
    wget \
    git \
    unzip \
    ca-certificates \
    gnupg \
    lsb-release \
    apt-transport-https \
    software-properties-common \
    ufw \
    net-tools
log "Base dependencies installed"

# ── 3. Install Docker ─────────────────────────────────────────────
info "Installing Docker..."
if command -v docker &> /dev/null; then
    warn "Docker already installed: $(docker --version)"
else
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh -q
    rm get-docker.sh
    systemctl enable docker
    systemctl start docker
    # Tambahkan user ke group docker
    usermod -aG docker $USER || true
    log "Docker installed: $(docker --version)"
fi

# ── 4. Install Docker Compose Plugin ─────────────────────────────
info "Installing Docker Compose..."
if docker compose version &> /dev/null; then
    warn "Docker Compose already installed: $(docker compose version)"
else
    apt-get install -y docker-compose-plugin
    log "Docker Compose installed: $(docker compose version)"
fi

# ── 5. Install Java 17 (diperlukan Jenkins) ───────────────────────
info "Installing Java 17..."
if java -version &>/dev/null 2>&1; then
    warn "Java already installed: $(java -version 2>&1 | head -1)"
else
    apt-get install -y fontconfig openjdk-17-jre
    log "Java installed: $(java -version 2>&1 | head -1)"
fi

# ── 6. Install Jenkins ────────────────────────────────────────────
info "Installing Jenkins..."
if command -v jenkins &>/dev/null || systemctl is-active --quiet jenkins; then
    warn "Jenkins already installed"
else
    wget -q -O /usr/share/keyrings/jenkins-keyring.asc \
        https://pkg.jenkins.io/debian-stable/jenkins.io-2023.key

    echo "deb [signed-by=/usr/share/keyrings/jenkins-keyring.asc]" \
        https://pkg.jenkins.io/debian-stable binary/ \
        | tee /etc/apt/sources.list.d/jenkins.list > /dev/null

    apt-get update -qq
    apt-get install -y jenkins
    systemctl enable jenkins
    systemctl start jenkins
    log "Jenkins installed"
fi

# ── 7. Tambahkan Jenkins ke grup Docker ──────────────────────────
info "Adding jenkins user to docker group..."
usermod -aG docker jenkins
log "Jenkins added to docker group"

# ── 8. Buat folder project ────────────────────────────────────────
info "Creating project directories..."
mkdir -p /opt/devora
chmod 755 /opt/devora
log "Directories created: /opt/devora"

# ── 9. Setup Firewall ─────────────────────────────────────────────
info "Configuring firewall..."
ufw allow ssh    > /dev/null
ufw allow 80     > /dev/null  # Web app
ufw allow 443    > /dev/null  # HTTPS
ufw allow 8080   > /dev/null  # Jenkins
ufw allow 3307   > /dev/null  # MySQL (opsional, bisa diclose nanti)
ufw --force enable
log "Firewall configured"

# ── 10. Restart Jenkins untuk apply docker group ──────────────────
info "Restarting Jenkins..."
systemctl restart jenkins
sleep 5
log "Jenkins restarted"

# ── 11. Status Summary ────────────────────────────────────────────
echo ""
echo "=================================================="
echo "   ✅ SETUP SELESAI!"
echo "=================================================="
echo ""
echo "📦 Docker    : $(docker --version)"
echo "📦 Compose   : $(docker compose version)"
echo "☕ Java      : $(java -version 2>&1 | head -1)"
echo ""
echo "🔧 Jenkins Status:"
systemctl status jenkins --no-pager -l | head -5
echo ""
echo "🔑 Jenkins Initial Admin Password:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
cat /var/lib/jenkins/secrets/initialAdminPassword 2>/dev/null || echo "(Belum tersedia, tunggu 30 detik lalu jalankan:)"
echo "  sudo cat /var/lib/jenkins/secrets/initialAdminPassword"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 Akses Jenkins di browser:"
echo "   http://103.157.27.213:8080"
echo ""
echo "📁 Project directory: /opt/devora"
echo ""
echo "⚠️  SELANJUTNYA:"
echo "   1. Buka http://103.157.27.213:8080 di browser"
echo "   2. Masukkan password di atas"
echo "   3. Install recommended plugins"
echo "   4. Buat pipeline 'devora-web'"
echo ""
