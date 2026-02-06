#!/bin/bash
echo "=========================================="
echo " KTC-Invoice VPS Diagnostic"
echo "=========================================="

echo ""
echo "1. Docker status:"
docker ps -a

echo ""
echo "2. Docker logs (last 50 lines of app):"
docker logs ktc-invoice-app --tail 50 2>&1

echo ""
echo "3. Docker logs (last 50 lines of nginx):"
docker logs ktc-invoice-nginx --tail 50 2>&1

echo ""
echo "4. Listening ports:"
netstat -tlnp | grep -E ":80|:443|:8080"

echo ""
echo "5. Firewall status:"
ufw status 2>/dev/null || iptables -L -n | head -20

echo ""
echo "6. Docker network:"
docker network ls
docker network inspect ktc-invoice-network 2>/dev/null | head -30

echo ""
echo "7. Disk space:"
df -h /

echo ""
echo "8. Memory:"
free -m

echo ""
echo "9. Try to restart containers:"
cd /opt/apps/ktc-invoice
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
sleep 10
docker ps

echo ""
echo "10. Test local connection:"
curl -I http://localhost:8080 2>&1 | head -5
curl -I http://localhost 2>&1 | head -5
