<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220714161712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update oauth2 client with name and pkce toggle';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oauth2_client ADD name VARCHAR(128) NOT NULL, CHANGE allow_plain_text_pkce allowPlainTextPkce TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE oauth2_client DROP name, CHANGE allowplaintextpkce allow_plain_text_pkce TINYINT(1) DEFAULT \'0\' NOT NULL');
    }
}
