<?php

namespace App\Controller\ApiV3;

use App\Dto\Offering\OfferingQueryDto;
use App\Dto\Offering\OfferingRequestDto;
use App\Entity\Offering;
use App\Repository\OfferingRepository;
use App\Service\Mapper\OfferingMapper;
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

class OfferingController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private OfferingMapper $offeringMapper,
        private EntityManagerInterface $em,
        private OfferingRepository $offeringRepository,
    ) {}

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/offerings', methods: ['GET'])]
    public function list(
        #[MapQueryString] OfferingQueryDto $dto,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $this->logger->debug('APIv3 list offerings');
        $filters = $normalizer->normalize($dto);
        $this->logger->debug('Filters ', $filters);
        $offerings = $this->offeringRepository->findByWithAssociations($filters, [
            'id' => 'desc',
        ]);
        return $this->json($this->offeringMapper->mapMultipleToDto($offerings));
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/offerings/{id}', methods: ['GET'])]
    public function retrieve(#[MapEntity(id: 'id')] Offering $offering): JsonResponse
    {
        $this->logger->debug('APIv3 get offering');
        return $this->json($this->offeringMapper->mapToDto($offering));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] OfferingRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 create offering');
        $offering = $this->offeringMapper->mapToEntity($dto);
        $this->em->persist($offering);
        $this->em->flush();
        return $this->json(
            data: $this->offeringMapper->mapToDto($offering),
            status: Response::HTTP_CREATED,
        );
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings/{id}', methods: ['PATCH'])]
    public function update(
        #[MapEntity(id: 'id')] Offering $offering,
        #[MapRequestPayload] OfferingRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 update offering');
        $offering = $this->offeringMapper->mapToEntity($dto, $offering);
        $this->em->flush();
        return $this->json($this->offeringMapper->mapToDto($offering));
    }
}
