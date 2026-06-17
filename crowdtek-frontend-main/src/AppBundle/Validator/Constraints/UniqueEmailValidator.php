<?php

namespace AppBundle\Validator\Constraints;

use ClientBundle\Service\PublicService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private PublicService $publicService,
        private string $network,
    ) {
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }
        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $network = $this->network;
        //$response = $this->injectedService->checkEmailAddress($network, $value);

        $response = $this->publicService->checkEmailAddress($network, $value);

        if (isset($response['outcome'])) {
            if ($response['outcome'] === 'fail') {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ email }}', $value)
                    ->addViolation();
            }
        }
    }
}
