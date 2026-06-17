<?php

namespace App\Service\Mapper;

use App\Dto\Document\DocumentResponseDto;
use App\Entity\Interface\RelatedDocumentInterface;
use App\Service\Manager\DocumentManager;
use Psr\Log\LoggerInterface;

class DocumentMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentManager $documentManager,
    ) {}

    public function mapToDto(RelatedDocumentInterface $entity): DocumentResponseDto
    {
        return new DocumentResponseDto(
            id: $entity->getDocument()->getId(),
            relationLinkId: $entity->getId(),
            relationId: $entity->getRelation()?->getId(),
            filename: $entity->getDocument()->getFilename(),
            description: $entity->getDocument()->getDescription(),
            type: $entity->getDocument()->getType(),
            tag: $entity->getDocument()->getTag(),
            path: $entity->getDocument()->getDocumentUrl(),
            url: $this->documentManager->getPublicCdnUrl(
                $entity->getDocument()->getDocumentUrl(),
            ),
            createdAt: $entity->getDocument()->getCreatedAt(),
            updatedAt: $entity->getDocument()->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<RelatedDocumentInterface> $entityList
     * @return DocumentResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than Asset objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof RelatedDocumentInterface) {
                throw new \InvalidArgumentException(
                    'entityList parameter must only contain objects of type '
                    . RelatedDocumentInterface::class,
                );
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }
}
