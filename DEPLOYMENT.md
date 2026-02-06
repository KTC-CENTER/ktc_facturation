# 🚀 KTC-Invoice Pro - Guide de Déploiement

## Architecture

```
facturation.kamer-center.net (81.169.177.240)
├── Nginx (SSL/Reverse Proxy) -> Port 80/443
├── PHP-FPM (Application) -> Port 9000 (interne)
├── MySQL (Base de données) -> Port 3306 (interne)
├── phpMyAdmin -> Port 8091 (localhost only)
└── Certbot (SSL renewal)
```

## 📋 Prérequis

### Sur le VPS (une seule fois)

1. **Connectez-vous en SSH au VPS:**
```bash
ssh root@81.169.177.240
```

2. **Téléchargez et exécutez le script de setup:**
```bash
curl -fsSL https://raw.githubusercontent.com/KTC-CENTER/ktc_facturation/main/scripts/vps-setup.sh | bash
```

Ou manuellement:
```bash
apt-get update && apt-get install -y docker.io docker-compose-plugin certbot git rsync
```

3. **Générez une clé SSH pour GitHub Actions:**
```bash
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions -N ""
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
cat ~/.ssh/github_actions  # Copier cette clé privée
```

### Sur GitHub

Allez dans **Settings > Secrets and variables > Actions** et ajoutez ces secrets:

| Secret | Description | Exemple |
|--------|-------------|---------|
| `VPS_SSH_KEY` | Clé privée SSH (contenu de `github_actions`) | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `APP_SECRET` | Secret Symfony | Générer avec: `openssl rand -hex 32` |
| `DB_PASSWORD` | Mot de passe MySQL | `SecurePassword123!` |
| `DB_ROOT_PASSWORD` | Mot de passe root MySQL | `VerySecureRoot456!` |
| `MAILER_DSN` | Configuration email | `smtp://user:pass@smtp.example.com:587` |
| `BREVO_API_KEY` | Clé API Brevo | `xkeysib-...` |
| `BREVO_SENDER_EMAIL` | Email expéditeur | `noreply@kamer-center.net` |

## 🔄 Déploiement Automatique

Le déploiement se déclenche automatiquement à chaque **push sur la branche `main`**.

### Workflow:
1. ✅ Tests PHP (composer, Symfony)
2. ✅ Sync des fichiers via rsync
3. ✅ Installation SSL (Let's Encrypt) - premier déploiement
4. ✅ Build Docker
5. ✅ Migrations base de données
6. ✅ Clear cache
7. ✅ Health check

### Déploiement manuel:
Allez dans **Actions > Deploy KTC-Invoice > Run workflow**

## 🔒 Certificat SSL

Le certificat Let's Encrypt est automatiquement:
- Installé lors du premier déploiement
- Renouvelé tous les 12h par le conteneur Certbot

### Renouvellement manuel (si nécessaire):
```bash
ssh root@81.169.177.240
certbot renew --force-renewal
docker restart ktc-invoice-nginx
```

## 📊 Monitoring

### Voir les logs:
```bash
# Tous les conteneurs
docker compose -f /opt/apps/ktc-invoice/docker-compose.prod.yml logs -f

# Application uniquement
docker logs -f ktc-invoice-app

# Nginx
docker logs -f ktc-invoice-nginx
```

### Statut des conteneurs:
```bash
docker ps
```

### Redémarrer l'application:
```bash
cd /opt/apps/ktc-invoice
docker compose -f docker-compose.prod.yml restart
```

## 🆘 Dépannage

### Erreur SSL "certificate not found"
```bash
# Arrêter nginx
docker stop ktc-invoice-nginx

# Regénérer le certificat
certbot certonly --standalone -d facturation.kamer-center.net --force-renewal

# Redémarrer
docker start ktc-invoice-nginx
```

### Base de données inaccessible
```bash
# Vérifier que MySQL est en cours d'exécution
docker logs ktc-invoice-mysql

# Recréer le conteneur
docker compose -f docker-compose.prod.yml up -d ktc-invoice-db
```

### Application en erreur 500
```bash
# Voir les logs PHP
docker exec ktc-invoice-app tail -f var/log/prod.log

# Vider le cache
docker exec ktc-invoice-app php bin/console cache:clear --env=prod
```

## 🔧 Configuration Locale (Développement)

Pour le développement local, utilisez:
```bash
docker compose up -d
```

Accès:
- Application: http://localhost:8090
- phpMyAdmin: http://localhost:8091
- MailHog: http://localhost:8026
