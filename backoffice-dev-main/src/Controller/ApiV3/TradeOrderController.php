<?php

namespace App\Controller\ApiV3;

use App\Dto\TradeOrder\TradeOrderQueryDto;
use App\Dto\TradeOrder\TradeOrderRequestDto;
use App\Dto\TradeOrder\TradeOrderStatusLogRequestDto;
use App\Entity\TradeOrder;
use App\Repository\TradeOrderRepository;
use App\Service\Mapper\TradeOrderMapper;
use App\Service\Mapper\TradeOrderStatusLogMapper;
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

class TradeOrderController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private TradeOrderMapper $tradeOrderMapper,
        private TradeOrderStatusLogMapper $tradeOrderStatusLogMapper,
        private EntityManagerInterface $em,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/trade-orders', methods: ['GET'])]
    public function list(
        #[MapQueryString] TradeOrderQueryDto $dto,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $this->logger->debug('APIv3 list trade orders');
        $filters = $normalizer->normalize($dto);
        // $this->logger->debug("Filters ", $filters);
        $tradeOrders = $this->tradeOrderRepository->findByWithAssociations(
            $filters,
            ['id' => 'desc'],
            $filters['perPage'],
            $filters['page'],
        );
        return $this->json($this->tradeOrderMapper->mapMultipleToDto($tradeOrders));
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/trade-orders/{id}', methods: ['GET'])]
    public function retrieve(
        #[MapEntity(id: 'id')] TradeOrder $tradeOrder,
    ): JsonResponse {
        $this->logger->debug('APIv3 get trade order');
        return $this->json($this->tradeOrderMapper->mapToDto($tradeOrder));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/trade-orders', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationGroups: ['create'])] TradeOrderRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 create trade order');
        $tradeOrder = $this->tradeOrderMapper->mapToEntity($dto);
        $this->em->persist($tradeOrder);
        $this->em->flush();
        return $this->json(
            data: $this->tradeOrderMapper->mapToDto($tradeOrder),
            status: Response::HTTP_CREATED,
        );
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/trade-orders/{id}', methods: ['PATCH'])]
    public function update(
        #[MapEntity(id: 'id')] TradeOrder $tradeOrder,
        #[MapRequestPayload] TradeOrderRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv3 update trade order');
        $tradeOrder = $this->tradeOrderMapper->mapToEntity($dto, $tradeOrder);
        $this->em->flush();
        return $this->json($this->tradeOrderMapper->mapToDto($tradeOrder));
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/trade-orders/{id}/status-logs', methods: ['GET'])]
    public function listStatusLogs(
        #[MapEntity(id: 'id')] TradeOrder $tradeOrder,
    ): JsonResponse {
        $this->logger->debug(
            "APIv3 list status logs for trade order {$tradeOrder->getId()}",
        );
        return $this->json($this->tradeOrderStatusLogMapper->mapMultipleToDto($tradeOrder->getStatusLogs()));
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/trade-orders/{id}/status-logs', methods: ['POST'])]
    public function createStatusLog(
        #[MapEntity(id: 'id')] TradeOrder $tradeOrder,
        #[MapRequestPayload] TradeOrderStatusLogRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug(
            "APIv3 create status log for trade order {$tradeOrder->getId()}",
        );
        $tradeOrderStatusLog = $this->tradeOrderStatusLogMapper->mapToEntity($dto);
        $tradeOrder->addStatusLog($tradeOrderStatusLog);
        $this->em->flush();
        return $this->json($this->tradeOrderStatusLogMapper->mapToDto(
            $tradeOrderStatusLog,
        ));
    }
}
