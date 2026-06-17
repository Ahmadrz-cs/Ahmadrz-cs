<?php

namespace ClientBundle\Service;

use AppBundle\Entity\ShareTrade;
use Psr\Log\LoggerInterface;

class ExportService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public static function generateFileName(string $prefix, string $extension): string
    {
        return $prefix . date(\DateTime::ATOM, time()) . '.' . $extension;
    }

    public function formatTradeHistoryCallable(string|int|null $userId): \Closure
    {
        return function (ShareTrade $row) use ($userId): array {
            $output = [];
            $direction = 'N/A';
            if ($userId) {
                if ($userId == $row->buyerId) {
                    $direction = 'buy';
                }
                if ($userId == $row->sellerId) {
                    $direction = 'sell';
                }
            }
            $output['direction'] = $direction;
            $output['uuid'] = $row->uuid;
            $output['price'] = $row->pricePerShare;
            $output['shares'] = $row->numberOfShares;
            $output['value'] = $row->tradeValue;
            $output['asset'] = $row->assetName;
            $output['status'] = $row->status->value;
            $output['created'] = $row->createdAt->format('r');
            $output['settled'] = $row->statusOccuredAt->format('r');
            return $output;
        };
    }
}
