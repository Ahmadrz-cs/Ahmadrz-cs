<?php

namespace AppBundle\Entity;

class Question implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $content,
        /**
         * @var QuestionChoice[]
         */
        public readonly array $choices,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return $this->id;
    }
}
