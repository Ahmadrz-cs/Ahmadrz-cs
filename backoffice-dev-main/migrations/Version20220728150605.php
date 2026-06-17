<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220728150605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transaction relation to transfer requests';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request ADD transaction_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_8422FDD42FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8422FDD42FC0CB0F ON transfer_request (transaction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_8422FDD42FC0CB0F');
        $this->addSql('DROP INDEX UNIQ_8422FDD42FC0CB0F ON transfer_request');
        $this->addSql('ALTER TABLE transfer_request DROP transaction_id');
    }
}
