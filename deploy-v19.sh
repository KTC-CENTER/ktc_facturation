#!/bin/bash
# ============================================================
# KTC Invoice Pro - Deploy v19 on VPS
# ============================================================
# Usage: 
#   scp deploy-v19.sh root@81.169.177.240:/tmp/
#   ssh root@81.169.177.240 "chmod +x /tmp/deploy-v19.sh && /tmp/deploy-v19.sh"
# ============================================================
set -e

echo "🚀 Déploiement KTC Invoice Pro v19..."
echo ""

cd /var/www/html

# ============================================================
# 1. Services
# ============================================================
echo "📦 Création NumberToWordsService..."
mkdir -p src/Service

cat > src/Service/NumberToWordsService.php << 'EOFSERVICE'
<?php

namespace App\Service;

class NumberToWordsService
{
    private array $units = [
        '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'
    ];

    private array $tens = [
        '', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'
    ];

    public function convert(float $number, string $currency = 'FCFA'): string
    {
        if ($number == 0) {
            return 'zéro ' . $currency;
        }

        $number = abs($number);
        $intPart = (int) floor($number);
        $decPart = (int) round(($number - $intPart) * 100);

        $result = $this->convertToWords($intPart);

        if ($decPart > 0) {
            $result .= ' ' . $currency . ' et ' . $this->convertToWords($decPart) . ' centimes';
        } else {
            $result .= ' ' . $currency;
        }

        return ucfirst($result);
    }

    private function convertToWords(int $number): string
    {
        if ($number < 20) {
            return $this->units[$number];
        }

        if ($number < 100) {
            return $this->convertTens($number);
        }

        if ($number < 1000) {
            return $this->convertHundreds($number);
        }

        if ($number < 1000000) {
            return $this->convertThousands($number);
        }

        if ($number < 1000000000) {
            return $this->convertMillions($number);
        }

        return $this->convertBillions($number);
    }

    private function convertTens(int $number): string
    {
        $ten = (int) floor($number / 10);
        $unit = $number % 10;

        if ($ten == 7 || $ten == 9) {
            $unit += 10;
        }

        $result = $this->tens[$ten];

        if ($unit == 0) {
            if ($ten == 8) {
                $result .= 's';
            }
            return $result;
        }

        if ($unit == 1 && $ten != 8 && $ten != 9) {
            $result .= ' et ';
        } elseif ($ten == 7 && $unit == 11) {
            $result .= ' et ';
        } else {
            $result .= '-';
        }

        $result .= $this->units[$unit];

        return $result;
    }

    private function convertHundreds(int $number): string
    {
        $hundred = (int) floor($number / 100);
        $rest = $number % 100;

        $result = '';

        if ($hundred == 1) {
            $result = 'cent';
        } else {
            $result = $this->units[$hundred] . ' cent';
            if ($rest == 0) {
                $result .= 's';
            }
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }

    private function convertThousands(int $number): string
    {
        $thousand = (int) floor($number / 1000);
        $rest = $number % 1000;

        $result = '';

        if ($thousand == 1) {
            $result = 'mille';
        } else {
            $result = $this->convertToWords($thousand) . ' mille';
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }

    private function convertMillions(int $number): string
    {
        $million = (int) floor($number / 1000000);
        $rest = $number % 1000000;

        $result = $this->convertToWords($million) . ' million';
        if ($million > 1) {
            $result .= 's';
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }

    private function convertBillions(int $number): string
    {
        $billion = (int) floor($number / 1000000000);
        $rest = $number % 1000000000;

        $result = $this->convertToWords($billion) . ' milliard';
        if ($billion > 1) {
            $result .= 's';
        }

        if ($rest > 0) {
            $result .= ' ' . $this->convertToWords($rest);
        }

        return $result;
    }
}
EOFSERVICE

echo "✅ NumberToWordsService créé"

# ============================================================
# 2. Mise à jour BD
# ============================================================
echo ""
echo "🗄️ Mise à jour base de données..."

docker exec ktc-invoice-mysql mysql -u root -pverysecret ktc_invoice << 'EOFSQL'
-- Ajout colonnes tracking proforma
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS sent_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS accepted_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS refused_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS expired_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS invoiced_at DATETIME NULL;

-- Table historique (si n'existe pas)
CREATE TABLE IF NOT EXISTS proforma_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proforma_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    status_label VARCHAR(100) NULL,
    changed_by_id INT NULL,
    changed_at DATETIME NOT NULL,
    comment TEXT NULL,
    CONSTRAINT fk_psh_proforma FOREIGN KEY (proforma_id) REFERENCES proforma(id) ON DELETE CASCADE,
    INDEX idx_psh_proforma (proforma_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing with approximate dates
UPDATE proforma SET sent_at = updated_at WHERE status = 'SENT' AND sent_at IS NULL;
UPDATE proforma SET accepted_at = updated_at WHERE status = 'ACCEPTED' AND accepted_at IS NULL;
UPDATE proforma SET invoiced_at = updated_at WHERE status = 'INVOICED' AND invoiced_at IS NULL;
EOFSQL

echo "✅ Base de données mise à jour"

# ============================================================
# 3. Mise à jour Proforma.php - Status "Initié"
# ============================================================
echo ""
echo "📝 Mise à jour entités..."

# Remplacer "Brouillon" par "Initié" dans Proforma.php
sed -i "s/'Brouillon'/'Initiée'/g" src/Entity/Proforma.php
sed -i "s/'Brouillon'/'Initiée'/g" src/Entity/Invoice.php

echo "✅ Status 'Brouillon' remplacé par 'Initié'"

# ============================================================
# 4. Injection NumberToWordsService dans InvoiceController
# ============================================================
echo ""
echo "📝 Mise à jour InvoiceController..."

# Ajouter use statement si pas présent
if ! grep -q "use App\\\\Service\\\\NumberToWordsService" src/Controller/InvoiceController.php; then
    sed -i '/use App\\Service\\PdfGeneratorService;/a use App\\Service\\NumberToWordsService;' src/Controller/InvoiceController.php
fi

# Ajouter au constructeur
if ! grep -q "NumberToWordsService" src/Controller/InvoiceController.php | grep -q "private"; then
    sed -i 's/private PdfGeneratorService \$pdfGenerator/private PdfGeneratorService \$pdfGenerator,\n        private NumberToWordsService \$numberToWords/' src/Controller/InvoiceController.php
fi

echo "✅ InvoiceController mis à jour"

# ============================================================
# 5. Cache
# ============================================================
echo ""
echo "🧹 Vidage cache..."

docker exec ktc-invoice-app php bin/console cache:clear 2>/dev/null || true

echo "✅ Cache vidé"

# ============================================================
# Terminé
# ============================================================
echo ""
echo "============================================================"
echo "🎉 DÉPLOIEMENT v19 TERMINÉ!"
echo "============================================================"
echo ""
echo "📝 Changements:"
echo "   ✅ NumberToWordsService créé"
echo "   ✅ Status 'Brouillon' → 'Initié'"
echo "   ✅ Colonnes tracking proforma ajoutées"
echo "   ✅ Table proforma_status_history créée"
echo ""
echo "🔗 Site: https://facturation.kamer-center.net"
echo "============================================================"
