<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class MimeTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MimeType) {
            throw new UnexpectedTypeException($constraint, MimeType::class);
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

        $data = base64_decode($value);
        $finfo = finfo_open();
        $mimeType = finfo_buffer($finfo, $data, FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        if (!$this->isMimeTypeAllowed($mimeType)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    public function isMimeTypeAllowed($mimeType)
    {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];

        if (in_array($mimeType, $allowedTypes)) {
            return true;
        }
        return false;
    }
}
