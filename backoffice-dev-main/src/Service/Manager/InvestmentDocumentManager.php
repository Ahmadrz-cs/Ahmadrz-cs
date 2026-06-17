<?php

namespace App\Service\Manager;

use App\Entity\InvestmentDocuments;
use App\Service\Manager\BaseManager;

class InvestmentDocumentManager extends BaseManager
{
    protected $entityClass = InvestmentDocuments::class;

    public function findDocumentsForInvestment($offset, $limit, $investment_id)
    {
        $condition = ['investment' => $investment_id];

        $documents = $this->findAllValue($offset, $limit, $condition);

        return $documents;
    }
}
