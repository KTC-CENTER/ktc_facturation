-- Migration pour le suivi des proformas
-- Ajouter les colonnes de suivi des dates de statut sur la table proforma

-- Ajouter les colonnes de date pour chaque changement de statut
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS sent_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS accepted_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS refused_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS expired_at DATETIME NULL;
ALTER TABLE proforma ADD COLUMN IF NOT EXISTS invoiced_at DATETIME NULL;

-- Créer la table d'historique des statuts
CREATE TABLE IF NOT EXISTS proforma_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proforma_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    status_label VARCHAR(100) NULL,
    changed_by_id INT NULL,
    changed_at DATETIME NOT NULL,
    comment TEXT NULL,
    CONSTRAINT fk_psh_proforma FOREIGN KEY (proforma_id) REFERENCES proforma(id) ON DELETE CASCADE,
    CONSTRAINT fk_psh_user FOREIGN KEY (changed_by_id) REFERENCES user(id) ON DELETE SET NULL,
    INDEX idx_psh_proforma (proforma_id),
    INDEX idx_psh_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mettre à jour les proformas existantes avec les dates approximatives basées sur leur statut actuel
UPDATE proforma SET sent_at = updated_at WHERE status = 'SENT' AND sent_at IS NULL;
UPDATE proforma SET accepted_at = updated_at WHERE status = 'ACCEPTED' AND accepted_at IS NULL;
UPDATE proforma SET refused_at = updated_at WHERE status = 'REFUSED' AND refused_at IS NULL;
UPDATE proforma SET expired_at = updated_at WHERE status = 'EXPIRED' AND expired_at IS NULL;
UPDATE proforma SET invoiced_at = updated_at WHERE status = 'INVOICED' AND invoiced_at IS NULL;

-- S'assurer que updated_at est nullable (corrige erreur si existante)
ALTER TABLE proforma MODIFY updated_at DATETIME NULL;
ALTER TABLE product MODIFY updated_at DATETIME NULL;
