<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230420220313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user KYC profile';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE KycProfile (id INT AUTO_INCREMENT NOT NULL, verified TINYINT(1) NOT NULL, lastReviewedAt DATETIME DEFAULT NULL, dueDiligenceLevel INT NOT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, verifiedBy_id INT DEFAULT NULL, INDEX IDX_54320A1A291AAFE6 (verifiedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE KycProfile ADD CONSTRAINT FK_54320A1A291AAFE6 FOREIGN KEY (verifiedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD kycProfile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F6B6113D FOREIGN KEY (kycProfile_id) REFERENCES KycProfile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F6B6113D ON users (kycProfile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F6B6113D');
        $this->addSql('ALTER TABLE KycProfile DROP FOREIGN KEY FK_54320A1A291AAFE6');
        $this->addSql('DROP TABLE KycProfile');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F6B6113D ON users');
        $this->addSql('ALTER TABLE users DROP kycProfile_id');
    }
}
