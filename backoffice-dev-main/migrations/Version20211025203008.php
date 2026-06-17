<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211025203008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_order ADD approvedBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_order ADD CONSTRAINT FK_A260A52AFACFC38A FOREIGN KEY (approvedBy_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_A260A52AFACFC38A ON payment_order (approvedBy_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_order DROP FOREIGN KEY FK_A260A52AFACFC38A');
        $this->addSql('DROP INDEX IDX_A260A52AFACFC38A ON payment_order');
        $this->addSql('ALTER TABLE payment_order DROP approvedBy_id');
    }
}
