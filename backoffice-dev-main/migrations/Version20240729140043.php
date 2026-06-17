<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240729140043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user onboarding data models: UserOnboardingProfile, UserCategorisation, UserAssessment, AssessmentResponse';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE AssessmentResponse (id INT AUTO_INCREMENT NOT NULL, assessment_id INT NOT NULL, question_id INT NOT NULL, choice_id INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_44FDA12EDD3DD5F1 (assessment_id), INDEX IDX_44FDA12E1E27F6BF (question_id), INDEX IDX_44FDA12E998666D1 (choice_id), INDEX IDX_44FDA12E3174800F (createdBy_id), INDEX IDX_44FDA12E65FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE OnboardingProfile (id INT AUTO_INCREMENT NOT NULL, cooloffEnd DATETIME DEFAULT NULL, cooloffAccepted TINYINT(1) DEFAULT NULL, riskWarningAccepted TINYINT(1) DEFAULT NULL, category VARCHAR(255) DEFAULT NULL, categoryReviewedAt DATETIME DEFAULT NULL, assessmentPassed TINYINT(1) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_30FB7AD43174800F (createdBy_id), INDEX IDX_30FB7AD465FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE UserAssessment (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, passed TINYINT(1) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, expiry DATETIME DEFAULT NULL, complete TINYINT(1) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_38176A81CCFA12B8 (profile_id), INDEX IDX_38176A813174800F (createdBy_id), INDEX IDX_38176A8165FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE UserCategorisation (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, category VARCHAR(255) NOT NULL, details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', notes VARCHAR(255) DEFAULT NULL, verified TINYINT(1) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, verifiedBy_id INT DEFAULT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_A73B6EEFCCFA12B8 (profile_id), INDEX IDX_A73B6EEF291AAFE6 (verifiedBy_id), INDEX IDX_A73B6EEF3174800F (createdBy_id), INDEX IDX_A73B6EEF65FF1AEC (updatedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE AssessmentResponse ADD CONSTRAINT FK_44FDA12EDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES UserAssessment (id)');
        $this->addSql('ALTER TABLE AssessmentResponse ADD CONSTRAINT FK_44FDA12E1E27F6BF FOREIGN KEY (question_id) REFERENCES Question (id)');
        $this->addSql('ALTER TABLE AssessmentResponse ADD CONSTRAINT FK_44FDA12E998666D1 FOREIGN KEY (choice_id) REFERENCES QuestionChoice (id)');
        $this->addSql('ALTER TABLE AssessmentResponse ADD CONSTRAINT FK_44FDA12E3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE AssessmentResponse ADD CONSTRAINT FK_44FDA12E65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE OnboardingProfile ADD CONSTRAINT FK_30FB7AD43174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE OnboardingProfile ADD CONSTRAINT FK_30FB7AD465FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE UserAssessment ADD CONSTRAINT FK_38176A81CCFA12B8 FOREIGN KEY (profile_id) REFERENCES OnboardingProfile (id)');
        $this->addSql('ALTER TABLE UserAssessment ADD CONSTRAINT FK_38176A813174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE UserAssessment ADD CONSTRAINT FK_38176A8165FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE UserCategorisation ADD CONSTRAINT FK_A73B6EEFCCFA12B8 FOREIGN KEY (profile_id) REFERENCES OnboardingProfile (id)');
        $this->addSql('ALTER TABLE UserCategorisation ADD CONSTRAINT FK_A73B6EEF291AAFE6 FOREIGN KEY (verifiedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE UserCategorisation ADD CONSTRAINT FK_A73B6EEF3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE UserCategorisation ADD CONSTRAINT FK_A73B6EEF65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD onboardingProfile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9982B20F FOREIGN KEY (onboardingProfile_id) REFERENCES OnboardingProfile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9982B20F ON users (onboardingProfile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9982B20F');
        $this->addSql('ALTER TABLE AssessmentResponse DROP FOREIGN KEY FK_44FDA12EDD3DD5F1');
        $this->addSql('ALTER TABLE AssessmentResponse DROP FOREIGN KEY FK_44FDA12E1E27F6BF');
        $this->addSql('ALTER TABLE AssessmentResponse DROP FOREIGN KEY FK_44FDA12E998666D1');
        $this->addSql('ALTER TABLE AssessmentResponse DROP FOREIGN KEY FK_44FDA12E3174800F');
        $this->addSql('ALTER TABLE AssessmentResponse DROP FOREIGN KEY FK_44FDA12E65FF1AEC');
        $this->addSql('ALTER TABLE OnboardingProfile DROP FOREIGN KEY FK_30FB7AD43174800F');
        $this->addSql('ALTER TABLE OnboardingProfile DROP FOREIGN KEY FK_30FB7AD465FF1AEC');
        $this->addSql('ALTER TABLE UserAssessment DROP FOREIGN KEY FK_38176A81CCFA12B8');
        $this->addSql('ALTER TABLE UserAssessment DROP FOREIGN KEY FK_38176A813174800F');
        $this->addSql('ALTER TABLE UserAssessment DROP FOREIGN KEY FK_38176A8165FF1AEC');
        $this->addSql('ALTER TABLE UserCategorisation DROP FOREIGN KEY FK_A73B6EEFCCFA12B8');
        $this->addSql('ALTER TABLE UserCategorisation DROP FOREIGN KEY FK_A73B6EEF291AAFE6');
        $this->addSql('ALTER TABLE UserCategorisation DROP FOREIGN KEY FK_A73B6EEF3174800F');
        $this->addSql('ALTER TABLE UserCategorisation DROP FOREIGN KEY FK_A73B6EEF65FF1AEC');
        $this->addSql('DROP TABLE AssessmentResponse');
        $this->addSql('DROP TABLE OnboardingProfile');
        $this->addSql('DROP TABLE UserAssessment');
        $this->addSql('DROP TABLE UserCategorisation');
        $this->addSql('DROP INDEX UNIQ_1483A5E9982B20F ON users');
        $this->addSql('ALTER TABLE users DROP onboardingProfile_id');
    }
}
