<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211013204924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment_order (id INT AUTO_INCREMENT NOT NULL, asset_id INT NOT NULL, description VARCHAR(255) DEFAULT NULL, scheduledFor DATE NOT NULL, status VARCHAR(255) NOT NULL, paymentType VARCHAR(255) NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_A260A52A5DA1941 (asset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_request (id INT AUTO_INCREMENT NOT NULL, payee_id INT NOT NULL, payout_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, amount NUMERIC(15, 2) NOT NULL, shareholding INT NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, paymentOrder_id INT NOT NULL, INDEX IDX_22DE817519E8E0F9 (paymentOrder_id), INDEX IDX_22DE8175CB4B68F (payee_id), UNIQUE INDEX UNIQ_22DE8175C6D61B7F (payout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment_order ADD CONSTRAINT FK_A260A52A5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE payment_request ADD CONSTRAINT FK_22DE817519E8E0F9 FOREIGN KEY (paymentOrder_id) REFERENCES payment_order (id)');
        $this->addSql('ALTER TABLE payment_request ADD CONSTRAINT FK_22DE8175CB4B68F FOREIGN KEY (payee_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE payment_request ADD CONSTRAINT FK_22DE8175C6D61B7F FOREIGN KEY (payout_id) REFERENCES payouts (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_request DROP FOREIGN KEY FK_22DE817519E8E0F9');
        $this->addSql('DROP TABLE payment_order');
        $this->addSql('DROP TABLE payment_request');
    }
}
