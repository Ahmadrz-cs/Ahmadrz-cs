<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class JsonToTextTransformer implements DataTransformerInterface
{
    /**
     * Convert a user object into a string representing the user id
     * @param  string|null $value
     */
    public function transform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (empty($value)) {
            return json_encode([]);
        }

        return json_encode($value);
    }

    /**
     * @param  string $value
     * @return array|null
     */
    public function reverseTransform($value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (empty($value)) {
            return [];
        }

        return json_decode($value, true);
    }
}
