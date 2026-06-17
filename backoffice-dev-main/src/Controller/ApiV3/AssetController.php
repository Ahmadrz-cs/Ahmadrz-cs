<?php

namespace App\Controller\ApiV3;

use App\Dto\Asset\AssetQueryDto;
use App\Dto\Asset\AssetRequestDto;
use App\Entity\Asset;
use App\Repository\AssetRepository;
use App\Service\Mapper\AssetMapper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AssetController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetMapper $assetMapper,
        private EntityManagerInterface $em,
        private AssetRepository $assetRepository,
    ) {}

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/assets', methods: ['GET'])]
    public function list(
        #[MapQueryString] AssetQueryDto $dto,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $this->logger->debug('APIv3 list assets');
        $filters = $normalizer->normalize($dto);
        // $this->logger->debug("Filters ", $filters);
        $assets = $this->assetRepository->findByWithAssociations($filters, [
            'id' => 'desc',
        ]);
        return $this->json($this->assetMapper->mapMultipleToDto($assets));
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/assets/{id}', methods: ['GET'])]
    public function retrieve(#[MapEntity(id: 'id')] Asset $asset): JsonResponse
    {
        $this->logger->debug('APIv3 get asset');
        return $this->json($this->assetMapper->mapToDto($asset));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/assets', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] AssetRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 create asset');
        $asset = $this->assetMapper->mapToEntity($dto);
        $this->em->persist($asset);
        $this->em->flush();
        return $this->json(
            data: $this->assetMapper->mapToDto($asset),
            status: Response::HTTP_CREATED,
        );
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/assets/{id}', methods: ['PATCH'])]
    public function update(
        #[MapEntity(id: 'id')] Asset $asset,
        #[MapRequestPayload] AssetRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 update asset');
        $asset = $this->assetMapper->mapToEntity($dto, $asset);
        $this->em->flush();
        return $this->json($this->assetMapper->mapToDto($asset));
    }
}
