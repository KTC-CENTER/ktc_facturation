#!/bin/bash
# ============================================================================
# KTC-Invoice Pro - VPS Diagnostic & Fix Script
# Run this on your VPS to diagnose and fix connection issues
# ============================================================================

set -e

echo "============================================"
echo " KTC-Invoice Pro - VPS Diagnostic"
echo "============================================"
echo ""

APP_DIR="/opt/apps/ktc-invoice"
DOMAIN="facturation.kamer-center.net"

# Check Docker
echo "1️⃣  Checking Docker..."
if command -v docker &> /dev/null; then
    echo "   ✅ Docker installed: $(docker --version)"
    if systemctl is-active --quiet docker; then
        echo "   ✅ Docker is running"
    else
        echo "   ❌ Docker is NOT running"
        echo "   → Fixing: Starting Docker..."
        systemctl start docker
    fi
else
    echo "   ❌ Docker not installed"
    echo "   → Fixing: Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
fi

# Check Docker Compose
echo ""
echo "2️⃣  Checking Docker Compose..."
if docker compose version &> /dev/null; then
    echo "   ✅ Docker Compose installed"
else
    echo "   ❌ Docker Compose not installed"
    echo "   → Fixing: Installing Docker Compose..."
    apt-get update && apt-get install -y docker-compose-plugin
fi

# Check app directory
echo ""
echo "3️⃣  Checking application directory..."
if [ -d "$APP_DIR" ]; then
    echo "   ✅ App directory exists: $APP_DIR"
    if [ -f "$APP_DIR/docker-compose.prod.yml" ]; then
        echo "   ✅ docker-compose.prod.yml exists"
    else
        echo "   ❌ docker-compose.prod.yml MISSING"
        echo "   → You need to run the GitHub Actions deployment first"
    fi
else
    echo "   ❌ App directory does not exist"
    echo "   → Creating directory..."
    mkdir -p $APP_DIR
fi

# Check containers
echo ""
echo "4️⃣  Checking Docker containers..."
if [ -f "$APP_DIR/docker-compose.prod.yml" ]; then
    cd $APP_DIR
    echo "   Current containers:"
    docker ps --format "   {{.Names}}: {{.Status}}" | grep ktc || echo "   (none running)"
fi

# Check ports
echo ""
echo "5️⃣  Checking ports 80 and 443..."
if ss -tlnp | grep -q ":80 "; then
    echo "   ✅ Port 80 is listening"
    ss -tlnp | grep ":80 " | head -1
else
    echo "   ❌ Port 80 is NOT listening"
fi

if ss -tlnp | grep -q ":443 "; then
    echo "   ✅ Port 443 is listening"
else
    echo "   ⚠️  Port 443 is NOT listening (SSL not configured yet)"
fi

# Check SSL certificate
echo ""
echo "6️⃣  Checking SSL certificate..."
if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo "   ✅ SSL certificate exists for $DOMAIN"
    openssl x509 -in /etc/letsencrypt/live/$DOMAIN/fullchain.pem -noout -dates 2>/dev/null | head -2
else
    echo "   ❌ SSL certificate MISSING"
    echo "   → Installing SSL certificate..."
    
    # Stop anything on port 80
    docker stop ktc-invoice-nginx 2>/dev/null || true
    fuser -k 80/tcp 2>/dev/null || true
    
    # Install certbot if needed
    apt-get install -y certbot 2>/dev/null || true
    
    # Get certificate
    certbot certonly --standalone \
        -d $DOMAIN \
        --email admin@kamer-center.net \
        --agree-tos \
        --non-interactive && echo "   ✅ SSL certificate installed!"
fi

# Check firewall
echo ""
echo "7️⃣  Checking firewall..."
if command -v ufw &> /dev/null; then
    if ufw status | grep -q "Status: active"; then
        echo "   UFW is active"
        if ufw status | grep -q "80"; then
            echo "   ✅ Port 80 allowed"
        else
            echo "   ❌ Port 80 not allowed → Fixing..."
            ufw allow 80/tcp
        fi
        if ufw status | grep -q "443"; then
            echo "   ✅ Port 443 allowed"
        else
            echo "   ❌ Port 443 not allowed → Fixing..."
            ufw allow 443/tcp
        fi
    else
        echo "   ✅ UFW is inactive"
    fi
else
    echo "   ✅ UFW not installed"
fi

# Try to start containers
echo ""
echo "8️⃣  Starting containers..."
if [ -f "$APP_DIR/docker-compose.prod.yml" ]; then
    cd $APP_DIR
    docker compose -f docker-compose.prod.yml up -d
    sleep 5
    echo ""
    echo "   Container status:"
    docker ps --format "   {{.Names}}: {{.Status}}" | grep ktc || echo "   ❌ No containers running"
else
    echo "   ⚠️  Cannot start - docker-compose.prod.yml missing"
    echo "   → Deploy the application first via GitHub Actions"
fi

# Final test
echo ""
echo "9️⃣  Testing connection..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null | grep -qE "200|301|302"; then
    echo "   ✅ Local HTTP responding"
else
    echo "   ❌ Local HTTP not responding"
fi

echo ""
echo "============================================"
echo " Diagnostic complete!"
echo "============================================"
echo ""
echo " If issues persist:"
echo " 1. Check docker logs: docker logs ktc-invoice-app"
echo " 2. Check nginx logs: docker logs ktc-invoice-nginx"
echo " 3. Restart containers: cd $APP_DIR && docker compose -f docker-compose.prod.yml restart"
echo ""
