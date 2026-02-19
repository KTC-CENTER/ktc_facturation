# KTC Invoice Pro - Patch v19

## 📋 Résumé des corrections

### 1. WhatsApp - Nouvel onglet ✅
- Les liens WhatsApp s'ouvrent maintenant dans un nouvel onglet (`target="_blank"`)
- L'utilisateur reste sur la page de la proforma/facture

### 2. Suivi Proforma avec Timeline ✅
- Nouvelle entité `ProformaStatusHistory` pour l'historique
- Colonnes de dates ajoutées: `sent_at`, `accepted_at`, `refused_at`, `expired_at`, `invoiced_at`
- Timeline visuelle sur la page de détail de la proforma
- Dates automatiquement enregistrées lors des changements de statut

### 3. NumberToWordsService ✅
- Nouveau service pour convertir les montants en lettres français
- Gestion des millions, milliards, centimes
- Règles françaises respectées (quatre-vingts, soixante-dix, etc.)
- Affiché sur la page détail facture

### 4. Statut "Initié" ✅
- Remplacement de "Brouillon" par "Initié" partout
- Entités Proforma.php et Invoice.php mises à jour
- Templates de liste mis à jour

### 5. Stats revues ✅
- Labels mis à jour dans les statistiques des listes
- "Initiées" au lieu de "Brouillons"

### 6. Création proforma depuis produit ✅
- Bouton "Créer une proforma" sur la fiche produit
- Le produit est automatiquement ajouté comme première ligne

## 📦 Fichiers du patch

```
patch/
├── src/
│   ├── Service/
│   │   └── NumberToWordsService.php       # NOUVEAU
│   ├── Entity/
│   │   ├── ProformaStatusHistory.php      # NOUVEAU
│   │   ├── Proforma.php                   # Modifié (timeline + Initié)
│   │   └── Invoice.php                    # Modifié (Initié)
│   ├── Repository/
│   │   └── ProformaStatusHistoryRepository.php  # NOUVEAU
│   └── Controller/
│       ├── InvoiceController.php          # Modifié (NumberToWords)
│       └── ProformaController.php         # Modifié (product_id)
├── templates/
│   ├── proforma/
│   │   ├── show.html.twig                 # Timeline + WhatsApp
│   │   └── index.html.twig                # Stats Initié
│   ├── invoice/
│   │   ├── show.html.twig                 # Montant en lettres
│   │   └── index.html.twig                # Stats Initié
│   └── product/
│       └── show.html.twig                 # Bouton créer proforma
├── migrations/
│   └── proforma_status_tracking.sql       # Migration BD
└── apply_patch.sh                          # Script d'application
```

## 🚀 Déploiement

### Option 1: Script automatique (VPS)
```bash
scp deploy-v19.sh root@81.169.177.240:/tmp/
ssh root@81.169.177.240 "chmod +x /tmp/deploy-v19.sh && /tmp/deploy-v19.sh"
```

### Option 2: Patch complet
```bash
# Sur le VPS
cd /var/www/html
unzip patch_v19.zip
./patch/apply_patch.sh
```

## 🗄️ Migration Base de Données

```sql
-- Colonnes de suivi
ALTER TABLE proforma ADD COLUMN sent_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN accepted_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN refused_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN expired_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN invoiced_at DATETIME NULL;

-- Table historique
CREATE TABLE proforma_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proforma_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    status_label VARCHAR(100) NULL,
    changed_by_id INT NULL,
    changed_at DATETIME NOT NULL,
    comment TEXT NULL,
    FOREIGN KEY (proforma_id) REFERENCES proforma(id) ON DELETE CASCADE
);
```

## ✅ Tests à effectuer

1. **Proforma**
   - [ ] Créer une proforma → statut "Initié" affiché
   - [ ] Envoyer par WhatsApp → nouvel onglet s'ouvre
   - [ ] Changer le statut → timeline mise à jour
   - [ ] Voir la timeline sur la page détail

2. **Facture**
   - [ ] Créer une facture → statut "Initié" affiché
   - [ ] Voir le montant en lettres sur la page détail
   - [ ] Envoyer par WhatsApp → nouvel onglet s'ouvre

3. **Produit**
   - [ ] Ouvrir la fiche d'un produit
   - [ ] Cliquer "Créer une proforma"
   - [ ] Vérifier que le produit est pré-rempli

4. **Stats**
   - [ ] Liste proformas → "Initiées" dans les stats
   - [ ] Liste factures → "Initiées" dans les stats
