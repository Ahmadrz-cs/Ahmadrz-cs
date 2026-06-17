<?php

namespace App\Service\Manager;

use App\Entity\Document;
use App\Entity\UserDocument;
use App\Service\Manager\BaseManager;

class UserDocumentManager extends BaseManager
{
    protected $entityClass = UserDocument::class;

    public function findDocumentsForUser($offset, $limit, $user_id)
    {
        $condition = ['user' => $user_id];

        $documents = $this->findAllValue($offset, $limit, $condition);

        return $documents;
    }
}
