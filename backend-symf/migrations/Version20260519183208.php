<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519183208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE contact_email contact_email VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE billing_address billing_address LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE tax_number tax_number VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice CHANGE status status VARCHAR(20) DEFAULT \'draft\' NOT NULL, CHANGE due_date due_date DATE DEFAULT NULL, CHANGE subtotal subtotal NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE tax_total tax_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE total total NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE invoice_item CHANGE tax tax NUMERIC(10, 2) DEFAULT NULL, CHANGE line_total line_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE quotation CHANGE status status VARCHAR(20) DEFAULT \'draft\' NOT NULL, CHANGE valid_until valid_until DATE DEFAULT NULL, CHANGE subtotal subtotal NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE tax_total tax_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE total total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE quotation_item CHANGE tax_rate tax_rate NUMERIC(10, 2) DEFAULT NULL, CHANGE line_total line_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE contact_email contact_email VARCHAR(255) NOT NULL, CHANGE phone phone VARCHAR(255) NOT NULL, CHANGE billing_address billing_address VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE tax_number tax_number VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE invoice CHANGE status status VARCHAR(20) NOT NULL, CHANGE due_date due_date DATE NOT NULL, CHANGE subtotal subtotal NUMERIC(10, 2) NOT NULL, CHANGE tax_total tax_total NUMERIC(10, 2) NOT NULL, CHANGE total total NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE invoice_item CHANGE tax tax NUMERIC(10, 2) NOT NULL, CHANGE line_total line_total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE quotation CHANGE status status VARCHAR(20) NOT NULL, CHANGE valid_until valid_until DATE NOT NULL, CHANGE subtotal subtotal NUMERIC(10, 2) NOT NULL, CHANGE tax_total tax_total NUMERIC(10, 2) NOT NULL, CHANGE total total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE quotation_item CHANGE tax_rate tax_rate NUMERIC(10, 2) NOT NULL, CHANGE line_total line_total NUMERIC(10, 2) NOT NULL');
    }
}
