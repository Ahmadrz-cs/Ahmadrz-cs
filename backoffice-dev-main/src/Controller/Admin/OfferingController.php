<?php

namespace App\Controller\Admin;

use App\Entity\BaseEntity;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\User;
use App\Form\Type\OfferingType;
use App\Form\Type\QueryOfferingType;
use App\Form\Type\QueryRaisedOfferingType;
use App\Repository\OfferingRepository;
use App\Repository\UserRepository;
use App\Service\Manager\AssetManager;
use App\Service\Manager\OfferingManager;
use App\Service\Manager\OfferingManagerV2;
use App\Service\Util\ExportHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/offering')]
class OfferingController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private OfferingManagerV2 $offeringManager,
        private OfferingRepository $offeringRepository,
        private UserRepository $userRepository,
        private AssetManager $assetManagerLegacy,
        private OfferingManager $offeringManagerLegacy,
        private Exporter $exporter,
    ) {}

    #[Route(path: '', name: 'admin_offering_index')]
    #[Route(path: '/list', name: 'admin_offering_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List offerings');
        $form = $this->createForm(QueryOfferingType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->offeringRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/offerings/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/export', name: 'admin_offering_list_export')]
    public function export(Request $request): StreamedResponse
    {
        $this->logger->info('Export offerings list');
        $filters = [];
        $form = $this->createForm(QueryOfferingType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData(); // default filters fallback if not valid
        }
        $query = $this->offeringRepository->buildQueryWithAssociations($filters, [
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
            ExportHelper::generateFileName('offerings', $format),
            new DoctrineORMQuerySourceIterator(
                $query,
                $this->doctrine
                    ->getManager()
                    ->getClassMetadata(Offering::class)
                    ->getFieldNames(),
            ),
        );
    }

    #[Route(path: '/add', name: 'admin_offering_add')]
    #[Route(path: '/{id}/edit', name: 'admin_offering_edit')]
    public function editAction(Request $request, ?Offering $offering = null): Response
    {
        $readOnly = false;

        if (!empty($offering)) {
            $action = 'edit';
            if (!$this->permissionCheck('CAN_UPDATE_OFFERING')) {
                $readOnly = true;
            }
        } else {
            $this->denyAccessUnlessGranted('CAN_CREATE_OFFERING', $this->getUser());
            $action = 'add';
            $offering = new Offering();

            /** @var User $user */
            $user = $this->getUser();
            $offering->setCreatedById($user->getId());

            //Setting Mandatory fields
            $offering->setVisibility(BaseEntity::VISIBILITY_AUTO);
        }

        //Get All Asset
        $asset = $this->assetManagerLegacy->findAllAsset();

        $form = $this->createForm(OfferingType::class, $offering, [
            'read_only' => $readOnly,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('addFields')->getData() as $customField) {
                if (!$customField->getOffering()) {
                    $customField->setOffering($form->getData());
                }
            }

            // foreach ($form->get('documents')->getData() as $document) {
            //     if (!$document->getOffering()) {
            //         $document->setOffering($form->getData());
            //     }
            // }

            if (!$this->offeringManagerLegacy->validateMinCommit($offering)) {
                $this->addFlash(
                    'warning',
                    'Min commit has been rounded to ' . $offering->getMinCommitUser(),
                );
            }

            if ($action == 'add') {
                $em = $this->doctrine->getManager();
                $em->persist($offering);
                $em->flush();
                $this->offeringManagerLegacy->sendNewOfferingCreationMail($offering);
            } elseif ($action == 'edit') {
                $em = $this->doctrine->getManager();
                $em->persist($offering);
                $em->flush();
            }
            return $this->redirect($this->generateUrl('admin_offering_index'));
        }
        return $this->render('admin/pages/offerings/edit.html.twig', [
            'offering' => $offering,
            'form' => $form->createView(),
            'isSecondaryMkt' => false,
        ]);
    }

    #[Route(path: '/{id}/view', name: 'admin_offering_view')]
    public function viewAction(Request $request, Offering $offering): Response
    {
        return $this->render('admin/pages/offerings/view.html.twig', [
            'offering' => $offering,
        ]);
    }

    // Legacy state transition routes
    #[Route(path: '/{id}/draftarchive', name: 'admin_offering_draftarchive')]
    public function draftArchiveAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->draftArchiveAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/draftsubmit', name: 'admin_offering_draftsubmit')]
    public function draftSubmitAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->draftSubmitAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/draftcancel', name: 'admin_offering_draftcancel')]
    public function draftCancelAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->draftCancelAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/submitarchive', name: 'admin_offering_submitarchive')]
    public function submitArchiveAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->submitArchiveAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/submitreject', name: 'admin_offering_submitreject')]
    public function submitRejectAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->submitRejectAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/submitcancel', name: 'admin_offering_submitcancel')]
    public function submitCancelAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->submitCancelAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/submitapprove', name: 'admin_offering_submitapprove')]
    public function submitApproveAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->submitApproveAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/approvearchive', name: 'admin_offering_approvearchive')]
    public function approveArchiveAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->approveArchiveAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/approvereject', name: 'admin_offering_approvereject')]
    public function approveRejectAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->approveRejectAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/approvecancel', name: 'admin_offering_approvecancel')]
    public function approveCancelAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->approveCancelAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/approvepublish', name: 'admin_offering_approvepublish')]
    public function approvePublishAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->approvePublishAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/publishrestrict', name: 'admin_offering_publishrestrict')]
    public function publishRestrictAction(
        Request $request,
        Offering $offering,
    ): Response {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->publishRestrictAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/publishclose', name: 'admin_offering_publishclose')]
    public function publishCloseAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->publishCloseAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/publishcancel', name: 'admin_offering_publishcancel')]
    public function publishCancelAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->publishCancelAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/closesettle', name: 'admin_offering_closesettle')]
    public function closeSettleAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->closeSettleAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/closecancel', name: 'admin_offering_closecancel')]
    public function closeCancelAction(Request $request, Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            $this->offeringManagerLegacy->closeCancelAction($offering);
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    // Modernised state transition routes
    #[Route(path: '/{id}/submit', name: 'admin_offering_submit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function submit(Offering $offering): Response
    {
        $this->offeringManagerLegacy->draftSubmitAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/approve', name: 'admin_offering_approve')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function approve(Offering $offering): Response
    {
        $this->offeringManagerLegacy->submitApproveAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/publish', name: 'admin_offering_publish')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function publish(Offering $offering): Response
    {
        $this->offeringManagerLegacy->approvePublishAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/reject', name: 'admin_offering_reject')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function reject(Offering $offering): Response
    {
        $this->offeringManagerLegacy->approveRejectAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/cancel', name: 'admin_offering_cancel')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function cancel(Offering $offering): Response
    {
        $this->offeringManagerLegacy->publishCancelAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/close', name: 'admin_offering_close')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function close(Offering $offering): Response
    {
        $this->offeringManagerLegacy->publishCloseAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/unrestrict', name: 'admin_offering_unrestrict')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function unrestrict(Offering $offering): Response
    {
        $this->offeringManagerLegacy->publishRestrictAction($offering);
        return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
    }

    #[Route(path: '/{id}/visibility/{visibility}', name: 'admin_offering_visibility')]
    public function offeringVisibilityAction(
        Request $request,
        Offering $offering,
        int $visibility,
    ): Response {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            if ($this->offeringManagerLegacy->setVisibility($offering, $visibility)) {
                $this->addFlash(
                    'success',
                    "Visibility successfully changed to {$offering->getVisibility()} for offering '{$offering->getId()} - {$offering->getName()}'",
                );
            } else {
                $this->addFlash(
                    'error',
                    "Visibility could not be changed for offering '{$offering->getId()} - {$offering->getName()}'",
                );
            }
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            ProductController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
            // Handle filter and pagination options
            $otherParams = [];
            if (!is_null($request->query->get('redirectRelisting'))) {
                $otherParams['sell_investment'] = $request->query->get(
                    'redirectRelisting',
                );
            }
            if (!is_null($request->query->get('redirectPage'))) {
                $otherParams['page'] = $request->query->get('redirectPage');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_offering_index',
                array_merge(['id' => $offering->getAsset()->getId()], $otherParams),
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/retail-mode', name: 'admin_offering_retail_mode')]
    public function offeringRetailMode(Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            try {
                $this->offeringManagerLegacy->switchOfferingMode($offering, 'retail');
                $this->addFlash(
                    'success',
                    $offering->getId()
                    . ' '
                    . $offering->getName()
                    . ': set to retail type, visibility to auto',
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'Offering mode switch to retail failed ' . $e->getMessage(),
                );
                $this->addFlash('error', 'Could not update offering');
            }
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/prefunding-mode', name: 'admin_offering_prefunding_mode')]
    public function offeringPrefundingMode(Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            try {
                $this->offeringManagerLegacy->switchOfferingMode(
                    $offering,
                    'prefunding',
                );
                $this->addFlash(
                    'success',
                    $offering->getId()
                    . ' '
                    . $offering->getName()
                    . ': set to prefunding type, visibility to VIP only',
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'Offering mode switch to prefunding failed ' . $e->getMessage(),
                );
                $this->addFlash('error', 'Could not update offering');
            }
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(path: '/{id}/feature-offering', name: 'admin_offering_featured_mode')]
    public function featuredOfferingAction(Offering $offering): Response
    {
        if ($this->permissionCheck('CAN_UPDATE_OFFERING')) {
            try {
                $this->offeringManagerLegacy->toggleOfferingFeaturedStatus($offering);
                $this->addFlash(
                    'success',
                    $offering->getId()
                    . ' '
                    . $offering->getName()
                    . ': set to featured',
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'Offering mode switch to featured failed ' . $e->getMessage(),
                );
                $this->addFlash('error', 'Could not update offering');
            }
        }
        return $this->redirectToRoute('admin_offering_index');
    }

    #[Route(
        '/funding-progress',
        name: 'admin_offering_funding_progress',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function fundingProgress(Request $request): Response
    {
        $this->logger->debug('View special offerings raised amount filterable table');
        $filters = [
            'includePrefunding' => false,
            'firstPartyOnly' => null,
            'lifecycleStatus' => ['draft', 'submitted', 'approved', 'published'],
        ];
        $form = $this->createForm(QueryRaisedOfferingType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->offeringRepository->queryRaisedAmount(
            filters: $filters ?? [],
            orderDirection: $filters['orderDirection'] ?? 'DESC',
            limit: $filters['perPage'] ?? 10,
            page: $filters['page'] ?? 1,
        );

        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        if ($request->query->get('export')) {
            return $this->exporter->getResponse(
                $format,
                ExportHelper::generateFileName('offering_funding_progress_', $format),
                new ArraySourceIterator($results),
            );
        }

        return $this->render('admin/pages/offerings/funding_progress.html.twig', [
            'results' => $results,
            'form' => $form->createView(),
        ]);
    }

    protected function permissionCheck(string $attribute)
    {
        $currentUser = $this->getUser();

        switch ($attribute) {
            case 'CAN_CREATE_OFFERING':
                if ($this->isGranted('CAN_CREATE_OFFERING', $currentUser)) {
                    return true;
                }
                break;
            case 'CAN_UPDATE_OFFERING':
                if ($this->isGranted('CAN_UPDATE_OFFERING', $currentUser)) {
                    return true;
                }
                break;
        }

        return false;
    }
}
