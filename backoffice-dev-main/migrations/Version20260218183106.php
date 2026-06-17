<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218183106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add trade orders and share trades';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Trade system core data model
        $this->addSql(
            'CREATE TABLE share_trade (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, derived TINYINT DEFAULT 0 NOT NULL, numberOfShares INT DEFAULT 0 NOT NULL, pricePerShare NUMERIC(12, 6) DEFAULT \'0.000000\' NOT NULL, tradeValue NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, buyOrder_id INT NOT NULL, sellOrder_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F774AF8BD17F50A6 (uuid), INDEX IDX_F774AF8B3E947540 (buyOrder_id), INDEX IDX_F774AF8B69C5E958 (sellOrder_id), INDEX IDX_F774AF8B3174800F (createdBy_id), INDEX IDX_F774AF8B65FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8',
        );
        $this->addSql(
            'CREATE TABLE share_trade_status_log (id INT AUTO_INCREMENT NOT NULL, notes VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, occuredAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, transitionedBy_id INT DEFAULT NULL, shareTrade_id INT NOT NULL, INDEX IDX_6669A4124A8EA82E (transitionedBy_id), INDEX IDX_6669A412C37FBED7 (shareTrade_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8',
        );
        $this->addSql(
            'CREATE TABLE trade_order (id INT AUTO_INCREMENT NOT NULL, minimumShares INT DEFAULT NULL, maximumShares INT DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, uuid BINARY(16) NOT NULL, expiration DATETIME DEFAULT NULL, transactionReference VARCHAR(255) DEFAULT NULL, direction INT NOT NULL, numberOfShares INT DEFAULT 0 NOT NULL, pricePerShare NUMERIC(12, 6) DEFAULT \'0.000000\' NOT NULL, type VARCHAR(255) DEFAULT \'market\' NOT NULL, fees NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, taxes NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, transaction_id INT DEFAULT NULL, asset_id INT NOT NULL, user_id INT NOT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_DF24437BD17F50A6 (uuid), UNIQUE INDEX UNIQ_DF24437B2FC0CB0F (transaction_id), INDEX IDX_DF24437B5DA1941 (asset_id), INDEX IDX_DF24437BA76ED395 (user_id), INDEX IDX_DF24437B3174800F (createdBy_id), INDEX IDX_DF24437B65FF1AEC (updatedBy_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8',
        );
        $this->addSql(
            'CREATE TABLE trade_order_status_log (id INT AUTO_INCREMENT NOT NULL, notes VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, occuredAt DATETIME NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, transitionedBy_id INT DEFAULT NULL, tradeOrder_id INT NOT NULL, INDEX IDX_9AB516144A8EA82E (transitionedBy_id), INDEX IDX_9AB51614F321B6E5 (tradeOrder_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8',
        );
        $this->addSql(
            'ALTER TABLE share_trade ADD CONSTRAINT FK_F774AF8B3E947540 FOREIGN KEY (buyOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'ALTER TABLE share_trade ADD CONSTRAINT FK_F774AF8B69C5E958 FOREIGN KEY (sellOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'ALTER TABLE share_trade ADD CONSTRAINT FK_F774AF8B3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE share_trade ADD CONSTRAINT FK_F774AF8B65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE share_trade_status_log ADD CONSTRAINT FK_6669A4124A8EA82E FOREIGN KEY (transitionedBy_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE share_trade_status_log ADD CONSTRAINT FK_6669A412C37FBED7 FOREIGN KEY (shareTrade_id) REFERENCES share_trade (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order ADD CONSTRAINT FK_DF24437B2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order ADD CONSTRAINT FK_DF24437B5DA1941 FOREIGN KEY (asset_id) REFERENCES assets (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order ADD CONSTRAINT FK_DF24437BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order ADD CONSTRAINT FK_DF24437B3174800F FOREIGN KEY (createdBy_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order ADD CONSTRAINT FK_DF24437B65FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order_status_log ADD CONSTRAINT FK_9AB516144A8EA82E FOREIGN KEY (transitionedBy_id) REFERENCES users (id)',
        );
        $this->addSql(
            'ALTER TABLE trade_order_status_log ADD CONSTRAINT FK_9AB51614F321B6E5 FOREIGN KEY (tradeOrder_id) REFERENCES trade_order (id)',
        );

        // New relations and migration tracker fields
        $this->addSql(
            'ALTER TABLE investments ADD tradeOrder_id INT DEFAULT NULL, ADD shareTrade_id INT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE investments ADD CONSTRAINT FK_74FD72E0F321B6E5 FOREIGN KEY (tradeOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'ALTER TABLE investments ADD CONSTRAINT FK_74FD72E0C37FBED7 FOREIGN KEY (shareTrade_id) REFERENCES share_trade (id)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_74FD72E0F321B6E5 ON investments (tradeOrder_id)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_74FD72E0C37FBED7 ON investments (shareTrade_id)',
        );
        $this->addSql('ALTER TABLE offerings ADD tradeOrder_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE offerings ADD CONSTRAINT FK_A7CD243BF321B6E5 FOREIGN KEY (tradeOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_A7CD243BF321B6E5 ON offerings (tradeOrder_id)',
        );

        // New functional relations for payment and transfer requests/orders
        $this->addSql('ALTER TABLE payment_request ADD shareTrade_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE payment_request ADD CONSTRAINT FK_22DE8175C37FBED7 FOREIGN KEY (shareTrade_id) REFERENCES share_trade (id)',
        );
        $this->addSql(
            'CREATE INDEX IDX_22DE8175C37FBED7 ON payment_request (shareTrade_id)',
        );
        $this->addSql('ALTER TABLE payment_request ADD tradeOrder_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE payment_request ADD CONSTRAINT FK_22DE8175F321B6E5 FOREIGN KEY (tradeOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'CREATE INDEX IDX_22DE8175F321B6E5 ON payment_request (tradeOrder_id)',
        );
        $this->addSql('ALTER TABLE payment_order ADD tradeOrder_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE payment_order ADD CONSTRAINT FK_A260A52AF321B6E5 FOREIGN KEY (tradeOrder_id) REFERENCES trade_order (id)',
        );
        $this->addSql(
            'CREATE INDEX IDX_A260A52AF321B6E5 ON payment_order (tradeOrder_id)',
        );
        $this->addSql(
            'ALTER TABLE transfer_request ADD shareTrade_id INT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE transfer_request ADD CONSTRAINT FK_8422FDD4C37FBED7 FOREIGN KEY (shareTrade_id) REFERENCES share_trade (id)',
        );
        $this->addSql(
            'CREATE INDEX IDX_8422FDD4C37FBED7 ON transfer_request (shareTrade_id)',
        );

        // New fields and relations for share transfer orders
        $this->addSql(
            'ALTER TABLE share_transfer_order ADD repaymentStart DATETIME DEFAULT NULL, ADD repaymentEnd DATETIME DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE share_transfer_request ADD shareTrade_id INT DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE share_transfer_request ADD CONSTRAINT FK_9D883A4CC37FBED7 FOREIGN KEY (shareTrade_id) REFERENCES share_trade (id)',
        );
        $this->addSql(
            'CREATE INDEX IDX_9D883A4CC37FBED7 ON share_transfer_request (shareTrade_id)',
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // New fields and relations for share transfer orders
        $this->addSql(
            'ALTER TABLE share_transfer_order DROP repaymentStart, DROP repaymentEnd',
        );
        $this->addSql(
            'ALTER TABLE share_transfer_request DROP FOREIGN KEY FK_9D883A4CC37FBED7',
        );
        $this->addSql('DROP INDEX IDX_9D883A4CC37FBED7 ON share_transfer_request');
        $this->addSql('ALTER TABLE share_transfer_request DROP shareTrade_id');

        // New relations and migration tracker fields
        $this->addSql('ALTER TABLE investments DROP FOREIGN KEY FK_74FD72E0F321B6E5');
        $this->addSql('ALTER TABLE investments DROP FOREIGN KEY FK_74FD72E0C37FBED7');
        $this->addSql('DROP INDEX UNIQ_74FD72E0F321B6E5 ON investments');
        $this->addSql('DROP INDEX UNIQ_74FD72E0C37FBED7 ON investments');
        $this->addSql('ALTER TABLE investments DROP tradeOrder_id, DROP shareTrade_id');
        $this->addSql('ALTER TABLE offerings DROP FOREIGN KEY FK_A7CD243BF321B6E5');
        $this->addSql('DROP INDEX UNIQ_A7CD243BF321B6E5 ON offerings');
        $this->addSql('ALTER TABLE offerings DROP tradeOrder_id');

        // New functional relations for payment and transfer requests/orders
        $this->addSql(
            'ALTER TABLE payment_request DROP FOREIGN KEY FK_22DE8175C37FBED7',
        );
        $this->addSql('DROP INDEX IDX_22DE8175C37FBED7 ON payment_request');
        $this->addSql('ALTER TABLE payment_request DROP shareTrade_id');
        $this->addSql(
            'ALTER TABLE payment_request DROP FOREIGN KEY FK_22DE8175F321B6E5',
        );
        $this->addSql('DROP INDEX IDX_22DE8175F321B6E5 ON payment_request');
        $this->addSql('ALTER TABLE payment_request DROP tradeOrder_id');
        $this->addSql('ALTER TABLE payment_order DROP FOREIGN KEY FK_A260A52AF321B6E5');
        $this->addSql('DROP INDEX IDX_A260A52AF321B6E5 ON payment_order');
        $this->addSql('ALTER TABLE payment_order DROP tradeOrder_id');
        $this->addSql(
            'ALTER TABLE transfer_request DROP FOREIGN KEY FK_8422FDD4C37FBED7',
        );
        $this->addSql('DROP INDEX IDX_8422FDD4C37FBED7 ON transfer_request');
        $this->addSql('ALTER TABLE transfer_request DROP shareTrade_id');

        // Trade system core data model
        $this->addSql('ALTER TABLE share_trade DROP FOREIGN KEY FK_F774AF8B3E947540');
        $this->addSql('ALTER TABLE share_trade DROP FOREIGN KEY FK_F774AF8B69C5E958');
        $this->addSql('ALTER TABLE share_trade DROP FOREIGN KEY FK_F774AF8B3174800F');
        $this->addSql('ALTER TABLE share_trade DROP FOREIGN KEY FK_F774AF8B65FF1AEC');
        $this->addSql(
            'ALTER TABLE share_trade_status_log DROP FOREIGN KEY FK_6669A4124A8EA82E',
        );
        $this->addSql(
            'ALTER TABLE share_trade_status_log DROP FOREIGN KEY FK_6669A412C37FBED7',
        );
        $this->addSql('ALTER TABLE trade_order DROP FOREIGN KEY FK_DF24437B2FC0CB0F');
        $this->addSql('ALTER TABLE trade_order DROP FOREIGN KEY FK_DF24437B5DA1941');
        $this->addSql('ALTER TABLE trade_order DROP FOREIGN KEY FK_DF24437BA76ED395');
        $this->addSql('ALTER TABLE trade_order DROP FOREIGN KEY FK_DF24437B3174800F');
        $this->addSql('ALTER TABLE trade_order DROP FOREIGN KEY FK_DF24437B65FF1AEC');
        $this->addSql(
            'ALTER TABLE trade_order_status_log DROP FOREIGN KEY FK_9AB516144A8EA82E',
        );
        $this->addSql(
            'ALTER TABLE trade_order_status_log DROP FOREIGN KEY FK_9AB51614F321B6E5',
        );
        $this->addSql('DROP TABLE share_trade');
        $this->addSql('DROP TABLE share_trade_status_log');
        $this->addSql('DROP TABLE trade_order');
        $this->addSql('DROP TABLE trade_order_status_log');
    }
}
