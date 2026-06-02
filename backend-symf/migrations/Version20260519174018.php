<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519174018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice ADD updated_at DATETIME DEFAULT NULL, ADD pdf_path VARCHAR(255) DEFAULT NULL, ADD pdf_generated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE quotation ADD updated_at DATETIME DEFAULT NULL, ADD pdf_path VARCHAR(255) DEFAULT NULL, ADD pdf_generated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP updated_at, DROP pdf_path, DROP pdf_generated_at');
        $this->addSql('ALTER TABLE quotation DROP updated_at, DROP pdf_path, DROP pdf_generated_at');
    }
}
