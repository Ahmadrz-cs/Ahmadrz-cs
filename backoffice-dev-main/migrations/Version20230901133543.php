<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230901133543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add asset yield and income';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assets ADD netProjectedYield NUMERIC(8, 4) DEFAULT NULL, ADD netProjectedIncome NUMERIC(15, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assets DROP netProjectedYield, DROP netProjectedIncome');
    }
}
