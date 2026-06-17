<?php

namespace App\Service\Manager;

use App\Entity\AssetDocuments;
use App\Service\Manager\BaseManager;

class AssetDocumentManager extends BaseManager
{
    protected $entityClass = AssetDocuments::class;

    public function findDocumentsForAsset($offset, $limit, $asset_id)
    {
        $condition = ['asset' => $asset_id];

        $documents = $this->findAllValue($offset, $limit, $condition);

        return $documents;
    }
}
