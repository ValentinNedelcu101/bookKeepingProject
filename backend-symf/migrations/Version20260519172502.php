<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519172502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, contact_email VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, billing_address VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, tax_number VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, issue_date DATE NOT NULL, due_date DATE NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, tax_total NUMERIC(10, 2) NOT NULL, total NUMERIC(15, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, client_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_9065174419EB6921 (client_id), INDEX IDX_90651744B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice_item (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, tax NUMERIC(10, 2) NOT NULL, line_total NUMERIC(10, 2) NOT NULL, invoice_id INT NOT NULL, INDEX IDX_1DDE477B2989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quotation (id INT AUTO_INCREMENT NOT NULL, quotation_number VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, issue_date DATE NOT NULL, valid_until DATE NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, tax_total NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, client_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_474A8DB919EB6921 (client_id), INDEX IDX_474A8DB9B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quotation_item (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, tax_rate NUMERIC(10, 2) NOT NULL, line_total NUMERIC(10, 2) NOT NULL, quotation_id INT NOT NULL, INDEX IDX_82EF8052B4EA4E60 (quotation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, tva_number INT NOT NULL, created_at DATETIME NOT NULL, phone VARCHAR(255) NOT NULL, billing_address LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_9065174419EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE quotation ADD CONSTRAINT FK_474A8DB919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE quotation ADD CONSTRAINT FK_474A8DB9B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE quotation_item ADD CONSTRAINT FK_82EF8052B4EA4E60 FOREIGN KEY (quotation_id) REFERENCES quotation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_9065174419EB6921');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744B03A8386');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B2989F1FD');
        $this->addSql('ALTER TABLE quotation DROP FOREIGN KEY FK_474A8DB919EB6921');
        $this->addSql('ALTER TABLE quotation DROP FOREIGN KEY FK_474A8DB9B03A8386');
        $this->addSql('ALTER TABLE quotation_item DROP FOREIGN KEY FK_82EF8052B4EA4E60');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE quotation');
        $this->addSql('DROP TABLE quotation_item');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
