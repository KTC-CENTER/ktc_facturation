<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206_AddPaymentFields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method and payment_reference fields to invoices table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoices ADD COLUMN IF NOT EXISTS payment_method VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoices ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoices DROP COLUMN IF EXISTS payment_method');
        $this->addSql('ALTER TABLE invoices DROP COLUMN IF EXISTS payment_reference');
    }
}
