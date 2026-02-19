-- ============================================================
-- KTC Invoice Pro - Script de correction BD
-- ============================================================

-- 1. Ajouter colonnes manquantes à product
ALTER TABLE product ADD COLUMN IF NOT EXISTS purchase_price DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE product ADD COLUMN IF NOT EXISTS margin DECIMAL(5,2) DEFAULT NULL;
ALTER TABLE product ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) DEFAULT NULL;
ALTER TABLE product ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT NULL;

-- 2. Ajouter marge par défaut à company_settings
ALTER TABLE company_settings ADD COLUMN IF NOT EXISTS default_margin DECIMAL(5,2) DEFAULT 30.00;

-- 3. Mettre à jour les valeurs par défaut
UPDATE company_settings SET default_margin = 30.00 WHERE default_margin IS NULL OR default_margin = 0;

-- 4. Fix updated_at nullable pour product
ALTER TABLE product MODIFY COLUMN updated_at DATETIME DEFAULT NULL;

-- 5. Fix type produit (uppercase pour ancien code)
UPDATE product SET type = 'LOGICIEL' WHERE type = 'logiciel';
UPDATE product SET type = 'MATERIEL' WHERE type = 'materiel';
UPDATE product SET type = 'SERVICE' WHERE type = 'service';

-- 6. Mots de passe "password" pour tous les utilisateurs
UPDATE user SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- 7. Fix totaux NULL
UPDATE proforma SET total_ht = '0', total_tva = '0', total_ttc = '0' WHERE total_ht IS NULL;
UPDATE invoice SET total_ht = '0', total_tva = '0', total_ttc = '0' WHERE total_ht IS NULL;

-- 8. Client par défaut
ALTER TABLE client ADD COLUMN IF NOT EXISTS is_default TINYINT(1) DEFAULT 0;

INSERT INTO client (id, name, email, phone, address, city, country, is_archived, is_default, created_at)
VALUES (1, 'Client par défaut', 'client@example.com', '+237600000000', 'Yaoundé', 'Yaoundé', 'Cameroun', 0, 1, NOW())
ON DUPLICATE KEY UPDATE is_default = 1, is_archived = 0;

UPDATE client SET is_default = 0 WHERE id != 1;
UPDATE client SET is_default = 1 WHERE id = 1;

-- 9. Vérification company_settings existe
INSERT INTO company_settings (id, company_name, currency, default_tax_rate, default_margin, proforma_prefix, invoice_prefix, proforma_start_number, invoice_start_number, proforma_current_number, invoice_current_number, default_validity_days, default_payment_days, timezone, locale, date_format, created_at, updated_at)
SELECT 1, 'KTC-Center', 'FCFA', 19.25, 30.00, 'PROV', 'FAC', 1, 1, 0, 0, 30, 30, 'Africa/Douala', 'fr', 'd/m/Y', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM company_settings WHERE id = 1);
