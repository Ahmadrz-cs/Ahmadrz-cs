<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class FileExtensionValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof FileExtension) {
            throw new UnexpectedTypeException($constraint, FileExtension::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        if (!preg_match(
            '/^.*\.(jpeg|JPEG|jpg|JPG|gif|GIF|pdf|PDF|png|PNG)$/',
            $value,
            $matches,
        )) {
            // the argument must be a string or an object implementing __toString()
            $this->context
                ->buildViolation($constraint->message)
                //->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
