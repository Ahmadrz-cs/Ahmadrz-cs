<?php

namespace AppBundle\Entity;

class QuestionChoice implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
        public readonly bool $correct,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return $this->id;
    }
}
