<?php

namespace App\Service\Manager;

use App\Entity\InvestmentDocuments;
use App\Entity\User;
use App\Service\Manager\DocumentManager;

class InvestmentDocumentManagerV2
{
    public function __construct(
        private DocumentManager $documentManager,
    ) {}

    public function createShareCertificate(
        InvestmentDocuments $investmentDocument,
        User $user,
    ): InvestmentDocuments {
        $document = $this->documentManager->linkDocument(
            $investmentDocument->getDocument(),
            $investmentDocument->getDocument()->getFile(),
            'private',
            'investment/' . $investmentDocument->getInvestment()->getId(),
        );
        $document->setTag('share_certificate');
        $document->setCreatedById($user->getId());
        $investmentDocument->setDocument($document);

        return $investmentDocument;
    }
}
