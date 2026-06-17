<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220704154539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transfer order entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transfer_order (id INT AUTO_INCREMENT NOT NULL, asset_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, scheduledFor DATE NOT NULL, status VARCHAR(255) NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, approvedBy_id INT DEFAULT NULL, INDEX IDX_56AD66C05DA1941 (asset_id), INDEX IDX_56AD66C0FACFC38A (approvedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfer_request (id INT AUTO_INCREMENT NOT NULL, debitWalletId VARCHAR(255) NOT NULL, creditWalletId VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, amount NUMERIC(15, 2) NOT NULL, status VARCHAR(255) NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, transferOrder_id INT NOT NULL, INDEX IDX_8422FDD4BF149C58 (transferOrder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transfer_order ADD CONSTRAINT FK_56AD66C05DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE transfer_order ADD CONSTRAINT FK_56AD66C0FACFC38A FOREIGN KEY (approvedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_8422FDD4BF149C58 FOREIGN KEY (transferOrder_id) REFERENCES transfer_order (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_8422FDD4BF149C58');
        $this->addSql('DROP TABLE transfer_order');
        $this->addSql('DROP TABLE transfer_request');
    }
}
