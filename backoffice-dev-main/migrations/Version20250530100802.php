<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530100802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Mangopay SCA enrollment tracking fields and transactionId to offering to track any fees';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD scaEnrolledAt DATETIME DEFAULT NULL, ADD scaStatus VARCHAR(255) DEFAULT 'inactive' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offerings ADD transactionId VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP scaEnrolledAt, DROP scaStatus
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offerings DROP transactionId
        SQL);
    }
}
