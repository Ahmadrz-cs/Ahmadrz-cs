<?php

namespace App\Service\Manager;

use App\Entity\AssetDocuments;
use App\Repository\AssetDocumentRepository;
use App\Service\DocumentService;
use App\Service\Manager\AssetManagerV2;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AssetDocumentManagerV2
{
    public function __construct(
        private AssetDocumentRepository $assetDocumentRepository,
        private EntityManagerInterface $entityManager,
        private DocumentService $documentService,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AssetManagerV2 $assetManager,
    ) {}

    /**
     * Returns an pagerfanta collection of documents realted to the asset id
     * Non admin users cannot view related documents if the asset is not published
     */
    public function getDocumentsByAssetId(
        int $assetId,
        int $page = 1,
        int $limit = 15,
    ): ?Pagerfanta {
        //Check asset exists
        //Throw a AccessDeniedHttpException if user is not admin and asset is not published
        if ($this->assetManager->getAsset($assetId)) {
            return $this->assetDocumentRepository->findByAssetId(
                $assetId,
                $page,
                $limit,
            );
        }

        return null;
    }

    /**
     * Returns an asset document.
     * Non admin users cannot get the document if the asset is not published.
     */
    public function getDocumentByAssetIdAndDocumentId(
        int $assetId,
        int $docId,
    ): ?AssetDocuments {
        //Check asset exists
        //Throw a AccessDeniedHttpException if user is not admin and asset is not published
        if ($this->assetManager->getAsset($assetId)) {
            $assetDoc = $this->assetDocumentRepository->findByAssetIdAndDocId(
                $assetId,
                $docId,
            );

            if (!$assetDoc) {
                return null;
            }

            $document = $assetDoc->getDocument();

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

            $assetDoc->setDocument($document);

            return $assetDoc;
        }

        return null;
    }

    public function deleteAssetDocument($assetId, $docId): void
    {
        $document = $this->getDocumentByAssetIdAndDocumentId($assetId, $docId);

        if (!$document) {
            throw new NotFoundHttpException('Document with id: '
            . $docId
            . ' does not exist for Asset with id: '
            . $assetId);
        }

        $this->assetDocumentRepository->remove($document);
        $this->entityManager->flush();
    }
}
