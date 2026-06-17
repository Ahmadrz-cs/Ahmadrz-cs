<?php

namespace App\Form\DataTransformer;

use App\Entity\Asset;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class AssetToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a asset object into a string representing the asset id
     * @param  Asset|null $asset
     */
    public function transform($asset): ?string
    {
        if (null === $asset) {
            return null;
        }

        return (string) $asset->getId();
    }

    /**
     * @param  string $assetId
     * @return Asset|null
     * @throws TransformationFailedException if object (asset) is not found.
     */
    public function reverseTransform($assetId): ?Asset
    {
        if (!$assetId) {
            return null;
        }

        $asset = $this->entityManager->getRepository(Asset::class)->find($assetId);

        if (null === $asset) {
            throw new TransformationFailedException(sprintf(
                'An asset with id "%s" does not exist!',
                $assetId,
            ));
        }
        return $asset;
    }
}
