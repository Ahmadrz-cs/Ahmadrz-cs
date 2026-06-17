<?php

namespace App\Service\Mangopay;

use MangoPay\FilterEvents;
use MangoPay\Hook;
use MangoPay\MangoPayApi;
use MangoPay\Pagination;
use MangoPay\Sorting;
use Psr\Log\LoggerInterface;

class MangopayWebhookService
{
    public function __construct(
        private LoggerInterface $logger,
        private MangoPayApi $mangopayApi,
    ) {}

    /**
     * @return \MangoPay\Hook[] Array with objects returned from API
     * @throws \MangoPay\Libraries\Exception
     */
    public function listHooks(Pagination $pagination, array $sort): array
    {
        $sorting = new Sorting();
        foreach ($sort as $fieldName => $direction) {
            $sorting->AddField($fieldName, $direction);
        }
        return $this->mangopayApi->Hooks->GetAll($pagination, $sorting);
    }

    public function retrieveHook(string $hookId): Hook
    {
        return $this->mangopayApi->Hooks->Get($hookId);
    }

    public function createHook(Hook $hook): Hook
    {
        return $this->mangopayApi->Hooks->Create($hook);
    }

    public function updateHook(Hook $hook): Hook
    {
        return $this->mangopayApi->Hooks->Update($hook);
    }

    /**
     * @return \MangoPay\Event[] Array with objects returned from API
     * @throws \MangoPay\Libraries\Exception
     */
    public function listEvents(
        Pagination $pagination,
        ?FilterEvents $filters,
        array $sort,
    ): array {
        $sorting = new Sorting();
        foreach ($sort as $fieldName => $direction) {
            $sorting->AddField($fieldName, $direction);
        }
        return $this->mangopayApi->Events->GetAll($pagination, $filters, $sorting);
    }
}
