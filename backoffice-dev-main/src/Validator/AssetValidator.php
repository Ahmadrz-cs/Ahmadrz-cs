<?php

namespace App\Validator;

use App\Service\Manager\AssetManagerV2;
use App\Validator\Asset;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AssetValidator extends ConstraintValidator
{
    public function __construct(
        private AssetManagerV2 $assetManager,
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Asset) {
            throw new UnexpectedTypeException($constraint, Asset::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_int($value)) {
            throw new UnexpectedValueException($value, 'int');
        }

        // attempt to find asset
        try {
            $asset = $this->assetManager->getAsset($value);
        } catch (\Exception $e) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ assetId }}', $value)
                ->addViolation();
        }

        if (!$asset) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ assetId }}', $value)
                ->addViolation();
        }

        // Check if asset is published
        if ($asset) {
            $status = $asset->getLifecycleStatus();
            if ($status != 'published') {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('{{ assetId }}', $value)
                    ->addViolation();
            }
        }
    }
}
