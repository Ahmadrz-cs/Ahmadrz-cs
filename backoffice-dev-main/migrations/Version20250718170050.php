<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250718170050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename tables to snake_case if not, for consistency';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Rename tables
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS AppSetting TO app_setting
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS AssessmentResponse TO assessment_response
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS AssetStatusLog TO asset_status_log
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS BankAccount TO bank_account
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS KycProfile TO kyc_profile
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS KycReport TO kyc_report
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS KycReview TO kyc_review
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS OnboardingProfile TO onboarding_profile
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS Question TO question
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS QuestionChoice TO question_choice
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS Report TO report
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS ReportSet TO report_set
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS reportset_report TO report_set_report
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS ResetPasswordRequest TO reset_password_request
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS ShareTransferOrder TO share_transfer_order
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS ShareTransferRequest TO share_transfer_request
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS TaskTracker TO task_tracker
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS UserAssessment TO user_assessment
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS UserCategorisation TO user_categorisation
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS WebhookEvent TO webhook_event
        SQL);

        // Update indexes and foreign keys - mainly for Doctrine detection
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_44fda12edd3dd5f1 TO IDX_420D1ADDDD3DD5F1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_44fda12e1e27f6bf TO IDX_420D1ADD1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_44fda12e998666d1 TO IDX_420D1ADD998666D1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_44fda12e3174800f TO IDX_420D1ADD3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_44fda12e65ff1aec TO IDX_420D1ADD65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS asset_status_log RENAME INDEX idx_59fa4e335da1941 TO IDX_97B1DB3D5DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS asset_status_log RENAME INDEX idx_59fa4e333174800f TO IDX_97B1DB3D3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS asset_status_log RENAME INDEX idx_59fa4e3365ff1aec TO IDX_97B1DB3D65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS bank_account RENAME INDEX idx_ed412811a76ed395 TO IDX_53A23E0AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS bank_account RENAME INDEX uniq_ed4128111d81c79d TO UNIQ_53A23E0A1D81C79D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS bank_account RENAME INDEX idx_ed412811facfc38a TO IDX_53A23E0AFACFC38A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_profile RENAME INDEX idx_54320a1a291aafe6 TO IDX_E7FD27B8291AAFE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_report RENAME INDEX idx_c1ce86a623edc87 TO IDX_68990A5E23EDC87
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_review RENAME INDEX idx_7ca270e423edc87 TO IDX_D5F5FC1C23EDC87
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_review RENAME INDEX idx_7ca270e49c6a92e TO IDX_D5F5FC1C9C6A92E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS onboarding_profile RENAME INDEX idx_30fb7ad43174800f TO IDX_409F36983174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS onboarding_profile RENAME INDEX idx_30fb7ad465ff1aec TO IDX_409F369865FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question RENAME INDEX idx_4f812b183174800f TO IDX_B6F7494E3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question RENAME INDEX idx_4f812b1865ff1aec TO IDX_B6F7494E65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question_choice RENAME INDEX idx_b1b0dae41e27f6bf TO IDX_C6F6759A1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question_choice RENAME INDEX idx_b1b0dae43174800f TO IDX_C6F6759A3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question_choice RENAME INDEX idx_b1b0dae465ff1aec TO IDX_C6F6759A65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set RENAME INDEX idx_713b806f5da1941 TO IDX_16EF5D5B5DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report DROP FOREIGN KEY FK_3428E10B0DFE6FD
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3428E10B0DFE6FD ON report_set_report
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `primary` ON report_set_report
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report CHANGE reportset_id report_set_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report ADD CONSTRAINT FK_73286E2E3908D887 FOREIGN KEY (report_set_id) REFERENCES report_set (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_73286E2E3908D887 ON report_set_report (report_set_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report ADD PRIMARY KEY (report_set_id, report_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report RENAME INDEX idx_3428e104bd2a4c0 TO IDX_73286E2E4BD2A4C0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS reset_password_request RENAME INDEX idx_35370143a76ed395 TO IDX_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_50f95d3a5da1941 TO IDX_AB9D42FA5DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_50f95d3afacfc38a TO IDX_AB9D42FAFACFC38A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_50f95d3a3174800f TO IDX_AB9D42FA3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_50f95d3a65ff1aec TO IDX_AB9D42FA65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_db7a1b931301e95f TO IDX_9D883A4C1301E95F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_db7a1b938de820d9 TO IDX_9D883A4C8DE820D9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_db7a1b936c755722 TO IDX_9D883A4C6C755722
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_db7a1b936e1b4fd5 TO IDX_9D883A4C6E1B4FD5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_db7a1b933174800f TO IDX_9D883A4C3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_db7a1b9365ff1aec TO IDX_9D883A4C65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_assessment RENAME INDEX idx_38176a81ccfa12b8 TO IDX_5035FB9CCCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_assessment RENAME INDEX idx_38176a813174800f TO IDX_5035FB9C3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_assessment RENAME INDEX idx_38176a8165ff1aec TO IDX_5035FB9C65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_a73b6eefccfa12b8 TO IDX_1D429C20CCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_a73b6eef291aafe6 TO IDX_1D429C20291AAFE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_a73b6eef3174800f TO IDX_1D429C203174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_a73b6eef65ff1aec TO IDX_1D429C2065FF1AEC
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // Update indexes and foreign keys - mainly for Doctrine detection
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS asset_status_log RENAME INDEX idx_97b1db3d65ff1aec TO IDX_59FA4E3365FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS asset_status_log RENAME INDEX idx_97b1db3d5da1941 TO IDX_59FA4E335DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS asset_status_log RENAME INDEX idx_97b1db3d3174800f TO IDX_59FA4E333174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set RENAME INDEX idx_16ef5d5b5da1941 TO IDX_713B806F5DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_420d1add3174800f TO IDX_44FDA12E3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_420d1adddd3dd5f1 TO IDX_44FDA12EDD3DD5F1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_420d1add65ff1aec TO IDX_44FDA12E65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_420d1add1e27f6bf TO IDX_44FDA12E1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS assessment_response RENAME INDEX idx_420d1add998666d1 TO IDX_44FDA12E998666D1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_ab9d42fa3174800f TO IDX_50F95D3A3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_ab9d42fa65ff1aec TO IDX_50F95D3A65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_ab9d42fa5da1941 TO IDX_50F95D3A5DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_order RENAME INDEX idx_ab9d42fafacfc38a TO IDX_50F95D3AFACFC38A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS onboarding_profile RENAME INDEX idx_409f36983174800f TO IDX_30FB7AD43174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS onboarding_profile RENAME INDEX idx_409f369865ff1aec TO IDX_30FB7AD465FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report DROP FOREIGN KEY FK_73286E2E3908D887
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_73286E2E3908D887 ON report_set_report
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `PRIMARY` ON report_set_report
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report CHANGE report_set_id reportset_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report ADD CONSTRAINT FK_3428E10B0DFE6FD FOREIGN KEY (reportset_id) REFERENCES report_set (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3428E10B0DFE6FD ON report_set_report (reportset_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report ADD PRIMARY KEY (reportset_id, report_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS report_set_report RENAME INDEX idx_73286e2e4bd2a4c0 TO IDX_3428E104BD2A4C0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_review RENAME INDEX idx_d5f5fc1c23edc87 TO IDX_7CA270E423EDC87
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_review RENAME INDEX idx_d5f5fc1c9c6a92e TO IDX_7CA270E49C6A92E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question_choice RENAME INDEX idx_c6f6759a65ff1aec TO IDX_B1B0DAE465FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question_choice RENAME INDEX idx_c6f6759a1e27f6bf TO IDX_B1B0DAE41E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question_choice RENAME INDEX idx_c6f6759a3174800f TO IDX_B1B0DAE43174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_9d883a4c65ff1aec TO IDX_DB7A1B9365FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_9d883a4c6c755722 TO IDX_DB7A1B936C755722
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_9d883a4c6e1b4fd5 TO IDX_DB7A1B936E1B4FD5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_9d883a4c1301e95f TO IDX_DB7A1B931301E95F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_9d883a4c3174800f TO IDX_DB7A1B933174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS share_transfer_request RENAME INDEX idx_9d883a4c8de820d9 TO IDX_DB7A1B938DE820D9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_report RENAME INDEX idx_68990a5e23edc87 TO IDX_C1CE86A623EDC87
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS bank_account RENAME INDEX uniq_53a23e0a1d81c79d TO UNIQ_ED4128111D81C79D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS bank_account RENAME INDEX idx_53a23e0aa76ed395 TO IDX_ED412811A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS bank_account RENAME INDEX idx_53a23e0afacfc38a TO IDX_ED412811FACFC38A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question RENAME INDEX idx_b6f7494e3174800f TO IDX_4F812B183174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS question RENAME INDEX idx_b6f7494e65ff1aec TO IDX_4F812B1865FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS kyc_profile RENAME INDEX idx_e7fd27b8291aafe6 TO IDX_54320A1A291AAFE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS reset_password_request RENAME INDEX idx_7ce748aa76ed395 TO IDX_35370143A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_1d429c203174800f TO IDX_A73B6EEF3174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_1d429c2065ff1aec TO IDX_A73B6EEF65FF1AEC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_1d429c20ccfa12b8 TO IDX_A73B6EEFCCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_categorisation RENAME INDEX idx_1d429c20291aafe6 TO IDX_A73B6EEF291AAFE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_assessment RENAME INDEX idx_5035fb9cccfa12b8 TO IDX_38176A81CCFA12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_assessment RENAME INDEX idx_5035fb9c3174800f TO IDX_38176A813174800F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE IF EXISTS user_assessment RENAME INDEX idx_5035fb9c65ff1aec TO IDX_38176A8165FF1AEC
        SQL);

        // Rename tables
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS app_setting TO AppSetting
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS assessment_response TO AssessmentResponse
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS asset_status_log TO AssetStatusLog
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS bank_account TO BankAccount
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS kyc_profile TO KycProfile
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS kyc_report TO KycReport
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS kyc_review TO KycReview
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS onboarding_profile TO OnboardingProfile
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS question TO Question
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS question_choice TO QuestionChoice
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS report TO Report
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS report_set TO ReportSet
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS report_set_report TO reportset_report
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS reset_password_request TO ResetPasswordRequest
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS share_transfer_order TO ShareTransferOrder
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS share_transfer_request TO ShareTransferRequest
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS task_tracker TO TaskTracker
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS user_assessment TO UserAssessment
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS user_categorisation TO UserCategorisation
        SQL);
        $this->addSql(<<<'SQL'
            RENAME TABLE IF EXISTS webhook_event TO WebhookEvent
        SQL);
    }
}
