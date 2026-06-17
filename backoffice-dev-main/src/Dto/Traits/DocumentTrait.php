<?php

namespace App\Dto\Traits;

use App\Validator as CommonAssert;
use JMS\Serializer\Annotation as JMS;

trait DocumentTrait
{
    #[JMS\Type('string')]
    #[CommonAssert\FileExtension]
    protected ?string $fileName = null;

    #[JMS\Type('string')]
    #[CommonAssert\FileSize]
    #[CommonAssert\MimeType]
    protected ?string $documentContent = null;

    #[JMS\Type('string')]
    protected ?string $tag = null;

    public function __construct(
        ?string $fileName,
        ?string $documentContent,
        ?string $tag,
    ) {
        $this->fileName = $fileName;
        $this->documentContent = $documentContent;
        $this->tag = $tag;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getDocumentContent()
    {
        return $this->documentContent;
    }

    public function getTag()
    {
        return $this->tag;
    }
}
