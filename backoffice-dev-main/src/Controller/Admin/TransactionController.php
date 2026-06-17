<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Form\Type\QueryTransactionType;
use App\Form\Type\TransactionType;
use App\Repository\TransactionRepository;
use App\Service\MangopayWalletService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions')]
class TransactionController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionRepository $transactionRepository,
        private MangopayWalletService $mangopayService,
    ) {}

    #[Route('', name: 'admin_transactions_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(Request $request): Response
    {
        $this->logger->debug('List transactions');
        $form = $this->createForm(QueryTransactionType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transactionRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/transactions/index.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_transactions_view', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function view(Transaction $transaction): Response
    {
        $this->logger->debug("View transaction #{$transaction->getId()}");
        return $this->render('admin/pages/transactions/view.html.twig', [
            'object' => $transaction,
        ]);
    }

    #[Route(
        '/{id}/mangopay-sync',
        name: 'admin_transactions_mangopay_sync',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function mangopaySync(
        Transaction $transaction,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($transaction->getReferenceId()) {
            $this->logger->debug(
                "Sync transaction #{$transaction->getId()} with Mangopay",
            );
            try {
                $transfer = $this->mangopayService->getTransfer(
                    $transaction->getReferenceId(),
                );
                $transaction->setPaymentStatus($transfer->Status);
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    "Successfully synced transaction status with Mangopay to: {$transaction->getPaymentStatus()}",
                );
            } catch (\Exception $e) {
                $this->logger->warning(
                    "Failed to sync transaction. Could not retrieve Mangopay transfer with id {$transaction->getReferenceId()}",
                    [$e->getMessage()],
                );
                $this->addFlash(
                    'warning',
                    "Failed to sync transaction. Could not retrieve Mangopay transfer with id: {$transaction->getReferenceId()}",
                );
            }
        } else {
            $this->logger->debug(
                "Cannot sync transaction #{$transaction->getId()} with Mangopay. Missing external reference ID",
            );
            $this->addFlash('warning', 'Transaction missing external reference ID');
        }
        return $this->redirectToRoute('admin_transactions_view', [
            'id' => $transaction->getId(),
        ]);
    }
}
