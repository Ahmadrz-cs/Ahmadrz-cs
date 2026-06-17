<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240828100205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add share transfer order and request tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ShareTransferOrder (id INT AUTO_INCREMENT NOT NULL, asset_id INT NOT NULL, description VARCHAR(255) DEFAULT NULL, scheduledFor DATE NOT NULL, status VARCHAR(255) DEFAULT \'draft\' NOT NULL, periodStart DATE NOT NULL, periodEnd DATE NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, approvedBy_id INT DEFAULT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_50F95D3A5DA1941 (asset_id), INDEX IDX_50F95D3AFACFC38A (approvedBy_id), INDEX IDX_50F95D3A3174800F (createdBy_id), INDEX IDX_50F95D3A65FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ShareTransferRequest (id INT AUTO_INCREMENT NOT NULL, seller_id INT NOT NULL, buyer_id INT NOT NULL, investment_id INT DEFAULT NULL, status VARCHAR(255) DEFAULT \'pending\' NOT NULL, shares INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, shareTransferOrder_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_DB7A1B931301E95F (shareTransferOrder_id), INDEX IDX_DB7A1B938DE820D9 (seller_id), INDEX IDX_DB7A1B936C755722 (buyer_id), INDEX IDX_DB7A1B936E1B4FD5 (investment_id), INDEX IDX_DB7A1B933174800F (createdBy_id), INDEX IDX_DB7A1B9365FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ShareTransferOrder ADD CONSTRAINT FK_50F95D3A5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE ShareTransferOrder ADD CONSTRAINT FK_50F95D3AFACFC38A FOREIGN KEY (approvedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ShareTransferOrder ADD CONSTRAINT FK_50F95D3A3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ShareTransferOrder ADD CONSTRAINT FK_50F95D3A65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ShareTransferRequest ADD CONSTRAINT FK_DB7A1B931301E95F FOREIGN KEY (shareTransferOrder_id) REFERENCES ShareTransferOrder (id)');
        $this->addSql('ALTER TABLE ShareTransferRequest ADD CONSTRAINT FK_DB7A1B938DE820D9 FOREIGN KEY (seller_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ShareTransferRequest ADD CONSTRAINT FK_DB7A1B936C755722 FOREIGN KEY (buyer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ShareTransferRequest ADD CONSTRAINT FK_DB7A1B936E1B4FD5 FOREIGN KEY (investment_id) REFERENCES investments (id)');
        $this->addSql('ALTER TABLE ShareTransferRequest ADD CONSTRAINT FK_DB7A1B933174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE ShareTransferRequest ADD CONSTRAINT FK_DB7A1B9365FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ShareTransferOrder DROP FOREIGN KEY FK_50F95D3A5DA1941');
        $this->addSql('ALTER TABLE ShareTransferOrder DROP FOREIGN KEY FK_50F95D3AFACFC38A');
        $this->addSql('ALTER TABLE ShareTransferOrder DROP FOREIGN KEY FK_50F95D3A3174800F');
        $this->addSql('ALTER TABLE ShareTransferOrder DROP FOREIGN KEY FK_50F95D3A65FF1AEC');
        $this->addSql('ALTER TABLE ShareTransferRequest DROP FOREIGN KEY FK_DB7A1B931301E95F');
        $this->addSql('ALTER TABLE ShareTransferRequest DROP FOREIGN KEY FK_DB7A1B938DE820D9');
        $this->addSql('ALTER TABLE ShareTransferRequest DROP FOREIGN KEY FK_DB7A1B936C755722');
        $this->addSql('ALTER TABLE ShareTransferRequest DROP FOREIGN KEY FK_DB7A1B936E1B4FD5');
        $this->addSql('ALTER TABLE ShareTransferRequest DROP FOREIGN KEY FK_DB7A1B933174800F');
        $this->addSql('ALTER TABLE ShareTransferRequest DROP FOREIGN KEY FK_DB7A1B9365FF1AEC');
        $this->addSql('DROP TABLE ShareTransferOrder');
        $this->addSql('DROP TABLE ShareTransferRequest');
    }
}
