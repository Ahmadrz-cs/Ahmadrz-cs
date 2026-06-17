<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230414122013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add webhook event log';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE WebhookEvent (id INT AUTO_INCREMENT NOT NULL, eventType VARCHAR(255) NOT NULL, resourceId VARCHAR(255) NOT NULL, fingerprint VARCHAR(255) NOT NULL, lastReceived INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE WebhookEvent');
    }
}
