<?php

namespace App\Controller\Admin;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TransferType;
use App\Entity\TransferRequest;
use App\Form\Type\TransferRequestType;
use App\Repository\TransactionRepository;
use App\Service\MangopayWalletService;
use App\Service\TransferOrderService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/transfer-requests')]
class TransferRequestController extends AbstractController
{
    public const RESTRICTED_REDIRECT_ROUTES = [
        'admin_monthend_fee_collection_manage',
        'admin_monthend_income_disaggregation_manage',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private TagAwareCacheInterface $defaultAppCache,
        private TransferOrderService $transferOrderService,
        private MangopayWalletService $mangopayWalletService,
        private TransactionRepository $transactionRepository,
    ) {}

    #[Route(
        '/{id}/edit',
        name: 'admin_transfer_request_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editTransfer(
        Request $request,
        TransferRequest $transferRequest,
    ): Response {
        $this->logger->debug('Edit transfer', [$transferRequest->getId()]);
        if ($request->query->get('restricted', false)) {
            // special editor only supports multi-asset monthend tools
            $redirectToRoute =
                match ($transferRequest->getTransferOrder()->getTransferType()) {
                    TransferType::FeeCollection
                        => 'admin_monthend_fee_collection_manage',
                    TransferType::IncomeDisaggregation
                        => 'admin_monthend_income_disaggregation_manage',
                    default => 'admin_transfer_order_manage',
                };
            $renderTemplate =
                match ($transferRequest->getTransferOrder()->getTransferType()) {
                    TransferType::FeeCollection,
                    TransferType::IncomeDisaggregation,
                        => 'restricted_edit',
                    default => 'edit',
                };
        } else {
            $renderTemplate = 'edit';
        }

        $form = $this->createForm(TransferRequestType::class, $transferRequest, [
            'restricted' => $request->query->get('restricted', false),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                "Transfer from {$transferRequest->getDebitWalletId()} to {$transferRequest->getCreditWalletId()} successfully updated",
            );
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_transfer_order_manage',
                ['id' => $transferRequest->getTransferOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render(
            "admin/pages/transfer_request/{$renderTemplate}.html.twig",
            [
                'transferRequest' => $transferRequest,
                'form' => $form->createView(),
                'exitRoute' => $redirectToRoute ?? 'admin_transfer_order_manage',
            ],
        );
    }

    #[Route('/{id}/delete', name: 'admin_transfer_request_delete', methods: ['GET'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function deleteTransfer(
        Request $request,
        TransferRequest $transferRequest,
    ): Response {
        if (
            AbstractOrder::STATE_DRAFT != $transferRequest->getTransferOrder()->getStatus()
        ) {
            $this->addFlash(
                'warning',
                'Transfers can only be removed when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_transfer_order_manage',
                ['id' => $transferRequest->getTransferOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $this->logger->info('Delete transfer', [$transferRequest->getId()]);
        $this->doctrine->getManager()->remove($transferRequest);
        $this->doctrine->getManager()->flush();
        $this->addFlash(
            'success',
            "Transfer from {$transferRequest->getDebitWalletId()} to {$transferRequest->getCreditWalletId()} successfully deleted",
        );
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_transfer_order_manage',
            ['id' => $transferRequest->getTransferOrder()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route('/{id}/run', name: 'admin_transfer_request_run', methods: ['GET'])]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function runTransfer(
        Request $request,
        TransferRequest $transferRequest,
    ): Response {
        $this->logger->info('Make single transfer', [$transferRequest->getId()]);
        try {
            $this->transferOrderService->runRequest($transferRequest);
            $this->addFlash('success', 'Transfer successfully made');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Unable to make transfer. ' . $e->getMessage());
            $this->logger->error('Unable to make transfer. ', [$e->getMessage()]);
        }
        $this->doctrine->getManager()->flush();
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_transfer_order_manage',
            ['id' => $transferRequest->getTransferOrder()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/search-transfer',
        name: 'admin_transfer_request_search_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function searchTransfer(
        Request $request,
        TransferRequest $transferRequest,
    ): Response {
        $this->logger->info('Find Mangopay transfer to link to request', [$transferRequest->getId()]);
        $form = $this
            ->createFormBuilder()
            ->add('transferId', TextType::class, [
                'label' => 'Mangopay transfer ID',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Search for Transfer'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute(
                'admin_transfer_request_link',
                [
                    'id' => $transferRequest->getId(),
                    'transferId' => $form->getData()['transferId'],
                ],
                Response::HTTP_SEE_OTHER,
            );
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->render('admin/pages/transfer_request/search.html.twig', [
            'transferRequest' => $transferRequest,
            'form' => $form->createView(),
            'exitRoute' => $redirectToRoute ?? 'admin_transfer_order_manage',
        ]);
    }

    #[Route(
        '/{id}/link/{transferId}',
        name: 'admin_transfer_request_link',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function linkTransfer(
        Request $request,
        TransferRequest $transferRequest,
        string $transferId,
    ): Response {
        $this->logger->info('Compare Mangopay transfer to link to request', [
            $transferRequest->getId(),
            $transferId,
        ]);
        try {
            $transfer = $this->defaultAppCache->get(
                "mangopay_{$transferId}",
                function (ItemInterface $item) use ($transferId): ?\Mangopay\Transfer {
                    $item->tag(['mangopay', 'transfer']);
                    return $this->mangopayWalletService->getTransfer($transferId);
                },
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                "Unable to find transfer {$transferId}. " . $e->getMessage(),
            );
            $this->logger->warning(
                "Unable to find transfer {$transferId}.",
                [$e->getMessage()],
            );
            return $this->redirectToRoute(
                'admin_transfer_request_search_transfer',
                ['id' => $transferRequest->getTransferOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $notAlreadyLinked = empty($this->transactionRepository->findBy(
            ['external_id' => $transferId],
            null,
            1,
        ));
        $isLinkable = $this->transferOrderService->isTransferLinkable(
            $transferRequest,
            $transfer,
        );
        if ($isLinkable and $notAlreadyLinked) {
            $form = $this
                ->createFormBuilder()
                ->add('submit', SubmitType::class, [
                    'label' => 'Link Transfer',
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->transferOrderService->runRequest($transferRequest, $transfer);
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    $redirectToRoute ?? 'admin_transfer_order_manage',
                    ['id' => $transferRequest->getTransferOrder()->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            }
        } else {
            $this->addFlash(
                'warning',
                "Cannot link transfer {$transferId}. Some hard requirements not met.",
            );
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->render('admin/pages/transfer_request/link.html.twig', [
            'transferRequest' => $transferRequest,
            'walletTransfer' => $transfer,
            'isLinkable' => $isLinkable,
            'notAlreadyLinked' => $notAlreadyLinked,
            'form' => $form ?? null,
            'exitRoute' => $redirectToRoute ?? 'admin_transfer_order_manage',
        ]);
    }
}
