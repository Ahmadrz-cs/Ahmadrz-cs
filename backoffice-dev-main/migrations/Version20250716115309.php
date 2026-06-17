<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250716115309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user bankAccountsSyncedAt. Add BankAccount fingerprint, display name, and account holder last name. Make account details nullable.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE BankAccount ADD accountHolderLastName VARCHAR(255) DEFAULT NULL, ADD fingerprint VARCHAR(255) DEFAULT NULL, ADD displayName VARCHAR(80) DEFAULT NULL, ADD currency VARCHAR(3) NOT NULL, CHANGE accountNumber accountNumber VARCHAR(34) DEFAULT NULL, CHANGE bankIdentifierCode bankIdentifierCode VARCHAR(11) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD bankAccountsSyncedAt DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE BankAccount DROP accountHolderLastName, DROP fingerprint, DROP displayName, DROP currency, CHANGE accountNumber accountNumber VARCHAR(34) DEFAULT NULL, CHANGE bankIdentifierCode bankIdentifierCode VARCHAR(11) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP bankAccountsSyncedAt
        SQL);
    }
}
