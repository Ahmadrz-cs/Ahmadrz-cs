<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

final class DocumentPatchDTO extends DocumentDTO
{
    #[JMS\Type('string')]
    private ?string $description = null;

    public function __construct(
        ?string $fileName,
        ?string $documentContent,
        ?string $tag,
        ?string $description,
    ) {
        parent::__construct($fileName, $documentContent, $tag);
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
