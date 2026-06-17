<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509094614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase offering and investment additional field key and value to 255 chars';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE investment_add_fields CHANGE fieldKey fieldKey VARCHAR(255) NOT NULL, CHANGE fieldValue fieldValue VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offering_add_fields CHANGE fieldKey fieldKey VARCHAR(255) NOT NULL, CHANGE fieldValue fieldValue VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE offering_add_fields CHANGE fieldKey fieldKey VARCHAR(20) NOT NULL, CHANGE fieldValue fieldValue VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE investment_add_fields CHANGE fieldKey fieldKey VARCHAR(20) NOT NULL, CHANGE fieldValue fieldValue VARCHAR(50) NOT NULL
        SQL);
    }
}
