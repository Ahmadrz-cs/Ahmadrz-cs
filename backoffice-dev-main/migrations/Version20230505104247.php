<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230505104247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add task tracker';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE TaskTracker (id INT AUTO_INCREMENT NOT NULL, metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', taskTrackerType VARCHAR(255) NOT NULL, tasks LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE assets ADD taskTracker_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE assets ADD CONSTRAINT FK_79D17D8E725E4C97 FOREIGN KEY (taskTracker_id) REFERENCES TaskTracker (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_79D17D8E725E4C97 ON assets (taskTracker_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assets DROP FOREIGN KEY FK_79D17D8E725E4C97');
        $this->addSql('DROP TABLE TaskTracker');
        $this->addSql('DROP INDEX UNIQ_79D17D8E725E4C97 ON assets');
        $this->addSql('ALTER TABLE assets DROP taskTracker_id');
    }
}
