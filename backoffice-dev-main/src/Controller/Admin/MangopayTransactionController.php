<?php

namespace App\Controller\Admin;

use App\Form\Type\MangopayRefundType;
use App\Service\MangopayWalletService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions/mangopay')]
#[IsGranted('ROLE_ANALYST')]
class MangopayTransactionController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWalletService $walletService,
    ) {}

    #[Route(
        '/transfers/{transferId}',
        name: 'admin_transactions_mangopay_transfer',
        methods: ['GET'],
    )]
    public function transferSingle(string $transferId): Response
    {
        $this->logger->debug("View Mangopay transfer id: {$transferId}");
        try {
            $transfer = $this->walletService->getTransfer($transferId);
            $refunds = $this->walletService->listTransferRefunds($transferId);
        } catch (\Exception $e) {
            $this->logger->error(
                "Could not find transfer with id: {$transferId}. " . $e->getMessage(),
            );
            throw $this->createNotFoundException('Transfer not found');
        }
        return $this->render('admin/pages/transactions/mangopay/transaction_single.html.twig', [
            'transaction' => $transfer,
            'refunds' => $refunds,
        ]);
    }

    #[Route(
        '/refunds/{refundId}',
        name: 'admin_transactions_mangopay_refund',
        methods: ['GET'],
    )]
    public function refundSingle(string $refundId): Response
    {
        $this->logger->debug("View Mangopay refund id: {$refundId}");
        try {
            $transfer = $this->walletService->getRefund($refundId);
        } catch (\Exception $e) {
            $this->logger->error(
                "Could not find refund with id: {$refundId}. " . $e->getMessage(),
            );
            throw $this->createNotFoundException('Refund not found');
        }
        return $this->render('admin/pages/transactions/mangopay/transaction_single.html.twig', [
            'transaction' => $transfer,
        ]);
    }

    #[Route(
        path: '/transfers/{transferId}/refund',
        name: 'admin_transactions_mangopay_transfer_refund',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function transferRefund(Request $request, string $transferId): Response
    {
        $this->logger->debug(
            "Configure Mangopay refund for transfer id: {$transferId}",
        );
        try {
            $initialTransfer = $this->walletService->getTransfer($transferId);
        } catch (\Exception $e) {
            $this->logger->error(
                "Could not find initial transfer with id: {$transferId}. "
                    . $e->getMessage(),
            );
            throw $this->createNotFoundException('Transfer not found');
        }

        $refund = new \MangoPay\Refund();
        $refund->AuthorId = $initialTransfer->AuthorId;
        $refund->InitialTransactionId = $transferId;

        $form = $this->createForm(MangopayRefundType::class, $refund);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->debug(
                    "Submitting Mangopay refund for transfer id: {$transferId}",
                );
                $refund = $this->walletService->createTransferRefund(
                    $transferId,
                    $refund,
                );
                $this->addFlash('success', 'Mangopay refund successfully created');
                return $this->redirectToRoute('admin_transactions_mangopay_refund', [
                    'refundId' => $refund->Id,
                ]);
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error creating Mangopay refund', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error creating Mangopay refund ' . $e->getMessage() . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Error creating Mangopay refund', [$e->getMessage()]);
                $this->addFlash(
                    'error',
                    'Error creating Mangopay refund ' . $e->getMessage(),
                );
            }
        }
        return $this->render('admin/pages/transactions/mangopay/refund.html.twig', [
            'transaction' => $initialTransfer,
            'form' => $form,
        ]);
    }
}
