<?php

namespace App\Controller\Admin;

use App\Form\Type\QueryMangopayTransactionsType;
use App\Service\MangopayWalletService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wallets')]
#[IsGranted('ROLE_ANALYST')]
class WalletController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWalletService $walletService,
    ) {}

    #[Route('/{walletId}', name: 'admin_wallet_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(Request $request, string $walletId): Response
    {
        $this->logger->debug("Edit asset wallet {$walletId} description");
        try {
            $wallet = $this->walletService->getWallet($walletId, 'USER_NOT_PRESENT');
        } catch (\Exception $e) {
            $this->logger->error(
                'Could not find or update wallet: ' . $e->getMessage(),
            );
            throw $this->createNotFoundException('Wallet not found');
        }

        $form = $this
            ->createFormBuilder($wallet)
            ->add('Description', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Save Changes'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $wallet = $this->walletService->updateWallet($wallet);
                $this->addFlash(
                    'success',
                    "Wallet {$walletId} description successfully updated",
                );
                $this->logger->info(
                    "Wallet {$walletId} description successfully updated",
                );
            } catch (\Exception $e) {
                $this->logger->error('Could not update wallet: ' . $e->getMessage());
            }
            return $this->redirectToRoute('admin_asset_wallet_list');
        }

        return $this->render('admin/pages/assets/edit_wallet.html.twig', [
            'form' => $form->createView(),
            'wallet' => $wallet,
        ]);
    }

    #[Route(
        '/{walletId}/transactions',
        name: 'admin_wallet_transactions',
        methods: ['GET'],
    )]
    public function transactions(Request $request, string $walletId): Response
    {
        // $this->logger->debug("View wallet {$walletId} transactions");
        $pagination = new \MangoPay\Pagination();
        $sorting = new \MangoPay\Sorting();
        $sorting->AddField('CreationDate', 'DESC');
        $filters = new \MangoPay\FilterTransactions();
        $filters->ScaContext = 'USER_NOT_PRESENT';
        $queryConfig = [
            'page' => $pagination->Page,
            'perPage' => $pagination->ItemsPerPage,
            'filters' => $filters,
        ];
        $form = $this->createForm(QueryMangopayTransactionsType::class, $queryConfig);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $queryConfig = $form->getData();
            $pagination->Page = $queryConfig['page'];
            $pagination->ItemsPerPage = $queryConfig['perPage'];
        }
        try {
            $this->logger->debug('Query ' . json_encode($queryConfig['filters']));
            $transactions = $this->walletService->mangopayApi->Wallets->GetTransactions(
                $walletId,
                $pagination,
                $queryConfig['filters'],
                $sorting,
            );
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving transactions', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error retrieving transactions ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving transactions', [$e->getMessage()]);
            $this->addFlash(
                'error',
                'Error retrieving transactions ' . $e->getMessage(),
            );
        }
        // Clamp pagination for rendering
        $pagination->Page = min($pagination->Page ?? 1, $pagination->TotalPages);
        return $this->render('admin/pages/wallets/transactions.html.twig', [
            'form' => $form->createView(),
            'results' => $transactions ?? [],
            'pagination' => $pagination,
            'walletId' => $walletId,
        ]);
    }
}
