<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230706155521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add asset relation to transfer request';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request ADD asset_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_8422FDD45DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('CREATE INDEX IDX_8422FDD45DA1941 ON transfer_request (asset_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_8422FDD45DA1941');
        $this->addSql('DROP INDEX IDX_8422FDD45DA1941 ON transfer_request');
        $this->addSql('ALTER TABLE transfer_request DROP asset_id');
    }
}
