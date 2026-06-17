<?php

namespace App\Controller\Admin;

use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Payout;
use App\Form\Type\AdminStatusType;
use App\Form\Type\InvestmentDocumentType;
use App\Form\Type\InvestmentType;
use App\Form\Type\PayoutType;
use App\Form\Type\QueryInvestmentType;
use App\Repository\InvestmentRepository;
use App\Service\Manager\AssetManagerV2;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\InvestmentManager;
use App\Service\Manager\InvestmentManagerV2;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/investment')]
class InvestmentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private FormFactoryInterface $formFactory,
        private InvestmentManagerV2 $investmentManager,
        private InvestmentRepository $investmentRepository,
        private AssetManagerV2 $assetManager,
        private DocumentManager $documentManager,
        private InvestmentManager $investmentManagerLegacy,
    ) {}

    #[Route(path: '', name: 'admin_investment_index')]
    #[Route(path: '/list', name: 'admin_investment_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List investments');
        $form = $this->createForm(QueryInvestmentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->investmentRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/investments/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/add', name: 'admin_investment_add')]
    #[Route(path: '/{id}/edit', name: 'admin_investment_edit')]
    public function editAction(
        Request $request,
        ?Investment $investment = null,
    ): Response {
        $readOnly = false;

        if (!empty($investment)) {
            if (!$this->permissionCheck('CAN_UPDATE_INVESTMENT')) {
                $readOnly = true;
            }
            $action = 'edit';
        } else {
            $this->denyAccessUnlessGranted('CAN_CREATE_INVESTMENT', $this->getUser());
            $action = 'add';
            $investment = new Investment();
            $investmentStatus = new InvestmentStatus();

            /**
             * New investment defaults (can be overriden by form)
             * - LifecycleStatus: Settled - must be settled to allow overriding settledOn date
             * - Type: off-market
             */
            $investmentStatus->setLifecycleStatus('settled');

            $investment->setType('Off-market');
            $investment->setStatus($investmentStatus);
            $investment->setCreatedById($this->getUser()->getId());

            //Setting Mandatory fields
            $investment->setVisibility(BaseEntity::VISIBILITY_AUTO);

            // Platform is GBP only, set Default currency
            // Should really use dependency injection for these params to do type hinting properly
            /** @var string $defaultCurrency */
            $defaultCurrency = $this->getParameter('default_ccy');
            $investment->setCurrency($defaultCurrency);
        }

        $form = $this->createForm(InvestmentType::class, $investment, [
            'read_only' => $readOnly,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('addFields')->getData() as $customField) {
                if (!$customField->getInvestment()) {
                    $customField->setInvestment($form->getData());
                }
            }

            if ($action == 'add') {
                $investment->setName($form->get('user')->getData());

                $em = $this->doctrine->getManager();
                $em->persist($investment);
                $em->flush();

                return $this->redirect($this->generateUrl('admin_investment_edit', [
                    'id' => $investment->getId(),
                ]));
            } elseif ($action == 'edit') {
                $em = $this->doctrine->getManager();
                $em->persist($investment);
                $em->flush();

                $this->addFlash('success', 'Investment updated successfully');
                return $this->redirect($this->generateUrl('admin_investment_edit', [
                    'id' => $investment->getId(),
                ]));
            }
        }
        return $this->render('admin/pages/investments/edit.html.twig', [
            'investment' => $investment,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/status', name: 'admin_investment_status_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function statusEdit(Request $request, Investment $investment): Response
    {
        $statusChoices = [
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_REJECTED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_SETTLED,
        ];
        $form = $this->createForm(AdminStatusType::class, $investment, [
            'data_class' => Investment::class,
            'statusChoices' => $statusChoices,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->flush();
            $this->addFlash(
                'success',
                "Status updated to {$investment->getLifecycleStatus()}",
            );
            return $this->redirectToRoute('admin_investment_status_edit', [
                'id' => $investment->getId(),
            ]);
        }
        return $this->render('admin/pages/maintenance/entity_status.html.twig', [
            'entityType' => 'Investment',
            'exitRoute' => 'admin_investment_edit',
            'object' => $investment,
            'form' => $form->createView(),
            'statusPlaces' => $statusChoices,
        ]);
    }

    #[Route(path: '/{id}/view', name: 'admin_investment_view')]
    public function viewAction(Investment $investment): Response
    {
        return $this->render('admin/pages/investments/view.html.twig', [
            'investment' => $investment,
        ]);
    }

    #[Route(path: '/{id}/approve', name: 'admin_investment_approve')]
    public function approveAction(Investment $investment): Response
    {
        if (!$this->permissionCheck('CAN_UPDATE_INVESTMENT')) {
            return $this->redirectToRoute('admin_investment_index');
        }

        if ($this->investmentManagerLegacy->approveInvestment($investment)) {
            return $this->redirectToRoute('admin_investment_index');
        } else {
            $this->addFlash(
                'error',
                'Unsupported state transition for chosen investment',
            );
        }
        return $this->redirectToRoute('admin_investment_index');
    }

    #[Route(path: '/{id}/reject', name: 'admin_investment_reject')]
    public function rejectAction(Investment $investment): Response
    {
        if (!$this->permissionCheck('CAN_UPDATE_INVESTMENT')) {
            return $this->redirectToRoute('admin_investment_index');
        }

        if ($this->investmentManagerLegacy->rejectInvestment($investment)) {
            return $this->redirectToRoute('admin_investment_index');
        } else {
            $this->addFlash(
                'error',
                'Unsupported state transition for chosen investment',
            );
        }
        return $this->redirectToRoute('admin_investment_index');
    }

    #[Route(path: '/{id}/withdraw', name: 'admin_investment_withdraw')]
    public function withdrawAction(Investment $investment): Response
    {
        if (!$this->permissionCheck('CAN_UPDATE_INVESTMENT')) {
            return $this->redirectToRoute('admin_investment_index');
        }

        if ($this->investmentManagerLegacy->withdrawInvestment($investment)) {
            return $this->redirectToRoute('admin_investment_index');
        } else {
            $this->addFlash(
                'error',
                'Unsupported state transition for chosen investment',
            );
        }
        return $this->redirectToRoute('admin_investment_index');
    }

    #[Route(path: '/{id}/add_document', name: 'admin_investment_add_document')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addInvestmentDocumentAction(
        Request $request,
        ?Investment $investment = null,
    ): Response {
        if (!$this->permissionCheck('CAN_CREATE_DOC')) {
            return $this->redirectToRoute('admin_investment_index');
        }

        $action = 'add';
        $investmentDocument = new InvestmentDocuments();
        $investmentDocument->setInvestment($investment);
        $newDocument = new Document();
        $investmentDocument->getDocument()->add($newDocument);

        $form = $this->createForm(InvestmentDocumentType::class, $investmentDocument, [
            'action' => $this->generateUrl('admin_investment_add_document', [
                'id' => $investment->getId(),
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $allDocument = $form->getData()->getDocument();

            foreach ($allDocument as $singleDocument) {
                if ($action == 'add') {
                    $docPathPrefix =
                        'investment/' . $form->getData()->getInvestment()->getId();
                    try {
                        $newDocument = $this->documentManager->linkDocument(
                            $newDocument,
                            $singleDocument->getFile(),
                            'private',
                            $docPathPrefix,
                        );
                        $newDocument->setCreatedById($this->getUser()->getId());
                        $investmentDocument->setDocument($newDocument);
                    } catch (\Exception $e) {
                        $this->logger->error(
                            'Unable to upload document to filestore: '
                                . $e->getMessage(),
                        );
                        $this->addFlash(
                            'error',
                            'Unable to upload document to filestore (S3). Please contact admin.',
                        );
                        return $this->redirect($this->generateUrl(
                            'admin_investmentdocument_index',
                        ));
                    }
                } else {
                    //on an edit on the description and tag can be set
                    $investmentDocument->setDocument($allDocument);
                }
            }

            // save changes
            $em = $this->doctrine->getManager();
            $em->persist($investmentDocument);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_investmentdocument_index', [
                'id' => $investmentDocument->getId(),
            ]));
        }
        return $this->render('admin/pages/documents/investment/edit.html.twig', [
            'investmentdocument' => $investmentDocument,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/add_payout', name: 'admin_investment_add_payout')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addInvestmentPayoutAction(
        Request $request,
        ?Investment $investment = null,
    ): Response {
        if (!$this->permissionCheck('CAN_UPDATE_INVESTMENT')) {
            return $this->redirectToRoute('admin_investment_index');
        }

        if (
            !empty($investment)
            && $investment->getLifecycleStatus() !== InvestmentLifecycle::STATE_SETTLED
        ) {
            $this->addFlash(
                'warning',
                'REMINDER: You should only add payouts to SETTLED investments',
            );
        }

        $payout = new Payout();
        $payout->setInvestment($investment);
        $payout->setCreatedById($this->getUser()->getId());
        $payout->setCurrency('GBP');
        $payout->setPayoutType(0);

        $form = $this->createForm(PayoutType::class, $payout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($payout);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_payout_index', [
                'id' => $payout->getId(),
            ]));
        }
        return $this->render('admin/pages/payouts/edit.html.twig', [
            'payout' => $payout,
            'form' => $form->createView(),
        ]);
    }

    protected function permissionCheck(string $attribute): bool
    {
        $currentUser = $this->getUser();

        switch ($attribute) {
            case 'CAN_CREATE_INVESTMENT':
                if ($this->isGranted('CAN_CREATE_INVESTMENT', $currentUser)) {
                    return true;
                }
                break;
            case 'CAN_UPDATE_INVESTMENT':
                if ($this->isGranted('CAN_UPDATE_INVESTMENT', $currentUser)) {
                    return true;
                }
                break;
            case 'CAN_CREATE_DOC':
                if ($this->isGranted('CAN_CREATE_DOC', $currentUser)) {
                    return true;
                }
                break;
        }

        return false;
    }
}
