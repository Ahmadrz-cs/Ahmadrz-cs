<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221205162933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add investment relation to transfer requests';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request ADD investment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transfer_request ADD CONSTRAINT FK_8422FDD46E1B4FD5 FOREIGN KEY (investment_id) REFERENCES investments (id)');
        $this->addSql('CREATE INDEX IDX_8422FDD46E1B4FD5 ON transfer_request (investment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transfer_request DROP FOREIGN KEY FK_8422FDD46E1B4FD5');
        $this->addSql('DROP INDEX IDX_8422FDD46E1B4FD5 ON transfer_request');
        $this->addSql('ALTER TABLE transfer_request DROP investment_id');
    }
}
