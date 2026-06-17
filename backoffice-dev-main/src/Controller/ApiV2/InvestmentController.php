<?php

namespace App\Controller\ApiV2;

use App\Dto\DocumentPatchDTO;
use App\Dto\DocumentPostDTO;
use App\Dto\InvestmentDTO;
use App\Dto\InvestmentPostDTO;
use App\Repository\InvestmentRepository;
use App\Service\Manager\InvestmentManagerV2;
use App\Service\Pagination\PaginatedCollection;
use App\Tests\Repository\InvestmentRepositoryTest;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation as Doc;
use OpenApi\Annotations as OA;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class InvestmentController extends AbstractFOSRestController
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Get all the investments - Admin only",
     *     @Doc\Model(type=App\Entity\Investment::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_INVESTMENT:READ")',
        ),
    )]
    #[Route(path: '/investments', methods: ['GET'])]
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
        name: 'status',
        requirements: '\w+',
        description: 'Filter by investment status',
    )]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group',
    )]
    public function getInvestments(
        ParamFetcherInterface $paramFetcher,
        InvestmentManagerV2 $investmentManager,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view'), 'pagination']);

        $pagerfanta = $investmentManager->getInvestments(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('id'),
            $paramFetcher->get('status'),
        );

        $view = View::create()
            ->setData(new PaginatedCollection($pagerfanta))
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Post(
     *      summary="Create a new investment",
     *      description="Create a new investment",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              required={"offeringId", "numberOfShares"},
     *               @OA\Property(property="offeringId", type="integer"),
     *               @OA\Property(property="numberOfShares", type="integer"),
     *               @OA\Property(property="userId", type="integer")
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=201,
     *     description="The new investment object",
     *     @Doc\Model(type=App\Entity\Investment::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    #[Route(path: '/investments', methods: ['POST'])]
    public function postInvestments(
        InvestmentManagerV2 $investmentManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        InvestmentPostDTO $investmentPostDTO,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $investment = $investmentManager->addInvestment($investmentPostDTO);
        $view = View::create()
            ->setData($investment)
            ->setStatusCode(Response::HTTP_CREATED)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns a single investment",
     *     @Doc\Model(type=App\Entity\Investment::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:READ")',
        ),
    )]
    #[Route(path: '/investments/{invId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group',
    )]
    public function getInvestment(
        ParamFetcherInterface $paramFetcher,
        InvestmentManagerV2 $investmentManager,
        int $invId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view')]);

        $investment = $investmentManager->getInvestment($invId);

        if (null === $investment) {
            throw new NotFoundHttpException('Investment with id: '
            . $invId
            . ' does not exist.');
        }

        $view = View::create()->setData($investment)->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Patch(
     *      summary="Update an Investment - Admin only",
     *      description="Update an Investment - Admin only",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *               @OA\Property(property="type", type="string"),
     *               @OA\Property(property="status", type="string"),
     *               @OA\Property(property="currency", type="string"),
     *               @OA\Property(property="transactionId", type="integer"),
     *               @OA\Property(property="pricePerShare", type="number", format="float")
     *          )
     *      )
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="The updated investment object",
     *     @Doc\Model(type=App\Entity\Investment::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    #[Route(path: '/investments/{invId}', methods: ['PATCH'])]
    public function patchInvestment(
        InvestmentManagerV2 $investmentManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        InvestmentDTO $investmentDTO,
        InvestmentRepository $investmentRepository,
        int $invId,
    ): Response {
        // Can only edit own investments
        $investment = $investmentRepository->find($invId);
        if (
            !$this->isGranted('ROLE_ADMIN', $this->getUser())
            && $this->getUser()->getUserIdentifier() != $investment?->getUser()?->getUserIdentifier()
        ) {
            throw new AccessDeniedException(
                'Regular users can only edit their own investments',
            );
        }

        $context = new Context();
        $context->addGroups(['standard']);

        $investment = $investmentManager->updateInvestment($invId, $investmentDTO);
        if (!$investment) {
            throw new NotFoundHttpException('Investment with id: '
            . $invId
            . ' does not exist.');
        }

        $view = View::create()->setData($investment)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_INVESTMENT:READ") and is_granted("ROLE_OAUTH2_PAYOUT:READ")',
        ),
    )]
    #[Route(path: '/investments/{invId}/payouts', methods: ['GET'])]
    public function getInvestmentPayouts(int $invId)
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Get all investment documents related to an investment
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns all the documents related to an investment",
     *     @Doc\Model(type=App\Entity\Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:READ")',
        ),
    )]
    #[Route(path: '/investments/{invId}/documents', methods: ['GET'])]
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
    public function getInvestmentDocuments(
        ParamFetcherInterface $paramFetcher,
        InvestmentManagerV2 $investmentManager,
        int $invId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $documents = $investmentManager->getDocuments($invId);

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

    /**
     * @OA\Post(
     *      summary="Create a new investment document - Admin only",
     *      description="Create a new investment document - Admin only",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=App\Dto\DocumentDTO::class)
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Create a new investment document",
     *     @Doc\Model(type=App\Entity\Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    #[Route(path: '/investments/{invId}/documents', methods: ['POST'])]
    public function postInvestmentDocuments(
        InvestmentManagerV2 $investmentManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        DocumentPostDTO $documentDTO,
        int $invId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $investmentDocument = $investmentManager->addDocument($invId, $documentDTO);
        $view = View::create()
            ->setData($investmentDocument)
            ->setStatusCode(Response::HTTP_CREATED)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Get an investment document",
     *     @Doc\Model(type=App\Entity\Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:READ")',
        ),
    )]
    #[Route(path: '/investments/{invId}/documents/{invDocId}', methods: ['GET'])]
    public function getInvestmentDocument(
        InvestmentManagerV2 $investmentManager,
        int $invId,
        int $invDocId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $document = $investmentManager->getDocument($invId, $invDocId);

        if (!$document) {
            throw new NotFoundHttpException(
                'No document found for investment with id: '
                . $invId
                . ' and document with id: '
                . $invDocId,
            );
        }

        $view = View::create()->setData($document)->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Patch(
     *      summary="Update investment document - Admin only",
     *      description="Update investment document - Admin only",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=App\Dto\DocumentDTO::class)
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=200,
     *     description="The updated document",
     *     @Doc\Model(type=App\Entity\Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    #[Route(path: '/investments/{invId}/documents/{invDocId}', methods: ['PATCH'])]
    public function patchInvestmentDocument(
        InvestmentManagerV2 $investmentManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        DocumentPatchDTO $documentDTO,
        int $invId,
        int $invDocId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $investmentDocument = $investmentManager->updateDocument(
            $invId,
            $invDocId,
            $documentDTO,
        );

        if (!$investmentDocument) {
            throw new NotFoundHttpException(
                'Investment document with id: '
                . $invDocId
                . ' related to investment with id: '
                . $invId
                . ' does not exist.',
            );
        }

        $view = View::create()
            ->setData($investmentDocument)
            ->setStatusCode(Response::HTTP_OK)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Delete(
     *      summary="Delete an investment document - Admin only",
     *      description="Delete an investment document - Admin only"
     * )
     *
     * @OA\Response(
     *     response=204,
     *     description="Confirmation of the deleted document"
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Investments")
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    #[Route(path: '/investments/{invId}/documents/{invDocId}', methods: ['DELETE'])]
    public function deleteInvestmentDocument(
        InvestmentManagerV2 $investmentManager,
        int $invId,
        int $invDocId,
    ): Response {
        $delteted = $investmentManager->deleteDocument($invId, $invDocId);

        if ($delteted) {
            $view = View::create()->setStatusCode(Response::HTTP_NO_CONTENT);

            return $this->handleView($view);
        }

        throw new NotFoundHttpException(
            'Investment document with id: '
            . $invDocId
            . ' related to investment with id: '
            . $invId
            . ' does not exist.',
        );
    }
}
