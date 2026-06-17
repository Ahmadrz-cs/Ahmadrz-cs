<?php

namespace App\Controller\ApiV2;

use App\Service\Manager\PayoutManagerV2;
use App\Service\Pagination\PaginatedCollection;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PayoutController extends AbstractFOSRestController
{
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_PAYOUT:READ")',
        ),
    )]
    #[Route(path: '/payouts', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'page',
        requirements: '\d+',
        default: 1,
        description: 'Page number of the repsonse',
    )]
    #[Rest\QueryParam(
        name: 'limit',
        requirements: '\d+',
        default: 10,
        description: 'Number of items returned in the response',
    )]
    #[Rest\QueryParam(
        name: 'id',
        requirements: '^[0-9]+(,[0-9]+)*$',
        description: 'Filter by ids. Example: 3,7,10,8',
    )]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group',
    )]
    public function getPayouts(
        ParamFetcherInterface $paramFetcher,
        PayoutManagerV2 $payoutManager,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view'), 'pagination']);

        $pagerfanta = $payoutManager->getPayouts(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('id'),
        );

        $view = View::create()
            ->setData(new PaginatedCollection($pagerfanta))
            ->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_PAYOUT:WRITE")',
        ),
    )]
    #[Route(path: '/payouts', methods: ['POST'])]
    public function postPayouts(): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_PAYOUT:READ")',
        ),
    )]
    #[Route(path: '/payouts/{payoutId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group',
    )]
    public function getPayout(
        ParamFetcherInterface $paramFetcher,
        PayoutManagerV2 $payoutManager,
        int $payoutId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view')]);

        $payout = $payoutManager->getPayout($payoutId);

        if (null === $payout) {
            throw new NotFoundHttpException('Payout with id: '
            . $payoutId
            . ' does not exist.');
        }

        $view = View::create()->setData($payout)->setContext($context);

        try {
            return $this->handleView($view);
        } catch (\JMS\Serializer\Exception\ExcludedClassException $e) {
            throw new HttpException(
                500,
                'Payout with id: ' . $payoutId . ' has data integrity violations.',
            );
        }
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_PAYOUT:WRITE")',
        ),
    )]
    #[Route(path: '/payouts/{payoutId}', methods: ['PATCH'])]
    public function patchPayout(int $payoutId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
