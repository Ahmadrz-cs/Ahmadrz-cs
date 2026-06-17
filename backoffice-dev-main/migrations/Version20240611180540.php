<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240611180540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop deprecated holdings table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE holdings DROP FOREIGN KEY FK_EF81C5825DA1941');
        $this->addSql('ALTER TABLE holdings DROP FOREIGN KEY FK_EF81C582A76ED395');
        $this->addSql('DROP TABLE holdings');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE holdings (id INT AUTO_INCREMENT NOT NULL, asset_id INT DEFAULT NULL, user_id INT DEFAULT NULL, investment_id INT DEFAULT 0, transaction_id INT DEFAULT 0, share_amount INT DEFAULT NULL, createdById INT DEFAULT 0, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, updatedBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_EF81C582A76ED395 (user_id), INDEX IDX_EF81C5825DA1941 (asset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE holdings ADD CONSTRAINT FK_EF81C5825DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE holdings ADD CONSTRAINT FK_EF81C582A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }
}
