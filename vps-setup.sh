#!/bin/bash
# ============================================================================
# KTC-Invoice Pro - VPS Initial Setup Script
# Run this once on your VPS before the first deployment
# ============================================================================

set -e

echo "============================================"
echo " KTC-Invoice Pro - VPS Setup"
echo "============================================"

# Update system
echo "📦 Updating system..."
apt-get update && apt-get upgrade -y

# Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo "🐳 Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    systemctl enable docker
    systemctl start docker
fi

# Install Docker Compose plugin
if ! docker compose version &> /dev/null; then
    echo "🔧 Installing Docker Compose plugin..."
    apt-get install -y docker-compose-plugin
fi

# Install Certbot
if ! command -v certbot &> /dev/null; then
    echo "🔒 Installing Certbot..."
    apt-get install -y certbot
fi

# Install useful tools
apt-get install -y htop curl wget git rsync

# Create app directory
mkdir -p /opt/apps/ktc-invoice
mkdir -p /opt/apps/ktc-invoice/certbot/www

# Configure firewall
echo "🔥 Configuring firewall..."
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
fi

# Add swap if not present (for small VPS)
if [ ! -f /swapfile ]; then
    echo "💾 Adding 2GB swap..."
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo ""
echo "============================================"
echo " ✅ VPS Setup Complete!"
echo "============================================"
echo ""
echo " Next steps:"
echo " 1. Add your SSH public key to ~/.ssh/authorized_keys"
echo " 2. Configure GitHub repository secrets (see docs)"
echo " 3. Push to main branch to trigger deployment"
echo ""
echo " Required GitHub Secrets:"
echo "  - VPS_SSH_KEY: Your private SSH key"
echo "  - APP_SECRET: Symfony app secret (generate with: openssl rand -hex 32)"
echo "  - DB_PASSWORD: MySQL password"
echo "  - DB_ROOT_PASSWORD: MySQL root password"
echo "  - MAILER_DSN: Mailer DSN"
echo "  - BREVO_API_KEY: Brevo API key"
echo "  - BREVO_SENDER_EMAIL: Sender email"
echo ""
