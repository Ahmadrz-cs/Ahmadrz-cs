<?php

namespace App\Dto;

use App\Entity\Document;

final class DocumentAssembler
{
    /**
     * @param DocumentDTO $documentDTO
     * @param Document|null $document
     * @return Document
     */
    public function readDTO(
        DocumentDTO $documentDTO,
        ?Document $document = null,
    ): Document {
        if (!$document) {
            $document = new Document();
        }

        $document->setName($documentDTO->getFileName() ?? $document->getFileName());
        $document->setFilename($document->getName());
        $document->setTag($documentDTO->getTag() ?? $document->getTag());

        if ($documentDTO instanceof DocumentPatchDTO) {
            $document->setDescription(
                $documentDTO->getDescription() ?? $document->getDescription(),
            );
        }

        //Note: this is not persisted in db. Useful for tasks such as S3 upload.
        $document->setDocumentContent($documentDTO->getDocumentContent());

        return $document;
    }

    /**
     * @param Document $document
     * @param DocumentDTO $documentDTO
     * @return Document
     */
    public function updateDocument(
        Document $document,
        DocumentDTO $documentDTO,
    ): Document {
        return $this->readDTO($documentDTO, $document);
    }

    /**
     * @param DocumentDTO $documentDTO
     * @return Document
     */
    public function createDocument(DocumentDTO $documentDTO): Document
    {
        return $this->readDTO($documentDTO);
    }
}
