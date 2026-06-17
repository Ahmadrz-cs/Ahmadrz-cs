<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class FileSizeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof FileSize) {
            throw new UnexpectedTypeException($constraint, FileSize::class);
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

        //convert the base64 string to the equivent size of the file when decoded (in bytes)
        $size = (int) ((strlen(rtrim($value, '=')) * 3) / 4);

        //if the size is greater than 4194304 bytes (4MB) add the violation
        if ($size > 4194304) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
