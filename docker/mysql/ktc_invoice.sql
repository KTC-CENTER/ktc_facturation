-- KTC Invoice Pro - Script d'initialisation MySQL v15 FINAL
-- Ce script est exécuté automatiquement lors de la première création du conteneur
-- Inclut toutes les données de fixtures corrigées

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS ktc_invoice
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ktc_invoice;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================================
-- STRUCTURE DES TABLES
-- ========================================================

-- Table user
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table client
CREATE TABLE `client` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rccm` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table company_settings
CREATE TABLE `company_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rccm` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Cameroun',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_base64` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FCFA',
  `default_tax_rate` decimal(5,2) NOT NULL DEFAULT 19.25,
  `default_margin` decimal(5,2) NOT NULL DEFAULT 30.00,
  `proforma_prefix` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROV',
  `invoice_prefix` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FAC',
  `proforma_start_number` int NOT NULL DEFAULT 1,
  `invoice_start_number` int NOT NULL DEFAULT 1,
  `proforma_current_number` int NOT NULL DEFAULT 0,
  `invoice_current_number` int NOT NULL DEFAULT 0,
  `default_validity_days` int NOT NULL DEFAULT 30,
  `default_payment_days` int NOT NULL DEFAULT 30,
  `default_proforma_conditions` longtext COLLATE utf8mb4_unicode_ci,
  `default_invoice_conditions` longtext COLLATE utf8mb4_unicode_ci,
  `default_payment_terms` longtext COLLATE utf8mb4_unicode_ci,
  `bank_details` longtext COLLATE utf8mb4_unicode_ci,
  `brevo_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reply_to_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_default_message` longtext COLLATE utf8mb4_unicode_ci,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Africa/Douala',
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `date_format` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'd/m/Y',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table product
CREATE TABLE `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `characteristics` longtext COLLATE utf8mb4_unicode_ci,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `margin` decimal(5,2) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_duration` int DEFAULT NULL,
  `max_users` int DEFAULT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warranty_months` int DEFAULT NULL,
  `duration_hours` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D34A04AD77153098` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table proforma_templates
CREATE TABLE `proforma_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Général',
  `base_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `default_notes` longtext COLLATE utf8mb4_unicode_ci,
  `default_conditions` longtext COLLATE utf8mb4_unicode_ci,
  `default_object` longtext COLLATE utf8mb4_unicode_ci,
  `validity_days` int NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2FEC1CF65E237E06` (`name`),
  KEY `IDX_2FEC1CF6B03A8386` (`created_by_id`),
  CONSTRAINT `FK_2FEC1CF6B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table proforma
CREATE TABLE `proforma` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `created_by_id` int NOT NULL,
  `template_id` int DEFAULT NULL,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `issue_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `total_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_tva` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `conditions` longtext COLLATE utf8mb4_unicode_ci,
  `object` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `refused_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `invoiced_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8383AFD6AEA34913` (`reference`),
  KEY `IDX_8383AFD619EB6921` (`client_id`),
  KEY `IDX_8383AFD6B03A8386` (`created_by_id`),
  KEY `IDX_8383AFD65DA0FB8` (`template_id`),
  CONSTRAINT `FK_8383AFD619EB6921` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  CONSTRAINT `FK_8383AFD6B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_8383AFD65DA0FB8` FOREIGN KEY (`template_id`) REFERENCES `proforma_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table proforma_status_history
CREATE TABLE `proforma_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proforma_id` int NOT NULL,
  `changed_by_id` int DEFAULT NULL,
  `old_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` datetime NOT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_proforma_status_history_proforma` (`proforma_id`),
  KEY `idx_proforma_status_history_date` (`changed_at`),
  KEY `IDX_STATUS_HISTORY_CHANGED_BY` (`changed_by_id`),
  CONSTRAINT `FK_STATUS_HISTORY_PROFORMA` FOREIGN KEY (`proforma_id`) REFERENCES `proforma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_STATUS_HISTORY_CHANGED_BY` FOREIGN KEY (`changed_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table invoices
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `created_by_id` int NOT NULL,
  `proforma_id` int DEFAULT NULL,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `total_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_tva` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `conditions` longtext COLLATE utf8mb4_unicode_ci,
  `object` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_terms` longtext COLLATE utf8mb4_unicode_ci,
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6A2F2F95AEA34913` (`reference`),
  UNIQUE KEY `UNIQ_6A2F2F95B26BFE8D` (`proforma_id`),
  KEY `IDX_6A2F2F9519EB6921` (`client_id`),
  KEY `IDX_6A2F2F95B03A8386` (`created_by_id`),
  CONSTRAINT `FK_6A2F2F9519EB6921` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  CONSTRAINT `FK_6A2F2F95B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_6A2F2F95B26BFE8D` FOREIGN KEY (`proforma_id`) REFERENCES `proforma` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table document_items
CREATE TABLE `document_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proforma_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_88BF9F3FB26BFE8D` (`proforma_id`),
  KEY `IDX_88BF9F3F2989F1FD` (`invoice_id`),
  KEY `IDX_88BF9F3F4584665A` (`product_id`),
  CONSTRAINT `FK_88BF9F3FB26BFE8D` FOREIGN KEY (`proforma_id`) REFERENCES `proforma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_88BF9F3F2989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_88BF9F3F4584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table template_items
CREATE TABLE `template_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_optional` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D2C83A825DA0FB8` (`template_id`),
  KEY `IDX_D2C83A824584665A` (`product_id`),
  CONSTRAINT `FK_D2C83A825DA0FB8` FOREIGN KEY (`template_id`) REFERENCES `proforma_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D2C83A824584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table document_shares
CREATE TABLE `document_shares` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proforma_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `created_by_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `view_count` int NOT NULL DEFAULT 0,
  `download_count` int NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `last_viewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `metadata` json DEFAULT NULL,
  `qr_code_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2E3DC01A5F37A13B` (`token`),
  KEY `IDX_2E3DC01AB26BFE8D` (`proforma_id`),
  KEY `IDX_2E3DC01A2989F1FD` (`invoice_id`),
  KEY `IDX_2E3DC01AB03A8386` (`created_by_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `FK_2E3DC01AB26BFE8D` FOREIGN KEY (`proforma_id`) REFERENCES `proforma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2E3DC01A2989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_2E3DC01AB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table email_templates
CREATE TABLE `email_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_text` longtext COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6023E2A577153098` (`code`),
  KEY `IDX_6023E2A5B03A8386` (`created_by_id`),
  CONSTRAINT `FK_6023E2A5B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table doctrine_migration_versions
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DONNÉES INITIALES
-- ========================================================

-- Paramètres entreprise avec marge par défaut
INSERT INTO `company_settings` (
  `id`, `company_name`, `address`, `country`, `phone`, `email`, `website`, 
  `rccm`, `tax_id`, `currency`, `default_tax_rate`, `default_margin`,
  `proforma_prefix`, `invoice_prefix`, `proforma_start_number`, `invoice_start_number`,
  `proforma_current_number`, `invoice_current_number`, `default_validity_days`, `default_payment_days`,
  `sender_email`, `sender_name`, `reply_to_email`, `brevo_api_key`,
  `default_proforma_conditions`, `default_invoice_conditions`,
  `timezone`, `locale`, `date_format`, `created_at`, `updated_at`
) VALUES (
  1, 'KTC-Center Sarl', 'Yaoundé, Cameroun', 'Cameroun', '+237 XXX XXX XXX', 
  'contact@ktc-center.com', 'http://www.ktc-center.com',
  'RC/YAO/2016/A/3141', 'M016200025487C', 'FCFA', 19.25, 30.00,
  'PROV', 'FAC', 1, 1, 0, 0, 30, 30,
  'sale@kamer-center.net', 'KTC-CENTER', 'sale@kamer-center.net',
  '',
  'Validité de l''offre : 30 jours\nPaiement : 100% à la commande\nLivraison : Sous 7 jours ouvrés après confirmation',
  'Conditions de paiement : 30 jours date de facture\nPénalités de retard : 1.5% par mois\nEscompte : Aucun',
  'Africa/Douala', 'fr', 'd/m/Y', NOW(), NOW()
);

-- Utilisateurs (mot de passe: password pour tous)
-- Hash bcrypt pour "password": $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `user` (`id`, `email`, `roles`, `password`, `first_name`, `last_name`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@kamer-center.net', '["ROLE_SUPER_ADMIN"]', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'KTC', '+237 600 000 001', 1, NOW(), NOW()),
(2, 'gestionnaire@ktc-center.com', '["ROLE_ADMIN"]', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jean', 'Dupont', '+237 600 000 002', 1, NOW(), NOW()),
(3, 'commercial@ktc-center.com', '["ROLE_COMMERCIAL"]', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marie', 'Mbarga', '+237 600 000 003', 1, NOW(), NOW()),
(4, 'commercial2@ktc-center.com', '["ROLE_COMMERCIAL"]', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pierre', 'Nkono', '+237 600 000 004', 1, NOW(), NOW()),
(5, 'viewer@ktc-center.com', '["ROLE_VIEWER"]', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Paul', 'Essomba', '+237 600 000 005', 1, NOW(), NOW());

-- Client par défaut (non supprimable)
INSERT INTO `client` (`id`, `name`, `email`, `phone`, `address`, `city`, `country`, `is_archived`, `is_default`, `created_at`) VALUES
(1, 'Client par défaut', 'client@example.com', '+237 600 000 000', 'Adresse à définir', 'Yaoundé', 'Cameroun', 0, 1, NOW());

-- Autres clients
INSERT INTO `client` (`id`, `name`, `email`, `phone`, `address`, `city`, `country`, `contact_person`, `contact_phone`, `is_archived`, `is_default`, `created_at`) VALUES
(2, 'Église Évangélique du Cameroun', 'secretariat@eec.cm', '+237 699 234 567', 'Avenue Kennedy', 'Douala', 'Cameroun', 'Pasteur Thomas', '+237 699 234 568', 0, 0, NOW()),
(3, 'Paroisse Saint-Pierre', 'paroisse.stpierre@gmail.com', '+237 699 111 222', 'Rue de la Cathédrale', 'Yaoundé', 'Cameroun', 'Père Michel', '+237 699 111 223', 0, 0, NOW()),
(4, 'SARL Entreprise Plus', 'contact@entrepriseplus.cm', '+237 233 445 566', 'Boulevard de la Liberté', 'Douala', 'Cameroun', 'M. Kamga Robert', '+237 699 333 444', 0, 0, NOW()),
(5, 'Cabinet Comptable Expertise', 'expertise.compta@gmail.com', '+237 233 112 233', 'Rue Nachtigal', 'Douala', 'Cameroun', 'M. Mbouombouo', '+237 699 112 234', 0, 0, NOW());

-- Produits - LOGICIELS
INSERT INTO `product` (`id`, `name`, `code`, `type`, `description`, `unit_price`, `unit`, `version`, `license_type`, `license_duration`, `max_users`, `is_active`, `purchase_price`, `margin`, `tax_rate`, `created_at`) VALUES
(1, 'CHURCH 3.0', 'LOG-CHURCH', 'logiciel', 'Logiciel de gestion paroissiale complet', 500000.00, 'licence', '3.0', '0-500 fidèles', 12, 5, 1, 384615.00, 30.00, 19.25, NOW()),
(2, 'CHURCH 3.0 Premium', 'LOG-CHURCH-PREM', 'logiciel', 'Logiciel de gestion paroissiale - Version premium', 800000.00, 'licence', '3.0', '500-2000 fidèles', 12, 15, 1, 615385.00, 30.00, 19.25, NOW()),
(3, 'ComptaPlus', 'LOG-COMPTA', 'logiciel', 'Logiciel de comptabilité générale', 350000.00, 'licence', '2.5', 'Mono-poste', 12, 1, 1, 269231.00, 30.00, 19.25, NOW());

-- Produits - MATÉRIELS
INSERT INTO `product` (`id`, `name`, `code`, `type`, `description`, `characteristics`, `unit_price`, `unit`, `brand`, `model`, `warranty_months`, `is_active`, `purchase_price`, `margin`, `tax_rate`, `created_at`) VALUES
(4, 'Ordinateur Desktop', 'MAT-DESKTOP', 'materiel', 'Ordinateur de bureau complet', 'Core I3 /4Go /250Go', 250000.00, 'unité', 'HP', 'ProDesk 400', 12, 1, 192308.00, 30.00, 19.25, NOW()),
(5, 'Imprimante Ticket', 'MAT-IMP-TICKET', 'materiel', 'Imprimante thermique pour tickets de caisse', 'Imprimante à Ticket de caisse', 75000.00, 'unité', 'Epson', 'TM-T20III', 12, 1, 57692.00, 30.00, 19.25, NOW()),
(6, 'Switch Réseau', 'MAT-SWITCH', 'materiel', 'Switch 4 ports + Clé Wifi', 'Switch 4 ports / Clé Wifi', 35000.00, 'unité', 'TP-Link', 'TL-SG1005D', 24, 1, 26923.00, 30.00, 19.25, NOW()),
(7, 'Onduleur', 'MAT-ONDULEUR', 'materiel', 'Onduleur de protection', '650VA / Protection surtension', 45000.00, 'unité', 'APC', 'Back-UPS 650', 24, 1, 34615.00, 30.00, 19.25, NOW());

-- Produits - SERVICES
INSERT INTO `product` (`id`, `name`, `code`, `type`, `description`, `unit_price`, `unit`, `duration_hours`, `is_active`, `purchase_price`, `margin`, `tax_rate`, `created_at`) VALUES
(8, 'Formation Utilisateurs', 'SRV-FORMATION', 'service', 'Formation des utilisateurs au logiciel', 50000.00, 'forfait', 8, 1, 38462.00, 30.00, 19.25, NOW()),
(9, 'Maintenance Annuelle', 'SRV-MAINT', 'service', 'Contrat de maintenance et support technique', 100000.00, 'forfait', NULL, 1, 76923.00, 30.00, 19.25, NOW()),
(10, 'Installation Site Web', 'SRV-WEBSITE', 'service', 'Création et hébergement site internet', 150000.00, 'forfait', 40, 1, 115385.00, 30.00, 19.25, NOW()),
(11, 'Pack SMS', 'SRV-SMS', 'service', 'Pack de 100 SMS', 10000.00, 'forfait', NULL, 1, 7692.00, 30.00, 19.25, NOW()),
(12, 'Support Formation + Maintenance', 'SRV-SUPPORT', 'service', 'Formation + Maintenance 1 an', 150000.00, 'forfait', 8, 1, 115385.00, 30.00, 19.25, NOW());

-- Modèles de proforma
INSERT INTO `proforma_templates` (`id`, `created_by_id`, `name`, `description`, `category`, `base_price`, `default_notes`, `default_conditions`, `default_object`, `validity_days`, `is_active`, `usage_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'CHURCH 3.0 EN LIGNE', 'Pack complet logiciel CHURCH 3.0 avec matériel et services', 'Paroisses', 0.00, 'Ce pack comprend tout le nécessaire pour démarrer avec CHURCH 3.0', 'Validité de l''offre : 30 jours\nPaiement : 100% à la commande', 'Acquisition logiciel CHURCH 3.0 et équipements', 30, 1, 0, NOW(), NOW()),
(2, 1, 'Pack Comptabilité PME', 'Solution comptable complète pour PME', 'Entreprises', 0.00, 'Solution comptable complète', 'Validité de l''offre : 30 jours\nPaiement : 50% à la commande, 50% à la livraison', 'Mise en place solution comptable ComptaPlus', 30, 1, 0, NOW(), NOW());

-- Items des modèles - Template 1: CHURCH 3.0 EN LIGNE
INSERT INTO `template_items` (`id`, `template_id`, `product_id`, `designation`, `description`, `quantity`, `unit`, `unit_price`, `discount`, `total`, `sort_order`, `is_optional`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'CHURCH 3.0', 'Logiciel de gestion paroissiale complet', 1.00, 'licence', 500000.00, 0.00, 500000.00, 1, 0, NOW(), NOW()),
(2, 1, 4, 'Ordinateur Desktop', 'Core I3 /4Go /250Go', 2.00, 'unité', 250000.00, 0.00, 500000.00, 2, 1, NOW(), NOW()),
(3, 1, 5, 'Imprimante Ticket', 'Imprimante à Ticket de caisse', 2.00, 'unité', 75000.00, 0.00, 150000.00, 3, 1, NOW(), NOW()),
(4, 1, 6, 'Switch Réseau', 'Switch 4 ports / Clé Wifi', 1.00, 'unité', 35000.00, 0.00, 35000.00, 4, 1, NOW(), NOW()),
(5, 1, 10, 'Installation Site Web', 'Création et hébergement site internet', 1.00, 'forfait', 150000.00, 0.00, 150000.00, 5, 1, NOW(), NOW()),
(6, 1, 12, 'Support Formation + Maintenance', 'Formation + Maintenance 1 an', 1.00, 'forfait', 150000.00, 0.00, 150000.00, 6, 0, NOW(), NOW()),
(7, 1, 11, 'Pack SMS', 'Pack de 100 SMS', 1.00, 'forfait', 10000.00, 0.00, 10000.00, 7, 1, NOW(), NOW());

-- Items des modèles - Template 2: Pack Comptabilité PME
INSERT INTO `template_items` (`id`, `template_id`, `product_id`, `designation`, `description`, `quantity`, `unit`, `unit_price`, `discount`, `total`, `sort_order`, `is_optional`, `created_at`, `updated_at`) VALUES
(8, 2, 3, 'ComptaPlus', 'Logiciel de comptabilité générale', 1.00, 'licence', 350000.00, 0.00, 350000.00, 1, 0, NOW(), NOW()),
(9, 2, 4, 'Ordinateur Desktop', 'Core I3 /4Go /250Go', 1.00, 'unité', 250000.00, 0.00, 250000.00, 2, 1, NOW(), NOW()),
(10, 2, 7, 'Onduleur', '650VA / Protection surtension', 1.00, 'unité', 45000.00, 0.00, 45000.00, 3, 1, NOW(), NOW()),
(11, 2, 8, 'Formation Utilisateurs', 'Formation des utilisateurs au logiciel', 1.00, 'forfait', 50000.00, 0.00, 50000.00, 4, 0, NOW(), NOW());

SELECT '✅ Base de données initialisée avec succès!' AS message;
SELECT '👤 Mot de passe pour tous les utilisateurs: password' AS info;

-- Ajouter colonne default_margin dans company_settings
ALTER TABLE company_settings ADD COLUMN IF NOT EXISTS default_margin DECIMAL(5,2) DEFAULT 30.00;

-- Ajouter colonne is_default dans client
ALTER TABLE client ADD COLUMN IF NOT EXISTS is_default TINYINT(1) DEFAULT 0;

-- ========================================================
-- MISE À JOUR DES DONNÉES
-- ========================================================

-- Colonne marge par défaut
ALTER TABLE company_settings ADD COLUMN IF NOT EXISTS default_margin DECIMAL(5,2) DEFAULT 30.00;

-- Fix totaux NULL pour proformas/factures
UPDATE proforma SET total_ht = '0', total_tva = '0', total_ttc = '0' WHERE total_ht IS NULL;
UPDATE invoice SET total_ht = '0', total_tva = '0', total_ttc = '0' WHERE total_ht IS NULL;

-- Mot de passe "password"
UPDATE user SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

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