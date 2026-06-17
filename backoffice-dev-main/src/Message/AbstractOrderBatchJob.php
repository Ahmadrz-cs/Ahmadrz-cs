<?php

namespace App\Message;

abstract readonly class AbstractOrderBatchJob
{
    public const int BATCH_LIMIT = 10;

    public int $batchSize;

    public function __construct(
        public string $orderFqcn,
        public int $orderId,
        public int $submittedByUserId,
        public bool $autoContinue = true,
        int $batchSize = self::BATCH_LIMIT,
    ) {
        // Only batchSize is not using constructor property promotion
        // Initialise batchSize separately as we need apply a function
        $this->batchSize = min(self::BATCH_LIMIT, $batchSize);
    }
}
