<?php

namespace App\Controller\ApiV2;

use App\Dto\OfferingDTO;
use App\Dto\OfferingPatchDTO;
use App\Dto\OfferingPostDTO;
use App\Service\Manager\OfferingDocumentManagerV2;
use App\Service\Manager\OfferingManagerV2;
use App\Service\Pagination\PaginatedCollection;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation as Doc;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OfferingController extends AbstractFOSRestController
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all the Offerings",
     *     @Doc\Model(type=Offering::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Offering")
     *
     */
    #[IsGranted('ROLE_OAUTH2_OFFERING:READ')]
    #[Route(path: '/offerings', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'page',
        requirements: '\d+',
        default: 1,
        description: 'Page number of the repsonse.',
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
        description: "Filter by Offerings id's. Example: 3,7,10,8",
    )]
    #[Rest\QueryParam(
        name: 'status',
        requirements: '\w+',
        description: 'Filter by offering status.',
    )]
    #[Rest\QueryParam(
        name: 'isFeatured',
        requirements: '(true|false)',
        description: 'Filter by featured offerings.',
    )]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group. Avaliable groups: minimum, standard',
    )]
    public function getOfferings(
        ParamFetcherInterface $paramFetcher,
        OfferingManagerV2 $offeringManager,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view'), 'pagination']);

        $pagerfanta = $offeringManager->getOfferings(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('id'),
            $paramFetcher->get('status'),
            $paramFetcher->get('isFeatured'),
        );

        $view = View::create()
            ->setData(new PaginatedCollection($pagerfanta))
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Create a new offering",
     *     @Doc\Model(type=Offering::class, groups={"standard"})
     * )
     * @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=OfferingPostDTO::class)
     *          )
     *      )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Offering Post")
     *
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings', methods: ['POST'])]
    public function postOfferings(
        OfferingManagerV2 $offeringManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        OfferingPostDTO $offeringDTO,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $offering = $offeringManager->addOffering($offeringDTO);
        $view = View::create()
            ->setData($offering)
            ->setStatusCode(Response::HTTP_CREATED)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns the Offering from a given id",
     *     @Doc\Model(type=Offering::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Offering")
     *
     */
    #[IsGranted('ROLE_OAUTH2_OFFERING:READ')]
    #[Route(path: '/offerings/{offId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group.',
    )]
    public function getOffering(
        ParamFetcherInterface $paramFetcher,
        OfferingManagerV2 $offeringManager,
        int $offId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view')]);

        $offering = $offeringManager->getOffering($offId);

        if (null === $offering) {
            throw new NotFoundHttpException('Offering with id: '
            . $offId
            . ' does not exist.');
        }

        $view = View::create()->setData($offering)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings/{offId}', methods: ['PATCH'])]
    public function patchOffering(
        OfferingManagerV2 $offeringManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        OfferingPatchDTO $offeringDTO,
        int $offId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $offering = $offeringManager->updateOffering($offId, $offeringDTO);
        if (null === $offering) {
            throw new NotFoundHttpException('Offering with id: '
            . $offId
            . ' does not exist.');
        }

        $view = View::create()->setData($offering)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings/{offId}/investments', methods: ['GET'])]
    public function getOfferingInvestments(int $offId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all the documents related to an offering",
     *     @Doc\Model(type=Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Offering")
     *
     */
    #[IsGranted('ROLE_OAUTH2_OFFERING:READ')]
    #[Route(path: '/offerings/{offId}/documents', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'page',
        requirements: '\d+',
        default: 1,
        description: 'Page number of the repsonse.',
    )]
    #[Rest\QueryParam(
        name: 'limit',
        requirements: '\d+',
        default: 10,
        description: 'Number of items returned in the response',
    )]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group.',
    )]
    public function getOfferingsDocuments(
        ParamFetcherInterface $paramFetcher,
        OfferingDocumentManagerV2 $offeringDocumentManagerV2,
        int $offId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view')]);

        $documents = $offeringDocumentManagerV2->getDocumentsByOfferingId($offId);

        if (null === $documents || empty($documents)) {
            $view = View::create()->setData(['data' => []]);
        } else {
            $pagerfanta = new \Pagerfanta\Pagerfanta(
                new \Pagerfanta\Adapter\ArrayAdapter($documents),
            );
            $pagerfanta->setCurrentPage($paramFetcher->get('page') ?? 1);
            $pagerfanta->setMaxPerPage($paramFetcher->get('limit') ?? 15);
            $context->addGroup('pagination');
            $view = View::create()
                ->setData(new PaginatedCollection($pagerfanta))
                ->setContext($context);
        }

        return $this->handleView($view);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings/{offId}/documents', methods: ['POST'])]
    public function postOfferingsDocuments(int $offId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns a single document related to an offering",
     *     @Doc\Model(type=Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Offering")
     *
     */
    #[IsGranted('ROLE_OAUTH2_OFFERING:READ')]
    #[Route(path: '/offerings/{offId}/documents/{offDocId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group.',
    )]
    public function getOfferingsDocument(
        ParamFetcherInterface $paramFetcher,
        OfferingDocumentManagerV2 $offeringDocumentManagerV2,
        int $offId,
        int $offDocId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view')]);

        $document = $offeringDocumentManagerV2->getDocumentByOfferingIdAndDocumentId(
            $offId,
            $offDocId,
        );

        if (null === $document || empty($document)) {
            throw new NotFoundHttpException(
                'No document found for offereing with id: '
                . $offId
                . ' and document id: '
                . $offDocId,
            );
        }

        $view = View::create()->setData($document)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings/{offId}/documents/{offDocId}', methods: ['PATCH'])]
    public function patchOfferingsDocument(int $offId, int $offDocId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/offerings/{offId}/documents/{offDocId}', methods: ['DELETE'])]
    public function deleteOfferingsDocument(int $offId, int $offDocId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
