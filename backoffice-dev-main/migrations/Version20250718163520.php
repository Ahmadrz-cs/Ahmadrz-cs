<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250718163520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused tables and columns';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Remove tables
        $this->addSql(<<<'SQL'
            ALTER TABLE offering_mgmt_tasks DROP FOREIGN KEY FK_67DD6A57D95F2A3C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mgmtfinance DROP FOREIGN KEY FK_158EB9DD5DA1941
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offering_mgmt DROP FOREIGN KEY FK_8124179D95F2A3C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_bankdetails DROP FOREIGN KEY FK_26B0AE1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offering_mgmt_tasks
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mgmtfinance
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offering_mgmt
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_bankdetails
        SQL);

        // Remove columns
        $this->addSql(<<<'SQL'
            ALTER TABLE assets DROP creditScore, DROP foundingDate, DROP foundingLocation, DROP facebookUri, DROP linkedinUri, DROP youtubeUri, DROP twitterUri, DROP location, DROP logo, DROP orgWebsite
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offerings DROP dealDescription, DROP creditScore, DROP loanToValue, DROP debtInterestRate, DROP county, DROP debtTerm, DROP additional_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payouts DROP minPayment, DROP ownerObject
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP linkedinId, DROP twitterId, DROP facebookId
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // Restore tables
        $this->addSql(<<<'SQL'
            CREATE TABLE offering_mgmt_tasks (id INT AUTO_INCREMENT NOT NULL, off_id INT DEFAULT NULL, name VARCHAR(20) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, description VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, type VARCHAR(20) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, createdById INT DEFAULT 0, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, updatedBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_67DD6A57D95F2A3C (off_id), UNIQUE INDEX UNIQ_67DD6A575E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mgmtfinance (id INT AUTO_INCREMENT NOT NULL, asset_id INT DEFAULT NULL, name VARCHAR(150) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, payoutAmount NUMERIC(10, 2) NOT NULL, transType VARCHAR(50) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, fromDate DATE DEFAULT NULL, toDate DATE DEFAULT NULL, dueDate DATE DEFAULT NULL, createdById INT DEFAULT 0, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, updatedBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_158EB9DD5DA1941 (asset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE offering_mgmt (id INT AUTO_INCREMENT NOT NULL, off_id INT DEFAULT NULL, name VARCHAR(20) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, description VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, createdById INT DEFAULT 0, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, updatedBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, UNIQUE INDEX UNIQ_81241795E237E06 (name), UNIQUE INDEX UNIQ_8124179D95F2A3C (off_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_bankdetails (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, acctNumber INT NOT NULL, bankName VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, sortCode INT NOT NULL, currency VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, bankAddress VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, bankCountry VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, swiftCode VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, createdById INT DEFAULT 0, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, createdBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, updatedBy VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_unicode_ci`, INDEX IDX_26B0AE1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offering_mgmt_tasks ADD CONSTRAINT FK_67DD6A57D95F2A3C FOREIGN KEY (off_id) REFERENCES offerings (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mgmtfinance ADD CONSTRAINT FK_158EB9DD5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offering_mgmt ADD CONSTRAINT FK_8124179D95F2A3C FOREIGN KEY (off_id) REFERENCES offerings (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_bankdetails ADD CONSTRAINT FK_26B0AE1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);

        // Restore columns
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD linkedinId VARCHAR(255) DEFAULT NULL, ADD twitterId VARCHAR(255) DEFAULT NULL, ADD facebookId VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offerings ADD dealDescription VARCHAR(255) DEFAULT NULL, ADD creditScore VARCHAR(255) DEFAULT NULL, ADD loanToValue NUMERIC(15, 2) DEFAULT '0.00', ADD debtInterestRate NUMERIC(10, 2) DEFAULT NULL, ADD county VARCHAR(255) DEFAULT NULL, ADD debtTerm INT DEFAULT NULL, ADD additional_type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payouts ADD minPayment NUMERIC(15, 2) DEFAULT NULL, ADD ownerObject VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE assets ADD creditScore VARCHAR(255) DEFAULT NULL, ADD foundingDate VARCHAR(255) DEFAULT NULL, ADD foundingLocation VARCHAR(255) DEFAULT NULL, ADD facebookUri VARCHAR(255) DEFAULT NULL, ADD linkedinUri VARCHAR(255) DEFAULT NULL, ADD youtubeUri VARCHAR(255) DEFAULT NULL, ADD twitterUri VARCHAR(255) DEFAULT NULL, ADD location VARCHAR(255) DEFAULT NULL, ADD logo VARCHAR(255) DEFAULT NULL, ADD orgWebsite VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
