<?php

namespace App\Service\Manager;

use App\Repository\OfferingDocumentRepository;
use App\Repository\OfferingRepository;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OfferingDocumentManagerV2
{
    public function __construct(
        private OfferingDocumentRepository $offeringDocumentRepository,
        private OfferingRepository $offeringRepository,
        private EntityManagerInterface $entityManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private DocumentService $documentService,
    ) {}

    public function getDocumentsByOfferingId($offeringId)
    {
        $document = $this->offeringDocumentRepository->findByOfferingId($offeringId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $document;
        } else {
            if (!empty($document)) {
                $offering = $this->offeringRepository->find($offeringId);
                if ($offering->getLifecycleStatus() == 'published') {
                    return $document;
                } else {
                    throw new AccessDeniedHttpException(sprintf('You do not have access to view documents realted to offering with id '
                    . $offeringId));
                }
            }
        }
        return null;
    }

    public function getDocumentByOfferingIdAndDocumentId($offeringId, $docId)
    {
        $offeringDoc = $this->offeringDocumentRepository->getDocumentByOfferingIdAndDocumentId(
            $offeringId,
            $docId,
        );

        if (empty($offeringDoc)) {
            return null;
        }

        $document = $offeringDoc->getDocument();

        if (!empty($document->getDocumentUrl())) {
            try {
                $docContent = $this->documentService->read(
                    $document->getDocumentUrl(),
                    'public',
                );
                $document->setDocumentContent(base64_encode($docContent));
            } catch (\Exception $e) {
                return null;
            }
        }

        $offeringDoc->setDocument($document);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $offeringDoc;
        } else {
            if ($offeringDoc->getOffering()->getLifecycleStatus() == 'published') {
                return $offeringDoc;
            } else {
                throw new AccessDeniedHttpException(sprintf('You do not have access to view documents related to offering with id '
                . $offeringId));
            }
        }

        return null;
    }
}
