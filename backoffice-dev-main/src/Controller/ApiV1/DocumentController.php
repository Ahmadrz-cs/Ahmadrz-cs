<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Entity\Document;
use App\Entity\DocumentRepository;
use App\Service\DocumentService;
use App\Service\Manager\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DocumentController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private DocumentService $documentService,
    ) {}

    /**
     * This will eventually become deprecated functionality changed to accommodate "direct access"
     * Currently, this endpoint only supports public files (asset/offering docs)
     * This is adequate since no user/investment docs are available on frontend anyway
     * @return JsonResponse
     */
    #[IsGranted('ROLE_USER')]
    #[Get(
        '/%api_network_path%/documents/{document_id}',
        name: 'api_get_single_document_by_id',
    )]
    public function getSingleDocument(int $document_id)
    {
        $this->logger->info('Get Document: ' . $document_id);

        try {
            /** @var DocumentRepository $documentRepository */
            $documentRepository = $this->doctrine
                ->getManager()
                ->getRepository(Document::class);

            /** @var Document $resultDocument */
            $resultDocument = $documentRepository->find($document_id);

            //entitlement check
            if (empty($resultDocument->getTag())) {
                //only return documents that have a tag
                return new ErrorResponse(
                    ErrorResponse::ERROR_DOCUMENT_NOT_FOUND,
                    'Tag field has not been set',
                );
            }

            $visibility = $this->_getDocVisibilityByTag($resultDocument->getTag());
            if (!$visibility) {
                //only return documents that have a tag
                return new ErrorResponse(
                    ErrorResponse::ERROR_DOCUMENT_NOT_FOUND,
                    'Tag field has not been set',
                );
            }

            $this->logger->debug(
                'Doc created user id: ' . $resultDocument->getCreatedById(),
            );

            $publicDocTags = [
                'property_photos',
                'floor_plan',
                'logo',
                'avatar',
                'calculations',
            ];
            $userDocTags = [
                'proof_of_identity',
                'proof_of_address',
                'proof_of_company',
            ];
            // auth users are allowed to access all doc tags
            $authDocTags = array_merge(
                $userDocTags,
                $publicDocTags,
                [
                    'share_certificate',
                    'read_to_activate',
                ],
            );

            //based on the tag determine the entitlement required to return it
            if (empty($this->getUser()->getUserIdentifier())) {
                $this->logger->debug('Retrieving public document');
                //public route
                if (!in_array($resultDocument->getTag(), $publicDocTags)) {
                    unset($resultDocument);
                    $this->logger->debug('Document not tagged');
                }
            } else {
                //auth user
                $this->logger->debug(
                    'Retrieving auth-only document for user id: '
                        . $this->getUser()->getId(),
                );

                // only consider returing tagged docs
                if (in_array($resultDocument->getTag(), $authDocTags)) {
                    // user docs are only retrievable by owner of the doc
                    // consider making user docs unretrievable by API - only allow access via CMS
                    if (
                        in_array($resultDocument->getTag(), $userDocTags)
                        && $this->getUser()->getId() !== $resultDocument->getCreatedById()
                    ) {
                        unset($resultDocument);
                    }
                    // share certificate is only retrievable by related user
                    elseif (
                        $resultDocument->getTag() == 'share_certificate'
                        && !$this->_isShareCertOwner($document_id, $this->getUser())
                    ) {
                        unset($resultDocument);
                    }
                } else {
                    unset($resultDocument);
                    $this->logger->debug('Document not tagged');
                }
            }

            /**
             * Either no document found or not allowed to access - return 404 equivalent
             * Do not leak exitence with 403 response
             * If doc exists and allowed to access, get the actual content from file store
             */
            if (empty($resultDocument)) {
                return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_NOT_FOUND);
            } else {
                // $this->logger->info('Getting doc from file store: ' . $resultDocument->getFilename());
                if (!empty($resultDocument->getDocumentUrl())) {
                    try {
                        $docContent = $this->documentService->read(
                            $resultDocument->getDocumentUrl(),
                            $visibility,
                        );
                        $resultDocument->setDocumentContent(base64_encode($docContent));
                    } catch (\Exception $e) {
                        $this->logger->error(
                            'No file found at document url: '
                                . $resultDocument->getDocumentUrl()
                                . ' Error msg: '
                                . $e->getMessage(),
                        );
                        return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_NOT_FOUND);
                    }
                } else {
                    $this->logger->error(
                        'Document ' . $resultDocument->getId() . ' has no url',
                    );
                    return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_NOT_FOUND);

                    // $resultDocument->setDocumentContent(base64_encode(stream_get_contents($resultDocument->getDocumentContent())));
                }
            }
            return new SuccessResponse([
                'document' => $resultDocument,
            ]);
        } catch (\Exception $e) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                $e->getMessage(),
            );
        }
    }

    /**
     * @param string $docTag
     * @return string|false
     */
    protected function _getDocVisibilityByTag($docTag)
    {
        $publicDocTags = [
            'property_photos',
            'floor_plan',
            'logo',
            'avatar',
            'calculations',
            'read_to_activate',
        ];
        $privateDocTags = [
            'proof_of_identity',
            'proof_of_address',
            'proof_of_company',
            'share_certificate',
        ];

        if (in_array($docTag, $publicDocTags)) {
            return 'public';
        }
        if (in_array($docTag, $privateDocTags)) {
            return 'private';
        }
        return false;
    }

    /**
     * @param string $docTag
     * @return string|false
     */
    protected function _isShareCertOwner($docId, $user)
    {
        $this->logger->debug(
            'Checking share certificate ownership: Doc_' . $docId . ' User_'
                . $user->getId(),
        );
        $userInvDocIds = [];
        // get all investments for user
        $userInvestments = $user->getInvestments();

        // loop through investments and get all investment docs for user
        foreach ($userInvestments as $inv) {
            $invDocs = $inv->getDocuments();
            foreach ($invDocs as $doc) {
                $userInvDocIds[] = $doc->getDocument()->getId();
            }
        }
        // check if docid is in that array
        if (in_array($docId, $userInvDocIds)) {
            return true;
        } else {
            return false;
        }
    }
}
