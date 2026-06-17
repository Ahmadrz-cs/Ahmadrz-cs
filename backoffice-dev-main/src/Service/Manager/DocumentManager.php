<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 17/02/17
 * Time: 23:53
 */

namespace App\Service\Manager;

use App\Entity\AssetDocuments;
use App\Entity\Document;
use App\Entity\InvestmentDocuments;
use App\Entity\OfferingDocuments;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentManager
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private DocumentService $documentService,
        private SluggerInterface $slugger,
        private string $cdnDomainName,
    ) {}

    /**
     * Helper for API controllers to map POST fields to correct entity property
     * $visibility is "public" or "private", default to private, anything else will be considered public
     * @param $parms
     * @param string $prefix
     *
     * @return Document
     */
    public function buildDocument($param, $visibility = 'private', $prefix = '')
    {
        $document = new Document();

        /** @var User $user */
        $user = $this->security->getUser();
        $document->setCreatedById($user->getId());
        $fileName = null;
        // file_name should be checked by controller that calls buildDocuments
        if (!empty($param->file_name)) {
            if (!empty($param->file_type)) {
                $fileName = $this->getSafeFileName(
                    $param->file_name,
                    $param->file_type,
                );
            } else {
                $fileName = $this->getSafeFileName($param->file_name);
            }
            $document->setName($fileName);
            $document->setFilename($fileName);
        }
        if (!empty($param->file_description)) {
            $document->setDescription($param->file_description);
        }
        if (!empty($param->file_type)) {
            $document->setType($param->file_type);
        }
        if (!empty($param->category)) {
            $document->setCategory($param->category);
        }
        if (!empty($param->file_alias)) {
            $document->setAlias($param->file_alias);
        }
        if (!empty($param->tag)) {
            $document->setTag($param->tag);
        }
        if (!empty($param->document_url)) {
            // if making a document copy, set url with original's url
            // remove cdn part if it exists to ensure url is relative
            $document->setDocumentUrl($this->formatDocumentUrl($param->document_url));
        }
        if (!empty($param->document_content)) {
            // if for whatever reason filename is empty from before...
            // mainly for the static analysis not to get confused
            if (!$fileName) {
                if (!empty($param->file_type)) {
                    $fileName = $this->getSafeFileName(
                        $param->file_name,
                        $param->file_type,
                    );
                } else {
                    $fileName = $this->getSafeFileName($param->file_name);
                }
            }
            // if prefix is empty, will save to root of filesystem MOUNT - mount configured in config.yml
            if ($prefix) {
                $filePath =
                    $prefix
                    . '/'
                    . bin2hex(openssl_random_pseudo_bytes(8))
                    . '_'
                    . $fileName;
            } else {
                $filePath = bin2hex(openssl_random_pseudo_bytes(8)) . '_' . $fileName;
            }
            try {
                $this->documentService->put(
                    $filePath,
                    base64_decode($param->document_content),
                    $visibility,
                );
                $document->setDocumentUrl($filePath);
            } catch (\Exception $e) {
                $this->logger->error('Unable to save document to file store: ', [$e->getMessage()]);
            }

            // $document->setDocumentContent(base64_decode($param->document_content));
        }
        return $document;
    }

    /**
     * Helper for API controllers to map POST fields to correct entity property
     * Link a file to a pre-existing document entity
     * $visibility is "public" or "private", default to private, anything else will be considered public
     * @param Document $partialDocument
     * @param UploadedFile $file
     * @param string $visibility
     * @param string $prefix
     *
     * @return Document
     */
    public function linkDocument(
        $partialDocument,
        $file,
        $visibility = 'private',
        $prefix = '',
    ) {
        $document = $partialDocument;

        // $fileSize = $file->getSize();
        $fileType = $file->getMimeType();
        $fileContent = file_get_contents($document->getFile()->getPathName());

        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileExtension = $file->guessExtension();
        $fileName = $this->slugger->slug($originalFileName, '_') . '.' . $fileExtension;

        $document->setType($fileType);
        $document->setFilename($fileName);
        // still setting documentContent (remove this at later date)
        // $document->setDocumentContent($fileContent);

        // if prefix is empty, will save to root of filesystem MOUNT - mount configured in config.yml
        if ($prefix) {
            $filePath =
                $prefix
                . '/'
                . bin2hex(openssl_random_pseudo_bytes(8))
                . '_'
                . $fileName;
        } else {
            $filePath = bin2hex(openssl_random_pseudo_bytes(8)) . '_' . $fileName;
        }

        try {
            $this->documentService->put($filePath, $fileContent, $visibility);
            $document->setDocumentUrl($filePath);
        } catch (\Exception $e) {
            $this->logger->error('Unable to save document to file store: ', [$e->getMessage()]);
        }

        return $document;
    }

    public function getUnsyncedDocs(string $docType): array
    {
        /**
         * Document is not synced if
         * - Has no documentUrl
         * - DocumentUrl does not have corresponding object
         */
        $this->logger->debug('Getting unsynced docs');
        $emRep = $this->getRepositoryForDocType($docType);
        $unsyncedDocs = $emRep->getDocumentInfoWithDocCriteria([
            'documentUrl' => 'IS NULL',
        ], false);
        // $docsWithUrls = $emRep->getDocumentInfoWithDocCriteria([
        //     'documentUrl' => 'IS NOT NULL'
        // ], false);

        // $visibility = $this->getVisibilityForDocType($docType);
        // $docsOnFs = $this->documentService->listContents($docType, $visibility);
        // $docPathsOnFs = $this->getFileInfoList($docsOnFs, 'path');
        // $this->logger->debug(json_encode($docPathsOnFs));
        // foreach ($docsWithUrls as $doc) {
        //     if (
        //         !$this->generatePrefix($doc, $docType)
        //         || !in_array($doc->getDocument()->getDocumentUrl(), $docPathsOnFs)
        //     ) {
        //         $unsyncedDocs[] = $doc;
        //     }
        // }
        return $unsyncedDocs;
    }

    /**
     * @param string $docType
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepositoryForDocType($docType)
    {
        switch ($docType) {
            case 'asset':
                $entityRepo = $this->entityManager->getRepository(AssetDocuments::class);
                break;
            case 'offering':
                $entityRepo = $this->entityManager->getRepository(OfferingDocuments::class);
                break;
            case 'user':
                $entityRepo = $this->entityManager->getRepository(UserDocument::class);
                break;
            case 'investment':
                $entityRepo = $this->entityManager->getRepository(InvestmentDocuments::class);
                break;
            default:
                return [];
        }
        return $entityRepo;
    }

    /**
     * @param string $docType
     * @return string
     */
    public function getVisibilityForDocType($docType)
    {
        if (in_array($docType, ['user', 'investment'])) {
            return 'private';
        } else {
            return 'public';
        }
    }

    /**
     * @param mixed $doc
     * @param string $docType
     * @return string|false
     */
    public function generatePrefix($doc, $docType)
    {
        switch ($docType) {
            case 'asset':
                $parentEntity = $doc->getAsset();
                break;
            case 'offering':
                $parentEntity = $doc->getOffering();
                break;
            case 'user':
                $parentEntity = $doc->getUser();
                break;
            case 'investment':
                $parentEntity = $doc->getInvestment();
                break;
            default:
                $parentEntity = false;
        }

        if (empty($parentEntity)) {
            return false;
        } else {
            return (
                $docType
                . '/'
                . $parentEntity->getId()
                . '/'
                . bin2hex(openssl_random_pseudo_bytes(8))
                . '_'
            );
        }
    }

    /**
     * Flattens response from listContents
     * @param array $fileStoreContents
     * @return array
     */
    public function getFileInfoList(
        $fileStoreContents,
        $infoType = 'path',
        $includeDir = false,
    ) {
        $info = [];
        foreach ($fileStoreContents as $fileInfo) {
            if ($includeDir) {
                $info[] = $fileInfo[$infoType];
            } else {
                if ($fileInfo['type'] == 'file') {
                    $info[] = $fileInfo[$infoType];
                }
            }
        }
        return $info;
    }

    public function generatePublicCdnUrls(array $objects): array
    {
        /**
         * Note that this uses a nested loop, worse case performance will be poor
         */
        foreach ($objects as $object) {
            $documents = $object->getDocuments();
            foreach ($documents as $objDocs) {
                $document = $objDocs->getDocument();
                $url = $document->getDocumentUrl();

                if (!empty($url)) {
                    $cdnUrl = 'https://' . $this->cdnDomainName . '/' . $url;
                    $document->setDocumentUrl($cdnUrl);
                    $objDocs->setDocument($document);
                }
            }
        }
        return $objects;
    }

    public function getPublicCdnUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        return "https://{$this->cdnDomainName}/{$path}";
    }

    public function formatDocumentUrl(string $documentUrl): string
    {
        $searchPattern = '~^\S*' . $this->cdnDomainName . '/.*$~';
        if (preg_match($searchPattern, $documentUrl)) {
            $documentUrl = preg_replace(
                '~^\S*' . $this->cdnDomainName . '/~',
                '',
                $documentUrl,
            );
        }
        return $documentUrl;
    }

    /**
     * Helper for API managers to save a documemt to the filestore
     * $visibility is "public" or "private", default to private, anything else will be considered public
     */
    public function saveInFileStore(
        Document $document,
        string $visibility = 'private',
        string $prefix = '',
        string $documentContent = '',
    ): bool {
        $fileName = $document->getFilename();
        $documentContent = $documentContent ?? $document->getDocumentContent();

        if ($prefix) {
            $filePath =
                $prefix
                . '/'
                . bin2hex(openssl_random_pseudo_bytes(8))
                . '_'
                . $fileName;
        } else {
            $filePath = bin2hex(openssl_random_pseudo_bytes(8)) . '_' . $fileName;
        }

        if ($documentContent) {
            $documentContent = base64_decode($documentContent);
        }

        try {
            $this->documentService->put($filePath, $documentContent, $visibility);
            $document->setDocumentUrl($filePath);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Unable to save document to file store: ', [$e->getMessage()]);
        }
        return false;
    }

    /**
     * Helper for API managers to attach the document content to a document entity
     * $visibility is "public" or "private", default to private, anything else will be considered public
     *
     * @param $document
     * @param string $visibility
     *
     * @return Document|null
     */
    public function getFileContent(
        Document $document,
        $visibility = 'private',
    ): ?Document {
        if (!empty($document->getDocumentUrl())) {
            try {
                $docContent = $this->documentService->read(
                    $document->getDocumentUrl(),
                    $visibility,
                );
                $document->setDocumentContent(base64_encode($docContent));
            } catch (\Exception $e) {
                return null;
            }
        }

        return $document;
    }

    /**
     * Helper for API managers to delete a document from the file store
     * $visibility is "public" or "private", default to private, anything else will be considered public
     *
     * @param Document $document
     * @param string $visibility
     *
     * @return bool
     */
    public function deleteDocument(Document $document, $visibility = 'private'): bool
    {
        if (!empty($document->getDocumentUrl())) {
            try {
                $this->documentService->delete(
                    $document->getDocumentUrl(),
                    $visibility,
                );
                return true;
            } catch (\Exception $e) {
                $this->logger->error('Unable to delete document to file store: ', [$e->getMessage()]);
                return false;
            }
        }

        return false;
    }

    /**
     * Only for buildDocument
     */
    private function getSafeFileName(
        string $originalFileName,
        ?string $fileType = null,
    ): string {
        $originalFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
        if ($fileType) {
            $mimeTypes = new MimeTypes();
            $exts = $mimeTypes->getExtensions($fileType);
            if ($exts) {
                $fileExtension = $exts[0];
            } else {
                $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
            }
        } else {
            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        }
        $sluggedFileName = $this->slugger->slug($originalFileName, '_');
        return "{$sluggedFileName}.{$fileExtension}";
    }
}
