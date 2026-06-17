<?php

namespace App\Entity\Interface;

use App\Entity\Document;

interface RelatedDocumentInterface
{
    public function getId(): int|string|null;

    public function getRelation();

    public function getDocument(): ?Document;
}
