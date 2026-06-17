<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250510113932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace users:roles PHP array with JSON and remove mails:params';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Drop mail params
        $this->addSql(<<<'SQL'
            ALTER TABLE mails DROP params
        SQL);

        // Convert user roles from serialized PHP arrays to JSON
        $this->addSql(<<<'SQL'
            UPDATE users SET roles = '[]' WHERE roles = 'a:0:{}'
        SQL);
        // Individually update the non-empty roles
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT id, roles FROM users WHERE roles != 'a:0:{}'
        SQL);
        foreach ($rows as $row) {
            $userRoles = unserialize($row['roles']);
            $rolesValue = json_encode($userRoles);
            $this->addSql(
                sql: 'UPDATE users SET roles = :rolesValue WHERE id = :id',
                params: [
                    'rolesValue' => $rolesValue,
                    'id' => $row['id'],
                ]
            );
        }

        // Update user roles column type
        $this->addSql(<<<'SQL'
            ALTER TABLE users MODIFY COLUMN roles JSON NOT NULL COMMENT '(DC2Type:json)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // Note that mail params are restored as JSON and all previous data is lost
        $this->addSql(<<<'SQL'
            ALTER TABLE mails ADD params JSON NOT NULL COMMENT '(DC2Type:json)'
        SQL);

        // Update user roles column type so it can accept serialized PHP arrays
        $this->addSql(<<<'SQL'
            ALTER TABLE users MODIFY COLUMN roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)'
        SQL);

        // Convert user roles from JSON to serialized PHP arrays
        $this->addSql(<<<'SQL'
            UPDATE users SET roles = 'a:0:{}' WHERE roles = '[]'
        SQL);
        // Individually update the non-empty roles
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT id, roles FROM users WHERE roles != '[]'
        SQL);
        foreach ($rows as $row) {
            $userRoles = json_decode($row['roles']);
            $rolesValue = serialize($userRoles);
            $this->addSql(
                sql: 'UPDATE users SET roles = :rolesValue WHERE id = :id',
                params: [
                    'rolesValue' => $rolesValue,
                    'id' => $row['id'],
                ]
            );
        }
    }
}
