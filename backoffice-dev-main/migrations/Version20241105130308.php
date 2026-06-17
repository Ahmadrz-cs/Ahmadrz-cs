<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241105130308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new asset phase-status, status log, and termStart';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE AssetStatusLog (id INT AUTO_INCREMENT NOT NULL, asset_id INT NOT NULL, phase VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, principalType INT NOT NULL, notes VARCHAR(255) DEFAULT NULL, occuredAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_59FA4E335DA1941 (asset_id), INDEX IDX_59FA4E333174800F (createdBy_id), INDEX IDX_59FA4E3365FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE AssetStatusLog ADD CONSTRAINT FK_59FA4E335DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE AssetStatusLog ADD CONSTRAINT FK_59FA4E333174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE AssetStatusLog ADD CONSTRAINT FK_59FA4E3365FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE assets ADD currentPhase VARCHAR(255) DEFAULT \'proposal\' NOT NULL, ADD currentStatus VARCHAR(255) DEFAULT \'draft\' NOT NULL, ADD termStart DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE AssetStatusLog DROP FOREIGN KEY FK_59FA4E335DA1941');
        $this->addSql('ALTER TABLE AssetStatusLog DROP FOREIGN KEY FK_59FA4E333174800F');
        $this->addSql('ALTER TABLE AssetStatusLog DROP FOREIGN KEY FK_59FA4E3365FF1AEC');
        $this->addSql('DROP TABLE AssetStatusLog');
        $this->addSql('ALTER TABLE assets DROP currentPhase, DROP currentStatus, DROP termStart');
    }
}
