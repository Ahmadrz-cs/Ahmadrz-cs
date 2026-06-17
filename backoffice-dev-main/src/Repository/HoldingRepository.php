<?php

namespace App\Repository;

use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;

class HoldingRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function getShareHoldingsAggregate(?int $assetId = null): ?array
    {
        $conn = $this->em->getConnection();
        $columns = implode(', ', [
            'asset',
            'assetId',
            'SUM(currentHolding) AS sharesInCirculation',
        ]);
        $sql = 'SELECT ' . $columns . ' FROM getShareHoldings';
        if ($assetId) {
            $sql .= ' WHERE assetId = ' . $assetId;
        }
        $sql .= ' GROUP BY assetId';
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getExtendedShareHoldings(): ?array
    {
        $conn = $this->em->getConnection();
        $columns = implode(', ', [
            'shr.*',
            'ast.companyNumber AS assetSPV',
            'usr.firstname',
            'usr.lastname',
            'usr.email AS userContactEmail',
        ]);
        $sql = 'SELECT ' . $columns . ' FROM getShareHoldings shr';
        $sql .= ' LEFT JOIN assets ast ON shr.assetId = ast.id';
        $sql .= ' LEFT JOIN users usr ON shr.userId = usr.id';
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getShareHoldings(array $filters = []): ?array
    {
        $conn = $this->em->getConnection();
        $sql = $this->buildShareholdingQuery($filters);
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getShareTrades(array $filters = []): ?array
    {
        $conn = $this->em->getConnection();
        $sql = $this->buildShareTradeQuery($filters);
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function buildShareholdingQuery(array $filters = []): string
    {
        $filters = array_filter(
            $filters,
            function ($value, $key) {
                return (
                    in_array($key, [
                        'assetId',
                        'currentHolding',
                        'userId',
                        'capitalRepayments',
                    ])
                    && is_numeric($value)
                );
            },
            ARRAY_FILTER_USE_BOTH,
        );

        $sql = 'SELECT * FROM getShareHoldings';
        foreach ($filters as $key => $value) {
            if (strpos($sql, 'WHERE')) {
                $sql .= ' AND ';
            } else {
                $sql .= ' WHERE ';
            }

            if (in_array($key, ['currentHolding', 'capitalRepayments']) && $value) {
                $operator = ' >= ';
            } else {
                $operator = ' = ';
            }

            $sql .= $key . $operator . $value;
        }
        return $sql .= ' ORDER BY asset ASC, currentHolding DESC, user ASC';
    }

    public function buildShareTradeQuery(array $filters = []): string
    {
        // Sanitise filters and build safe query
        $aggregate = (bool) ($filters['aggregate'] ?? false);
        $filters = array_filter(
            $filters,
            function ($value, $key) {
                return (
                    in_array($key, [
                        'assetId',
                        'buyerId',
                        'sellerId',
                        'settledFrom',
                        'settledTo',
                    ])
                    && (
                        is_numeric($value)
                        || is_string($value) && Helper::isValidDate($value)
                    )
                );
            },
            ARRAY_FILTER_USE_BOTH,
        );

        $sql = 'SELECT';
        if ($aggregate) {
            $columns = implode(', ', [
                'asset',
                'seller',
                'SUM(numberOfShares) AS numberOfShares',
                'MAX(settledOn) AS lastSettled',
                'COUNT(investment) AS tradeCount',
                'assetId',
                'sellerId',
            ]);
            $sql .= ' ' . $columns . ' FROM getShareTrades';
        } else {
            $sql .= ' * FROM getShareTrades';
        }

        foreach ($filters as $key => $value) {
            if (strpos($sql, 'WHERE')) {
                $sql .= ' AND ';
            } else {
                $sql .= ' WHERE ';
            }

            if ($key == 'settledFrom') {
                $operator = ' >= ';
            } elseif ($key == 'settledTo') {
                $operator = ' < ';
            } else {
                $operator = ' = ';
            }

            if (Helper::isValidDate($value) && preg_match('~^settled\w+$~', $key)) {
                $key = 'settledOn';
                $value = "'" . $value . "'";
            }
            $sql .= $key . $operator . $value;
        }

        if ($aggregate) {
            $sql .= ' GROUP BY asset, seller';
        }
        $sql .= ' ORDER BY settledOn DESC, asset ASC';

        return $sql;
    }
}
