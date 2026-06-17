<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230418105615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bank account registration table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE BankAccount (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, accountType VARCHAR(32) NOT NULL, accountNumber VARCHAR(34) NOT NULL, bankIdentifierCode VARCHAR(11) NOT NULL, description VARCHAR(255) DEFAULT NULL, providerId VARCHAR(64) DEFAULT NULL, accountHolderType VARCHAR(32) NOT NULL, country VARCHAR(3) NOT NULL, bankName VARCHAR(50) DEFAULT NULL, status VARCHAR(32) NOT NULL, accountHolderName VARCHAR(255) DEFAULT NULL, createdBy VARCHAR(255) DEFAULT NULL, updatedBy VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, accountHolderAddress_id INT DEFAULT NULL, approvedBy_id INT DEFAULT NULL, INDEX IDX_ED412811A76ED395 (user_id), UNIQUE INDEX UNIQ_ED4128111D81C79D (accountHolderAddress_id), INDEX IDX_ED412811FACFC38A (approvedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE BankAccount ADD CONSTRAINT FK_ED412811A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE BankAccount ADD CONSTRAINT FK_ED4128111D81C79D FOREIGN KEY (accountHolderAddress_id) REFERENCES addresses (id)');
        $this->addSql('ALTER TABLE BankAccount ADD CONSTRAINT FK_ED412811FACFC38A FOREIGN KEY (approvedBy_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE BankAccount DROP FOREIGN KEY FK_ED412811A76ED395');
        $this->addSql('ALTER TABLE BankAccount DROP FOREIGN KEY FK_ED4128111D81C79D');
        $this->addSql('ALTER TABLE BankAccount DROP FOREIGN KEY FK_ED412811FACFC38A');
        $this->addSql('DROP TABLE BankAccount');
    }
}
