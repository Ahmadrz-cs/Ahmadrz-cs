<?php

namespace App\Form\DataTransformer;

use \BcMath\Number;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BcNumberToStringTransformer implements DataTransformerInterface
{
    /**
     * Convert a Number object into a string representing the Number id
     * @param  Number|null $number
     */
    public function transform($number): ?string
    {
        if (null === $number) {
            return null;
        }

        return (string) $number;
    }

    /**
     * @param  string $number
     * @return Number|null
     * @throws TransformationFailedException if object (Number) is not found.
     */
    public function reverseTransform($number): ?Number
    {
        if (null === $number) {
            return null;
        }

        if (!\is_numeric($number)) {
            throw new TransformationFailedException(sprintf(
                'The number value given is not numeric. Type: "%s"',
                \gettype($number),
            ));
        }

        return new Number($number);
    }
}
