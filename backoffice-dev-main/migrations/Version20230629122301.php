<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230629122301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report and report set';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Report (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) DEFAULT NULL, origin VARCHAR(255) NOT NULL, resourceId VARCHAR(255) NOT NULL, referenceId VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT \'draft\' NOT NULL, step VARCHAR(255) DEFAULT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ReportSet (id INT AUTO_INCREMENT NOT NULL, asset_id INT DEFAULT NULL, reportSetType VARCHAR(255) DEFAULT \'custom\' NOT NULL, description VARCHAR(255) DEFAULT NULL, periodStart DATE DEFAULT NULL, periodEnd DATE DEFAULT NULL, progress SMALLINT DEFAULT 1 NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_713B806F5DA1941 (asset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reportset_report (reportset_id INT NOT NULL, report_id INT NOT NULL, INDEX IDX_3428E10B0DFE6FD (reportset_id), INDEX IDX_3428E104BD2A4C0 (report_id), PRIMARY KEY(reportset_id, report_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ReportSet ADD CONSTRAINT FK_713B806F5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)');
        $this->addSql('ALTER TABLE reportset_report ADD CONSTRAINT FK_3428E10B0DFE6FD FOREIGN KEY (reportset_id) REFERENCES ReportSet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reportset_report ADD CONSTRAINT FK_3428E104BD2A4C0 FOREIGN KEY (report_id) REFERENCES Report (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ReportSet DROP FOREIGN KEY FK_713B806F5DA1941');
        $this->addSql('ALTER TABLE reportset_report DROP FOREIGN KEY FK_3428E10B0DFE6FD');
        $this->addSql('ALTER TABLE reportset_report DROP FOREIGN KEY FK_3428E104BD2A4C0');
        $this->addSql('DROP TABLE Report');
        $this->addSql('DROP TABLE ReportSet');
        $this->addSql('DROP TABLE reportset_report');
    }
}
