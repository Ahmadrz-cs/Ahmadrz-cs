<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230623171504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add kyc review';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE KycReview (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, status VARCHAR(255) DEFAULT \'open\' NOT NULL, decision TINYINT(1) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, identityReview TINYINT(1) NOT NULL, addressReview TINYINT(1) NOT NULL, countryReview TINYINT(1) NOT NULL, kycProviderReview TINYINT(1) NOT NULL, dueDiligenceLevelReview TINYINT(1) NOT NULL, kycSurveyReview TINYINT(1) NOT NULL, transactionsReview TINYINT(1) NOT NULL, completedAt DATETIME DEFAULT NULL, reviewType VARCHAR(255) DEFAULT \'adhoc\' NOT NULL, principalType INT DEFAULT 0 NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, reviewedBy_id INT DEFAULT NULL, INDEX IDX_7CA270E423EDC87 (subject_id), INDEX IDX_7CA270E49C6A92E (reviewedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE KycReview ADD CONSTRAINT FK_7CA270E423EDC87 FOREIGN KEY (subject_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE KycReview ADD CONSTRAINT FK_7CA270E49C6A92E FOREIGN KEY (reviewedBy_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE KycReview DROP FOREIGN KEY FK_7CA270E423EDC87');
        $this->addSql('ALTER TABLE KycReview DROP FOREIGN KEY FK_7CA270E49C6A92E');
        $this->addSql('DROP TABLE KycReview');
    }
}
