<?php

namespace App\Service\Manager;

use App\Entity\OfferingDocuments;
use App\Service\Manager\BaseManager;

class OfferingDocumentManager extends BaseManager
{
    protected $entityClass = OfferingDocuments::class;

    public function findDocumentsForOffering($offset, $limit, $offering_id)
    {
        $condition = ['offering' => $offering_id];

        $documents = $this->findAllValue($offset, $limit, $condition);

        return $documents;
    }
}
