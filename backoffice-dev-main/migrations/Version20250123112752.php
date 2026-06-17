<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123112752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment and transfer statusInfo and change default status to pending';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_request ADD statusInfo VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE transfer_request ADD statusInfo VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT \'pending\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request DROP statusInfo, CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE payment_request DROP statusInfo, CHANGE status status VARCHAR(255) NOT NULL');
    }
}
