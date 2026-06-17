<?php

namespace App\Controller\ApiV3;

use App\Dto\Investment\InvestmentQueryDto;
use App\Dto\Investment\InvestmentRequestDto;
use App\Entity\Investment;
use App\Repository\InvestmentRepository;
use App\Service\Mapper\InvestmentMapper;
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

class InvestmentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private InvestmentMapper $investmentMapper,
        private EntityManagerInterface $em,
        private InvestmentRepository $investmentRepository,
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/investments', methods: ['GET'])]
    public function list(
        #[MapQueryString] InvestmentQueryDto $dto,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $this->logger->debug('APIv3 list investments');
        $filters = $normalizer->normalize($dto);
        // $this->logger->debug("Filters ", $filters);
        $investments = $this->investmentRepository->findByWithAssociations($filters, [
            'id' => 'desc',
        ]);
        return $this->json($this->investmentMapper->mapMultipleToDto($investments));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/investments/{id}', methods: ['GET'])]
    public function retrieve(
        #[MapEntity(id: 'id')] Investment $investment,
    ): JsonResponse {
        $this->logger->debug('APIv3 get investment');
        return $this->json($this->investmentMapper->mapToDto($investment));
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/investments', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] InvestmentRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 create investment');
        $investment = $this->investmentMapper->mapToEntity($dto);
        $this->em->persist($investment);
        $this->em->flush();
        return $this->json(
            data: $this->investmentMapper->mapToDto($investment),
            status: Response::HTTP_CREATED,
        );
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/investments/{id}', methods: ['PATCH'])]
    public function update(
        #[MapEntity(id: 'id')] Investment $investment,
        #[MapRequestPayload] InvestmentRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 update investment');
        $investment = $this->investmentMapper->mapToEntity($dto, $investment);
        $this->em->flush();
        return $this->json($this->investmentMapper->mapToDto($investment));
    }
}
