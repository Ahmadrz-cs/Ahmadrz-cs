<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017135304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bank account registration metadata, user assessment question type, kyc profile restrictions, onboarding profile product access. Simplify offerings and add inv_id index. Simplify asset status and update values.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bank_account ADD metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql(
            'ALTER TABLE kyc_profile
            ADD buyRestricted TINYINT(1) DEFAULT 0 NOT NULL,
            ADD sellRestricted TINYINT(1) DEFAULT 0 NOT NULL,
            ADD depositRestricted TINYINT(1) DEFAULT 0 NOT NULL,
            ADD withdrawRestricted TINYINT(1) DEFAULT 0 NOT NULL'
        );
        $this->addSql(
            'ALTER TABLE onboarding_profile
            ADD realEstatePlanAccess TINYINT(1) DEFAULT 0 NOT NULL, 
            ADD realEstateBuildAccess TINYINT(1) DEFAULT 0 NOT NULL'
        );


        // Update offering table and indexes
        $this->addSql('ALTER TABLE offerings ADD INDEX IF NOT EXISTS IDX_A7CD243B6F6FD57F (inv_id)');
        $this->addSql('ALTER TABLE offerings DROP INDEX IF EXISTS IDX_A7CD243B2F27AA81, ADD UNIQUE INDEX UNIQ_A7CD243B2F27AA81 (offeringStatus_id)');
        $this->addSql('ALTER TABLE offerings DROP discr');

        $this->addSql('ALTER TABLE user_assessment ADD questionType VARCHAR(255) DEFAULT NULL');

        // Update existing appropriateness assessments with proper questionType
        // Note the field is still nullable to allow unrestricted question composition
        $this->addSql(
            'UPDATE user_assessment SET questionType = \'appropriateness\' WHERE questionType IS NULL'
        );

        // Remove asset phase and static asset status field
        $this->addSql('ALTER TABLE asset_status_log DROP phase');
        $this->addSql('ALTER TABLE assets DROP currentPhase');
        $this->addSql('ALTER TABLE assets DROP currentStatus');
        // Update existing asset statuses to corresponding new status
        $this->addSql(
            'UPDATE asset_status_log SET status = \'acquiring\' WHERE status = \'fundraising\''
        );
        $this->addSql(
            'UPDATE asset_status_log SET status = \'active\' WHERE status IN (\'satisfactory\', \'impacted\', \'distressed\')'
        );
        $this->addSql(
            'UPDATE asset_status_log SET status = \'closing\' WHERE status IN (\'distributing\', \'archiving\')'
        );
        $this->addSql(
            'UPDATE asset_status_log SET status = \'archived\' WHERE status IN (\'mature\', \'premature\')'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bank_account DROP metadata');
        $this->addSql('ALTER TABLE kyc_profile DROP buyRestricted, DROP sellRestricted, DROP depositRestricted, DROP withdrawRestricted');
        $this->addSql('ALTER TABLE onboarding_profile DROP realEstatePlanAccess, DROP realEstateBuildAccess');

        $this->addSql('ALTER TABLE offerings DROP INDEX UNIQ_A7CD243B2F27AA81, ADD INDEX IDX_A7CD243B2F27AA81 (offeringStatus_id)');
        $this->addSql('ALTER TABLE offerings ADD discr VARCHAR(255) NOT NULL DEFAULT \'offering\'');

        $this->addSql('ALTER TABLE user_assessment DROP questionType');

        // Restore asset phase
        $this->addSql('ALTER TABLE asset_status_log ADD phase VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE assets ADD currentPhase VARCHAR(255) DEFAULT \'proposal\' NOT NULL');
        $this->addSql('ALTER TABLE assets ADD currentStatus VARCHAR(255) DEFAULT \'draft\' NOT NULL');
    }
}
