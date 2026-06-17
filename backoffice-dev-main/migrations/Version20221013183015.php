<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221013183015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add asset deposit and distribution wallet id fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assets ADD depositWalletId VARCHAR(255) DEFAULT NULL, ADD distributionWalletId VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assets DROP depositWalletId, DROP distributionWalletId');
    }
}
