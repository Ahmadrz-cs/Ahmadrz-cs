<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015133308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user status log and remove blameable in onboarding profile';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_status_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, notes VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, occuredAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, transitionedBy_id INT DEFAULT NULL, INDEX IDX_F637FE8B4A8EA82E (transitionedBy_id), INDEX IDX_F637FE8BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_status_log ADD CONSTRAINT FK_F637FE8B4A8EA82E FOREIGN KEY (transitionedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_status_log ADD CONSTRAINT FK_F637FE8BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        // Convert existing user status record into status logs for supported states
        // Initial state
        $this->addSql(
            'INSERT INTO user_status_log (user_id, status, occuredAt, createdAt, updatedAt)
            SELECT u.id, \'pending\', u.createdAt, NOW(), NOW()
            FROM users u'
        );
        $this->addSql(
            'INSERT INTO user_status_log (user_id, status, occuredAt, createdAt, updatedAt)
            SELECT u.id, \'active\', ustat.emailValidatedOn, NOW(), NOW()
            FROM users u LEFT JOIN users_statuses ustat ON u.status_id = ustat.id 
            WHERE ustat.emailValidatedOn IS NOT NULL'
        );
        // (Account) closed
        $this->addSql(
            'INSERT INTO user_status_log (user_id, status, occuredAt, createdAt, updatedAt)
            SELECT u.id, \'closed\', ustat.blockedOn, NOW(), NOW()
            FROM users u LEFT JOIN users_statuses ustat ON u.status_id = ustat.id 
            WHERE ustat.blockedOn IS NOT NULL'
        );

        // Remove createdBy and updatedBy from onboardingProfile
        $this->addSql('ALTER TABLE onboarding_profile DROP FOREIGN KEY FK_30FB7AD43174800F');
        $this->addSql('ALTER TABLE onboarding_profile DROP FOREIGN KEY FK_30FB7AD465FF1AEC');
        $this->addSql('DROP INDEX IDX_409F369865FF1AEC ON onboarding_profile');
        $this->addSql('DROP INDEX IDX_409F36983174800F ON onboarding_profile');
        $this->addSql('ALTER TABLE onboarding_profile DROP createdBy_id, DROP updatedBy_id');

        // Remove createdBy and updatedBy from asset_status_logs and replace principalType with transitionedBy
        $this->addSql('ALTER TABLE asset_status_log DROP FOREIGN KEY FK_59FA4E3365FF1AEC');
        $this->addSql('ALTER TABLE asset_status_log DROP FOREIGN KEY FK_59FA4E333174800F');
        $this->addSql('DROP INDEX IDX_97B1DB3D3174800F ON asset_status_log');
        $this->addSql('DROP INDEX IDX_97B1DB3D65FF1AEC ON asset_status_log');
        $this->addSql('ALTER TABLE asset_status_log ADD transitionedBy_id INT DEFAULT NULL, DROP principalType, DROP createdBy_id, DROP updatedBy_id');
        $this->addSql('ALTER TABLE asset_status_log ADD CONSTRAINT FK_97B1DB3D4A8EA82E FOREIGN KEY (transitionedBy_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_97B1DB3D4A8EA82E ON asset_status_log (transitionedBy_id)');

        // Remove principalType on KycReview
        $this->addSql('ALTER TABLE kyc_review DROP principalType');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_status_log DROP FOREIGN KEY FK_F637FE8B4A8EA82E');
        $this->addSql('ALTER TABLE user_status_log DROP FOREIGN KEY FK_F637FE8BA76ED395');
        $this->addSql('DROP TABLE user_status_log');

        // Reinstate createdBy and updatedBy to onboardingProfile
        $this->addSql('ALTER TABLE onboarding_profile ADD createdBy_id INT DEFAULT NULL, ADD updatedBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE onboarding_profile ADD CONSTRAINT FK_30FB7AD43174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE onboarding_profile ADD CONSTRAINT FK_30FB7AD465FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_409F369865FF1AEC ON onboarding_profile (updatedBy_id)');
        $this->addSql('CREATE INDEX IDX_409F36983174800F ON onboarding_profile (createdBy_id)');

        // Reinstate createdBy and updatedByasset_status_logs and replace transitionedBy with principalType
        $this->addSql('ALTER TABLE asset_status_log DROP FOREIGN KEY FK_97B1DB3D4A8EA82E');
        $this->addSql('DROP INDEX IDX_97B1DB3D4A8EA82E ON asset_status_log');
        $this->addSql('ALTER TABLE asset_status_log ADD principalType INT NOT NULL, ADD updatedBy_id INT DEFAULT NULL, CHANGE transitionedBy_id createdBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE asset_status_log ADD CONSTRAINT FK_59FA4E3365FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE asset_status_log ADD CONSTRAINT FK_59FA4E333174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_97B1DB3D3174800F ON asset_status_log (createdBy_id)');
        $this->addSql('CREATE INDEX IDX_97B1DB3D65FF1AEC ON asset_status_log (updatedBy_id)');

        // Reinstate principalType on KycReview
        $this->addSql('ALTER TABLE kyc_review ADD principalType INT DEFAULT 0 NOT NULL');
    }
}
