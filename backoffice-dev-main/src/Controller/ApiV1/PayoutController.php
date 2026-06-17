<?php

namespace App\Controller\ApiV1;

use App\Entity\Payout;
use App\Repository\PayoutRepository;
use App\Service\Manager\PayoutManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PayoutController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private PayoutManager $payoutManager,
        private PayoutRepository $payoutRepository,
    ) {}

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\QueryParam(
        name: 'sort',
        requirements: '^([+-]?[a-zA-Z]+,?)*$',
        nullable: true,
    )]
    #[Rest\QueryParam(name: 'id', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\QueryParam(name: 'type', requirements: '^\w+(,\w+)*$', nullable: true)]
    #[Rest\View]
    #[Get('/%api_network_path%/payouts', name: 'api_get_payouts')]
    public function getPayouts(ParamFetcherInterface $paramFetcher)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Resource not found!');
        }

        $queryParams = $paramFetcher->all(true);
        $this->logger->info('GET /payouts with params ' . json_encode($queryParams));

        $totalCount = $this->payoutRepository->count([]);
        $resultValues = $this->payoutManager->findByQuery(
            $queryParams,
            $this->isGranted('ROLE_ADMIN'),
        );

        if (!empty($resultValues)) {
            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'offset' => $queryParams['offset'],
                    'limit' => $queryParams['limit'],
                    'count' => $totalCount,
                    'list' => $resultValues,
                ],
                'status' => 200,
            ]);
        } else {
            throw $this->createNotFoundException('Resource not found!');
        }
    }
}
