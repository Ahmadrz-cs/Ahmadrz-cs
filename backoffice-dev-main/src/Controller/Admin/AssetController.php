<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\AssetStatusLog;
use App\Entity\BaseEntity;
use App\Entity\User;
use App\Form\AssetStatusLogType;
use App\Form\Type\AssetType;
use App\Form\Type\FieldPickerType;
use App\Form\Type\QueryAssetType;
use App\Repository\AssetRepository;
use App\Repository\UserRepository;
use App\Service\Manager\AssetManager;
use App\Service\Manager\AssetManagerV2;
use App\Service\MangopayWalletService;
use App\Service\Util\ExportHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/asset')]
class AssetController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MangopayWalletService $walletService,
        private UserRepository $userRepository,
        private AssetRepository $assetRepository,
        private AssetManagerV2 $assetManager,
        private AssetManager $assetManagerLegacy,
        private Exporter $exporter,
    ) {}

    #[Route(path: '', name: 'admin_asset_index')]
    #[Route(path: '/list', name: 'admin_asset_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List assets');
        $defaultFilters = [
            // 'currentStatus' => AssetStatus::typicalCases()
        ];
        $form = $this->createForm(QueryAssetType::class, $defaultFilters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->assetRepository->findByWithAssociations(
            $filters ?? $defaultFilters,
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/assets/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/list-custom', name: 'admin_asset_list_custom')]
    public function listCustom(Request $request): Response
    {
        $this->logger->info('List assets with customisable table');
        $filters = [
            'page' => 1,
            'perPage' => '10',
            'orderBy' => 'id',
            'orderDirection' => 'DESC',
        ];
        $form = $this->createForm(QueryAssetType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData(); // default filters fallback if not valid
        }
        $defaultFields = [
            'chosenFields' => [
                'id',
                'name',
                'companyNumber',
                'amountOfShares',
                'pricePerShare',
                'createdAt',
            ],
        ];
        $excludedFields = [
            'gross_yield',
            'additional_wallet',
            'orgEmail',
            'pointsOfInterest',
            'telephone',
            'legalName',
            'taxId',
            'detailedDesc',
            'briefDescription',
        ];
        $availableFields = array_unique(array_merge(
            $defaultFields['chosenFields'],
            $this->doctrine
                ->getManager()
                ->getClassMetadata(Asset::class)
                ->getFieldNames(),
        ));
        $pickerOptions = [
            'choices' => array_diff($availableFields, $excludedFields),
        ];
        // sort($pickerOptions['choices']);
        $formFieldPicker = $this->createForm(
            FieldPickerType::class,
            $defaultFields,
            $pickerOptions,
        );
        $results = $this->assetRepository->findByWithAssociations(
            $filters,
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/assets/list_custom.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
            'formFieldPicker' => $formFieldPicker->createView(),
            'defaultColumns' => $defaultFields['chosenFields'],
        ]);
    }

    #[Route(path: '/export', name: 'admin_asset_list_export')]
    public function export(Request $request): StreamedResponse
    {
        $this->logger->info('Export assets list');
        $filters = [];
        $form = $this->createForm(QueryAssetType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData(); // default filters fallback if not valid
        }
        $query = $this->assetRepository->buildQueryWithAssociations($filters, [
            $filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC',
        ]
            // $filters['perPage'] ?? 10,
            // $filters['page'] ?? 1
        );
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        return $this->exporter->getResponse(
            $format,
            ExportHelper::generateFileName('assets', $format),
            new DoctrineORMQuerySourceIterator(
                $query,
                $this->doctrine
                    ->getManager()
                    ->getClassMetadata(Asset::class)
                    ->getFieldNames(),
            ),
        );
    }

    #[Route(path: '/add', name: 'admin_asset_add')]
    #[Route(path: '/{id}/edit', name: 'admin_asset_edit')]
    public function editAction(Request $request, ?Asset $asset = null): Response
    {
        $readOnly = false;

        if (!empty($asset)) {
            if (!$this->permissionCheck('CAN_UPDATE_ASSET')) {
                $readOnly = true;
            }
            $action = 'edit';
        } else {
            $this->denyAccessUnlessGranted('CAN_CREATE_ASSET', $this->getUser());
            $action = 'add';
            $asset = new Asset();

            /** @var User $user */
            $user = $this->getUser();
            $asset->setCreatedById($user->getId());

            //Setting Mandatory fields

            $asset->setVisibility(BaseEntity::VISIBILITY_AUTO);
        }

        $form = $this->createForm(AssetType::class, $asset, ['read_only' => $readOnly]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('addFields')->getData() as $customField) {
                if (!$customField->getAsset()) {
                    $customField->setAsset($form->getData());
                }
            }

            foreach ($form->get('addresses')->getData() as $customField) {
                if (!$customField->getAsset()) {
                    $customField->setAsset($form->getData());
                }
            }

            foreach ($form->get('members')->getData() as $customField) {
                if (!$customField->getAsset()) {
                    $customField->setAsset($form->getData());
                }
            }

            foreach ($form->get('fees')->getData() as $customField) {
                if (!$customField->getAsset()) {
                    $customField->setAsset($form->getData());
                }
            }

            if ($action == 'add') {
                $em = $this->doctrine->getManager();
                $em->persist($asset);
                $em->flush();
                $this->assetManagerLegacy->newAssetCreatedMailSend($asset);
                return $this->redirect($this->generateUrl('admin_asset_index', ['id' => $asset->getId()]));
            } elseif ($action == 'edit') {
                $em = $this->doctrine->getManager();
                $em->persist($asset);
                $em->flush();

                return $this->redirect($this->generateUrl('admin_asset_index', ['id' => $asset->getId()]));
            }
        }
        return $this->render('admin/pages/assets/edit.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/view', name: 'admin_asset_view')]
    public function viewAction(Asset $asset): Response
    {
        return $this->render('admin/pages/assets/view.html.twig', [
            'asset' => $asset,
        ]);
    }

    #[Route(path: '/wallets', name: 'admin_asset_wallet_list')]
    public function walletsList(Request $request): Response
    {
        $this->logger->info('Viewing asset wallets');
        $filters = [
            'page' => 1,
            'perPage' => '10',
            'orderBy' => 'id',
            'orderDirection' => 'DESC',
        ];
        $form = $this->createForm(QueryAssetType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->assetRepository->findByWithAssociations(
            $filters,
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/assets/wallets.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        path: '/{asset}/wallets/create-all',
        name: 'admin_asset_wallets_create_all',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createAllWallets(Request $request, Asset $asset): Response
    {
        $this->logger->info('Create asset wallets');
        $onlyMinimum = $request->query->get('onlyMinimum', false);
        try {
            $walletsToCreate = $this->assetManager->createAllWallets(
                $asset,
                $onlyMinimum,
            );
            $this->addFlash(
                'success',
                json_encode($walletsToCreate)
                    . ' wallets successfully created if missing.',
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Could not create all wallets for asset id: '
                    . $asset->getId()
                    . ' Error details: '
                    . $e->GetMessage(),
            );
            $this->addFlash('error', 'Not all wallets could be created.');
        }
        if (
            $request->query->get('redirectRoute')
            && $request->query->get('redirectId')
            && in_array(
                $request->query->get('redirectRoute'),
                [
                    'admin_product_edit_wallets',
                    'admin_product_dashboard',
                    'admin_product_edit_documents',
                ],
            )
        ) {
            $parameters = ['id' => $request->query->get('redirectId')];
            if ($request->query->get('setup')) {
                $parameters['setup'] = 1;
            }
            return $this->redirectToRoute(
                $request->query->get('redirectRoute'),
                $parameters,
            );
        }

        return $this->redirectToRoute('admin_asset_wallets_manage', [
            'asset' => $asset->getId(),
        ]);
    }

    #[Route(
        path: '/{asset}/wallets/create/{type}',
        name: 'admin_asset_wallets_create',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createWallet(Asset $asset, string $type): Response
    {
        $this->logger->info('Create asset wallet');

        $propertyAccessor =
            PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();

        if (in_array($type, AssetManagerV2::SUPPORTED_WALLETS)) {
            $walletId = $propertyAccessor->getValue($asset, $type . 'WalletId');
            if (is_null($walletId)) {
                try {
                    $this->assetManager->createWallet($asset, $type);
                } catch (\Exception $e) {
                    $this->logger->error(
                        'The '
                            . $type
                            . ' wallet for asset id: '
                            . $asset->getId()
                            . '. Could not be created.'
                            . ' Error details: '
                            . $e->GetMessage(),
                    );
                    $this->addFlash('error', $type . ' wallet could not be created.');
                }

                $walletId = $propertyAccessor->getValue($asset, $type . 'WalletId');

                if ($walletId) {
                    $type = ucwords($type);
                    $this->addFlash(
                        'success',
                        $type . ' wallet has been successfully created.',
                    );
                }
            }
        }

        return $this->redirectToRoute('admin_asset_wallets_manage', [
            'asset' => $asset->getId(),
        ]);
    }

    #[Route(
        path: '/{asset}/manage-wallets',
        name: 'admin_asset_wallets_manage',
        methods: ['GET'],
    )]
    public function manageWallets(
        Asset $asset,
        #[MapQueryParameter] bool $loadWallets = false,
    ): Response {
        $this->logger->info('Manage asset wallets');

        return $this->render('admin/pages/assets/manage_wallets.html.twig', [
            'asset' => $asset,
            'wallets' => $this->assetManager->getAssetWallets($asset, $loadWallets),
        ]);
    }

    // #[Route(path: '/{id}/registerwithmangopay', name: 'admin_asset_registerwithmangopay')]
    // public function registerWithMangoPay(Asset $assetId): Response
    // {
    //     if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
    //         try {
    //             $this->assetManagerLegacy->registerWithMangoPay($assetId);
    //             return $this->redirectToRoute('admin_asset_index');
    //         } catch (\Exception $ex) {
    //             $this->logger->error($ex->getMessage());

    //             return $this->redirectToRoute('admin_asset_index');
    //         }
    //     }
    //     return $this->redirectToRoute('admin_asset_index');
    // }

    //Adding the CMS Life Cycle Transition routes and methods (Action)
    #[Route(path: '/{id}/draftarchive', name: 'admin_asset_draftarchive')]
    public function draftArchiveAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->draftArchiveAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/draftsubmit', name: 'admin_asset_draftsubmit')]
    public function draftSubmitAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->draftSubmitAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/draftcancel', name: 'admin_asset_draftcancel')]
    public function draftCancelAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->draftCancelAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/submitarchive', name: 'admin_asset_submitarchive')]
    public function submitArchiveAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->submitArchiveAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/submitreject', name: 'admin_asset_submitreject')]
    public function submitRejectAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->submitRejectAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/submitcancel', name: 'admin_asset_submitcancel')]
    public function submitCancelAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->submitCancelAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/submitapprove', name: 'admin_asset_submitapprove')]
    public function submitApproveAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->submitApproveAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/approvearchive', name: 'admin_asset_approvearchive')]
    public function approveArchiveAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->approveArchiveAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/approvereject', name: 'admin_asset_approvereject')]
    public function approveRejectAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->approveRejectAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/approvecancel', name: 'admin_asset_approvecancel')]
    public function approveCancelAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->approveCancelAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/approvepublish', name: 'admin_asset_approvepublish')]
    public function approvePublishAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->approvePublishAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/publisharchive', name: 'admin_asset_publisharchive')]
    public function publishArchiveAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->publishArchiveAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/publishcancel', name: 'admin_asset_publishcancel')]
    public function publishCancelAction(Asset $asset): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_ASSET')) {
            if ($this->assetManagerLegacy->publishCancelAction($asset)) {
                return $this->redirectToRoute('admin_asset_index');
            } else {
                $this->addFlash(
                    'error',
                    'Unsupported state transition for chosen asset',
                );
            }
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/visibility/{visibility}', name: 'admin_asset_visibility')]
    public function assetVisibilityAction(Asset $asset, int $visibility): Response
    {
        if (!$this->permissionCheck('CAN_UPDATE_ASSET')) {
            return $this->redirectToRoute('admin_asset_index');
        }

        if ($this->assetManagerLegacy->setVisibility($asset, $visibility)) {
            $this->addFlash(
                'success',
                'Visibility successfully changed for asset ' . $asset->getId() . ' - '
                    . $asset->getName(),
            );
        } else {
            $this->addFlash(
                'error',
                'Visibility could not be changed for asset ' . $asset->getId() . ' - '
                    . $asset->getName(),
            );
        }
        return $this->redirectToRoute('admin_asset_index');
    }

    #[Route(path: '/{id}/status-logs/create', name: 'admin_asset_status_log_create')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createStateLog(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Create new asset status log');
        $redirectToRoute = 'admin_asset_edit';
        $redirectToId = $asset->getId();
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_product_status_logs',
                'admin_product_dashboard',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
            $redirectToId = $request->query->get('redirectId', $redirectToId);
        }

        $assetStatusLog = new AssetStatusLog();
        $assetStatusLog->setTransitionedBy($this->getUser());
        $form = $this->createForm(AssetStatusLogType::class, $assetStatusLog);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $asset->addStatusLog($assetStatusLog);
            $this->doctrine->getManager()->persist($assetStatusLog);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Successfully created new asset status log');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/assets/status_logs/create.html.twig', [
            'asset' => $asset,
            'assetStatusLog' => $assetStatusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
        ]);
    }

    #[Route(path: '/status-logs/{id}', name: 'admin_asset_status_log_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editStateLog(
        Request $request,
        AssetStatusLog $assetStatusLog,
    ): Response {
        $this->logger->debug('Edit new asset status log');
        $redirectToRoute = 'admin_asset_edit';
        $asset = $assetStatusLog->getAsset();
        $redirectToId = $asset->getId();
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_product_status_logs',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }

        $form = $this->createForm(AssetStatusLogType::class, $assetStatusLog);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Successfully updated asset status log');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/assets/status_logs/edit.html.twig', [
            'asset' => $asset,
            'assetStatusLog' => $assetStatusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
        ]);
    }

    protected function permissionCheck(string $attribute): bool
    {
        $currentUser = $this->getUser();

        switch ($attribute) {
            case 'CAN_CREATE_ASSET':
                if ($this->isGranted('CAN_CREATE_ASSET', $currentUser)) {
                    return true;
                }
                break;
            case 'CAN_UPDATE_ASSET':
                if ($this->isGranted('CAN_UPDATE_ASSET', $currentUser)) {
                    return true;
                }
                break;
        }

        return false;
    }
}
