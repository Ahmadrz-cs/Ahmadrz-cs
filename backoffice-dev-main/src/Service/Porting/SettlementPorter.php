<?php

namespace App\Service\Porting;

use App\Entity\Enum\TransferType;
use App\Entity\TransferOrder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SettlementPorter
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {}

    public function portSettlements(): int|string
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'UPDATE transfer_request trq
            LEFT JOIN investments inv ON inv.id = trq.investment_id
            SET trq.shareTrade_id = inv.shareTrade_id
            WHERE trq.investment_id IS NOT NULL';

        $statement = $connection->prepare($sql);
        return $statement->executeStatement();
    }

    public function scanSettlementsOrders(): array
    {
        $connection = $this->entityManager->getConnection();
        $sql = "SELECT
            trq.transferOrder_id AS id,
            tro.transferType AS transferType,
            COUNT(trq.id) AS count,
            SUM(CASE
                WHEN trq.shareTrade_id IS NOT NULL THEN 1
                ELSE 0
            END) AS 'hasShareTrade'
        FROM transfer_request trq
        LEFT JOIN transfer_order tro ON tro.id = trq.transferOrder_id
        WHERE trq.investment_id IS NOT NULL
        GROUP BY trq.transferOrder_id;";

        $statement = $connection->prepare($sql);
        return $statement->executeQuery()->fetchAllAssociative();
    }
}
