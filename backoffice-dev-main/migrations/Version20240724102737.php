<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240724102737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add question and question choice tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Question (id INT AUTO_INCREMENT NOT NULL, questionType VARCHAR(255) NOT NULL, section INT DEFAULT NULL, content VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, locked TINYINT(1) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_4F812B183174800F (createdBy_id), INDEX IDX_4F812B1865FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE QuestionChoice (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, content VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, correct TINYINT(1) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_B1B0DAE41E27F6BF (question_id), INDEX IDX_B1B0DAE43174800F (createdBy_id), INDEX IDX_B1B0DAE465FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Question ADD CONSTRAINT FK_4F812B183174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE Question ADD CONSTRAINT FK_4F812B1865FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE QuestionChoice ADD CONSTRAINT FK_B1B0DAE41E27F6BF FOREIGN KEY (question_id) REFERENCES Question (id)');
        $this->addSql('ALTER TABLE QuestionChoice ADD CONSTRAINT FK_B1B0DAE43174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE QuestionChoice ADD CONSTRAINT FK_B1B0DAE465FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Question DROP FOREIGN KEY FK_4F812B183174800F');
        $this->addSql('ALTER TABLE Question DROP FOREIGN KEY FK_4F812B1865FF1AEC');
        $this->addSql('ALTER TABLE QuestionChoice DROP FOREIGN KEY FK_B1B0DAE41E27F6BF');
        $this->addSql('ALTER TABLE QuestionChoice DROP FOREIGN KEY FK_B1B0DAE43174800F');
        $this->addSql('ALTER TABLE QuestionChoice DROP FOREIGN KEY FK_B1B0DAE465FF1AEC');
        $this->addSql('DROP TABLE Question');
        $this->addSql('DROP TABLE QuestionChoice');
    }
}
