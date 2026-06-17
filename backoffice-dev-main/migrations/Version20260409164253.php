<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409164253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add asset trading control fields and trade order complement';
    }

    public function up(Schema $schema): void
    {
        // Add asset trading control fields
        $this->addSql(
            'ALTER TABLE assets ADD featured INT DEFAULT 0 NOT NULL, ADD buyRestricted TINYINT DEFAULT 0 NOT NULL, CHANGE COLUMN blockedForSale sellRestricted TINYINT DEFAULT 0 NOT NULL',
        );

        // Add complementary trade order field
        $this->addSql(
            'ALTER TABLE trade_order ADD complementaryOrder_id INT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE trade_order ADD CONSTRAINT FK_DF24437B71B85DE2 FOREIGN KEY (complementaryOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_DF24437B71B85DE2 ON trade_order (complementaryOrder_id)',
        );

        // Increase transaction value_amount limit to 12.2 digits (1 trillion - 1 pennies)
        $this->addSql(
            'ALTER TABLE transactions CHANGE value_amount value_amount NUMERIC(14, 2) DEFAULT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        // Revert transaction value_amount limit to 8.2 digits (100 million - 1 pennies)
        $this->addSql(
            'ALTER TABLE transactions CHANGE value_amount value_amount NUMERIC(10, 2) DEFAULT NULL',
        );

        // Remove complementary trade order field
        $this->addSql('ALTER TABLE trade_order DROP FOREIGN KEY FK_DF24437B71B85DE2');
        $this->addSql('DROP INDEX UNIQ_DF24437B71B85DE2 ON trade_order');
        $this->addSql('ALTER TABLE trade_order DROP complementaryOrder_id');

        // Revert asset trading control fields
        $this->addSql(
            'ALTER TABLE assets CHANGE COLUMN sellRestricted blockedForSale TINYINT DEFAULT 0, DROP buyRestricted, DROP featured',
        );
    }
}
