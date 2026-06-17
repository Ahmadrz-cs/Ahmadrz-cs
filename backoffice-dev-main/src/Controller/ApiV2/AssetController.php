<?php

namespace App\Controller\ApiV2;

use App\Dto\AssetDTO;
use App\Service\Manager\AssetDocumentManagerV2;
use App\Service\Manager\AssetManagerV2;
use App\Service\Manager\OfferingManagerV2;
use App\Service\Pagination\PaginatedCollection;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TODO complete postAssetDocuments() method
 * TODO update swagger bundle and add swagger annotations
 */
class AssetController extends AbstractFOSRestController
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all the Assets",
     *     @Doc\Model(type=Asset::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Asset")
     *
     */
    #[IsGranted('ROLE_OAUTH2_ASSET:READ')]
    #[Route(path: '/assets', methods: ['GET'])]
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
        description: 'Serilization group. Avaliable groups: minimum, standard',
    )]
    #[Rest\QueryParam(
        name: 'id',
        requirements: '^[0-9]+(,[0-9]+)*$',
        description: "Filter by Asset id's. Example: 3,7,10,8",
    )]
    #[Rest\QueryParam(
        name: 'type',
        requirements: '\w+',
        description: 'Filter by Asset type. Example: Commercial',
    )]
    public function getAssets(
        ParamFetcherInterface $paramFetcher,
        AssetManagerV2 $assetManager,
    ): Response {
        $context = new Context();
        if ($this->isGranted('ROLE_ADMIN') and $paramFetcher->get('view') == 'admin') {
            $context->addGroups(['admin', 'pagination']);
        } else {
            switch ($paramFetcher->get('view')) {
                case 'admin':
                    $context->addGroups(['standard', 'pagination']);
                    break;
                case 'standard':
                    $context->addGroups(['standard', 'pagination']);
                    break;
                case 'minimum':
                    $context->addGroups(['minimum', 'pagination']);
                    break;
            }
        }

        $pagerfanta = $assetManager->getAssets(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('id'),
            $paramFetcher->get('type'),
        );

        $view = View::create()
            ->setData(new PaginatedCollection($pagerfanta))
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Create a new Asset",
     *     @Doc\Model(type=Asset::class, groups={"admin"})
     * )
     * @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=AssetDTO::class)
     *          )
     *      )
     * @OA\Tag(name="V2 Asset Post")
     * @Doc\Security(name="PasswordOAuth2")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_ASSET:WRITE")',
        ),
    )]
    #[Route(path: '/assets', methods: ['POST'])]
    public function postAssets(
        AssetManagerV2 $assetManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])] AssetDTO $assetDTO,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $asset = $assetManager->addAsset($assetDTO);
        $view = View::create()
            ->setData($asset)
            ->setStatusCode(Response::HTTP_CREATED)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns the Asset from a given id",
     *     @Doc\Model(type=Asset::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Asset")
     *
     */
    #[IsGranted('ROLE_OAUTH2_ASSET:READ')]
    #[Route(path: '/assets/{assetId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group.',
    )]
    public function getAsset(
        ParamFetcherInterface $paramFetcher,
        AssetManagerV2 $assetManager,
        int $assetId,
    ): Response {
        $context = new Context();
        if ($this->isGranted('ROLE_ADMIN') and $paramFetcher->get('view') == 'admin') {
            $context->addGroup('admin');
        } else {
            switch ($paramFetcher->get('view')) {
                case 'admin':
                    $context->addGroup('standard');
                    break;
                case 'standard':
                    $context->addGroup('standard');
                    break;
                case 'minimum':
                    $context->addGroup('minimum');
                    break;
            }
        }

        $asset = $assetManager->getAsset($assetId);

        if (null === $asset) {
            throw new NotFoundHttpException('Asset with id: '
            . $assetId
            . ' does not exist.');
        }

        $view = View::create()->setData($asset)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_ASSET:WRITE")',
        ),
    )]
    #[Route(path: '/assets/{assetId}', methods: ['PATCH'])]
    public function patchAssets(
        AssetManagerV2 $assetManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])] AssetDTO $assetDTO,
        int $assetId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $asset = $assetManager->updateAsset($assetId, $assetDTO);
        if (null === $asset) {
            throw new NotFoundHttpException('Asset with id: '
            . $assetId
            . ' does not exist.');
        }

        $view = View::create()->setData($asset)->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all the Offerings which belong to the given Asset ID",
     *     @Doc\Model(type=Offering::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Asset")
     *
     */
    #[IsGranted('ROLE_OAUTH2_OFFERING:READ')]
    #[Route(path: '/assets/{assetId}/offerings', methods: ['GET'])]
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
    public function getAssetOfferings(
        ParamFetcherInterface $paramFetcher,
        OfferingManagerV2 $offeringManager,
        int $assetId,
    ): Response {
        $context = new Context();
        if ($this->isGranted('ROLE_ADMIN') and $paramFetcher->get('view') == 'admin') {
            $context->addGroup('admin');
        } else {
            switch ($paramFetcher->get('view')) {
                case 'admin':
                    $context->addGroup('standard');
                    break;
                case 'standard':
                    $context->addGroup('standard');
                    break;
                case 'minimum':
                    $context->addGroup('minimum');
                    break;
            }
        }

        $offerings = $offeringManager->getOfferingByAssetId($assetId);

        if (null === $offerings || empty($offerings)) {
            $view = View::create()->setData(['data' => []]);
        } else {
            $pagerfanta = new \Pagerfanta\Pagerfanta(
                new \Pagerfanta\Adapter\ArrayAdapter($offerings),
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
     * @OA\Response(
     *     response=200,
     *     description="Returns all the Documents which belong to the given Asset ID",
     *     @Doc\Model(type=AssetDocuments::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Asset")
     *
     */
    #[IsGranted('ROLE_OAUTH2_ASSET:READ')]
    #[Route(path: '/assets/{assetId}/documents', methods: ['GET'])]
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
        description: 'Number of items returned in the response.',
    )]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group.',
    )]
    public function getAssetDocuments(
        ParamFetcherInterface $paramFetcher,
        AssetDocumentManagerV2 $assetDocumentManager,
        int $assetId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view'), 'pagination']);

        $pagerfanta = $assetDocumentManager->getDocumentsByAssetId(
            $assetId,
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
        );

        $view = View::create()
            ->setData(new PaginatedCollection($pagerfanta))
            ->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_ASSET:WRITE")',
        ),
    )]
    #[Route(path: '/assets/{assetId}/documents', methods: ['POST'])]
    public function postAssetDocuments(int $assetId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns the Document which belong to the given Asset ID and Document ID",
     *     @Doc\Model(type=Document::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 Asset")
     *
     */
    #[IsGranted('ROLE_OAUTH2_ASSET:READ')]
    #[Route(path: '/assets/{assetId}/documents/{assetDocId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(minimum|standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group.',
    )]
    public function getAssetDocument(
        ParamFetcherInterface $paramFetcher,
        AssetDocumentManagerV2 $assetDocumentManager,
        int $assetId,
        int $assetDocId,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view')]);

        $documents = $assetDocumentManager->getDocumentByAssetIdAndDocumentId(
            $assetId,
            $assetDocId,
        );

        if (null === $documents || empty($documents)) {
            throw new NotFoundHttpException(sprintf(
                'No document was found for asset with id: '
                . $assetId
                . ' and a document id: '
                . $assetDocId,
            ));
        }

        $view = View::create()->setData($documents)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_ASSET:WRITE")',
        ),
    )]
    #[Route(path: '/assets/{assetId}/documents/{assetDocId}', methods: ['PATCH'])]
    public function patchAssetDocument(int $assetId, int $assetDocId): Response
    {
        //TODO
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_ASSET:WRITE")',
        ),
    )]
    #[Route(path: '/assets/{assetId}/documents/{assetDocId}', methods: ['DELETE'])]
    public function deleteAssetDocument(
        AssetDocumentManagerV2 $assetDocumentManager,
        int $assetId,
        int $assetDocId,
    ): Response {
        $assetDocumentManager->deleteAssetDocument($assetId, $assetDocId);

        $view = View::create();

        return $this->handleView($view);
    }
}
