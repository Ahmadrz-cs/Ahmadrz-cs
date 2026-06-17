<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213150635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove DC2Type sql comments and change term_service_accepted to bool';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'ALTER TABLE bank_account CHANGE uuid uuid BINARY(16) NOT NULL, CHANGE metadata metadata JSON DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE ext_log_entries CHANGE data data LONGTEXT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE oauth2_access_token CHANGE expiry expiry DATETIME NOT NULL, CHANGE scopes scopes TEXT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE oauth2_authorization_code CHANGE expiry expiry DATETIME NOT NULL, CHANGE scopes scopes TEXT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE oauth2_client CHANGE redirectUris redirectUris TEXT DEFAULT NULL, CHANGE grants grants TEXT DEFAULT NULL, CHANGE scopes scopes TEXT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE oauth2_refresh_token CHANGE expiry expiry DATETIME NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE reset_password_request CHANGE requestedAt requestedAt DATETIME NOT NULL, CHANGE expiresAt expiresAt DATETIME NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE task_tracker CHANGE tasks tasks JSON NOT NULL, CHANGE metadata metadata JSON DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE user_categorisation CHANGE details details JSON DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE users CHANGE term_service_accepted term_service_accepted TINYINT(1) DEFAULT 0, CHANGE roles roles JSON NOT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'ALTER TABLE bank_account CHANGE uuid uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE metadata metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'',
        );
        $this->addSql(
            'ALTER TABLE ext_log_entries CHANGE data data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'',
        );
        $this->addSql(
            'ALTER TABLE oauth2_access_token CHANGE expiry expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE scopes scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\'',
        );
        $this->addSql(
            'ALTER TABLE oauth2_authorization_code CHANGE expiry expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE scopes scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\'',
        );
        $this->addSql(
            'ALTER TABLE oauth2_client CHANGE redirectUris redirectUris TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_redirect_uri)\', CHANGE grants grants TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_grant)\', CHANGE scopes scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\'',
        );
        $this->addSql(
            'ALTER TABLE oauth2_refresh_token CHANGE expiry expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'',
        );
        $this->addSql(
            'ALTER TABLE reset_password_request CHANGE requestedAt requestedAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expiresAt expiresAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'',
        );
        $this->addSql(
            'ALTER TABLE task_tracker CHANGE tasks tasks JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE metadata metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'',
        );
        $this->addSql(
            'ALTER TABLE users CHANGE term_service_accepted term_service_accepted VARCHAR(255) DEFAULT \'0\', CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'',
        );
        $this->addSql(
            'ALTER TABLE user_categorisation CHANGE details details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'',
        );
    }
}
