<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230421132934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add kyc report';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE KycReport (id INT AUTO_INCREMENT NOT NULL, subject_id INT NOT NULL, createdAt DATETIME NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, providerName VARCHAR(255) NOT NULL, providerReferenceId VARCHAR(255) NOT NULL, checkType VARCHAR(255) NOT NULL, result VARCHAR(255) NOT NULL, score VARCHAR(255) NOT NULL, verified TINYINT(1) NOT NULL, checkedAt DATETIME NOT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_C1CE86A623EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE KycReport ADD CONSTRAINT FK_C1CE86A623EDC87 FOREIGN KEY (subject_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE KycReport DROP FOREIGN KEY FK_C1CE86A623EDC87');
        $this->addSql('DROP TABLE KycReport');
    }
}
