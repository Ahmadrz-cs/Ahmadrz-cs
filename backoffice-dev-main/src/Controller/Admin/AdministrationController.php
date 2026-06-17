<?php

namespace App\Controller\Admin;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\DirectDebit;
use App\Entity\Document;
use App\Entity\User;
use App\Service\Manager\DirectDebitManager;
use App\Service\Manager\DocumentManager;
use App\Service\MangoPay;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/administration')]
class AdministrationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private Security $security,
    ) {}

    #[Route(path: '/documents', name: 'admin_administration_doc_dashboard')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function documentsOverview(DocumentManager $documentManager): Response
    {
        $this->logger->debug('Document Sync Dashboard');

        /** @var \App\Entity\DocumentRepository */
        $repository = $this->doctrine->getManager()->getRepository(Document::class);
        $totalDocCount = $repository->count([]);

        $docTypes = ['asset', 'offering', 'user', 'investment'];
        $docCounts = [];
        foreach ($docTypes as $docType) {
            /** @var \Doctrine\ORM\EntityRepository */
            $docTypeRepository = $documentManager->getRepositoryForDocType($docType);
            $docCounts[$docType]['all'] = $docTypeRepository->count([]);
            $docCounts[$docType]['hasurl'] = count($docTypeRepository->getDocumentInfoWithDocCriteria(
                [
                    'documentUrl' => 'IS NOT NULL',
                ],
                false,
            ));
        }

        return $this->render('admin/pages/administration/documents_overview.html.twig', [
            'total_count' => $totalDocCount,
            'total_count_with_url' =>
                $totalDocCount - $repository->count(['documentUrl' => null]),
            'doc_counts' => $docCounts,
        ]);
    }

    #[Route(path: '/documents/{type}', name: 'admin_document_manage')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function manageDocuments(
        string $type,
        DocumentManager $documentManager,
    ): Response {
        $this->logger->debug('Sync Manager for ' . $type . ' documents');

        /** @var \Doctrine\ORM\EntityRepository */
        $repository = $documentManager->getRepositoryForDocType($type);
        $docCount = $repository->count([]);
        $unsyncedDocs = $documentManager->getUnsyncedDocs($type);

        return $this->render('admin/pages/administration/documents_manage.html.twig', [
            'doc_type' => $type,
            'total_count' => $docCount,
            'unsynced_docs' => $unsyncedDocs,
        ]);
    }

    #[Route(path: '/directdebit', name: 'direct_debit_payins')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function viewDueDirectDebitPayins(MangoPay $mangopayService): Response
    {
        /** @var \App\Repository\DirectDebitRepository */
        $repository = $this->doctrine->getManager()->getRepository(DirectDebit::class);
        $directDebits = $repository->getDueDirectDebits();

        $status = [];
        $statusCode = [];

        foreach ($directDebits as $debitObj) {
            $mandateId = $debitObj->getMangopayMandateId();
            $mangopayMandate = $mangopayService->getMandate($mandateId);

            $status[$mandateId] = $mangopayMandate->Status;
            $statusCode[$mandateId] = $mangopayMandate->ResultCode;
        }

        return $this->render('admin/pages/administration/direct_debit_payins.html.twig', [
            'directDebits' => $directDebits,
            'status' => $status,
            'statusCode' => $statusCode,
        ]);
    }

    #[Route(path: '/directdebit/all', name: 'direct_debit_all')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function viewAllDirectDebitPayins(MangoPay $mangopayService): Response
    {
        /** @var \App\Repository\DirectDebitRepository */
        $repository = $this->doctrine->getManager()->getRepository(DirectDebit::class);
        $directDebits = $repository->findAll();

        $status = [];
        $statusCode = [];

        foreach ($directDebits as $debitObj) {
            $mandateId = $debitObj->getMangopayMandateId();
            $mangopayMandate = $mangopayService->getMandate($mandateId);

            $status[$mandateId] = $mangopayMandate->Status;
            $statusCode[$mandateId] = $mangopayMandate->ResultCode;
        }

        return $this->render('admin/pages/administration/direct_debit_all.html.twig', [
            'directDebits' => $directDebits,
            'status' => $status,
            'statusCode' => $statusCode,
        ]);
    }

    #[Route(path: '/directdebit/settled', name: 'direct_debit_payins_settled')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function viewSettledDirectDebitPayins(MangoPay $mangopayService): Response
    {
        /** @var \App\Repository\DirectDebitRepository */
        $repository = $this->doctrine->getManager()->getRepository(DirectDebit::class);
        $directDebits = $repository->getSettledDirectDebits();

        $status = [];
        $statusCode = [];

        foreach ($directDebits as $debitObj) {
            $mandateId = $debitObj->getMangopayMandateId();
            $mangopayMandate = $mangopayService->getMandate($mandateId);

            $status[$mandateId] = $mangopayMandate->Status;
            $statusCode[$mandateId] = $mangopayMandate->ResultCode;
        }

        return $this->render('admin/pages/administration/direct_debit_settled_payins.html.twig', [
            'directDebits' => $directDebits,
            'status' => $status,
            'statusCode' => $statusCode,
        ]);
    }

    #[Route(
        path: '/directdebit/{userId}/{mandateId}/{amount}',
        name: 'direct_debit_process_payins',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function directDebitPayin(
        int $userId,
        int $mandateId,
        float $amount,
        MangoPay $mangopayService,
        DirectDebitManager $directDebitManager,
    ): Response {
        $this->logger->info('=============IN directDebitPayin===========');

        $userRepo = $this->doctrine->getManager()->getRepository(User::class);
        $user = $userRepo->findOneBy(['id' => $userId]);

        try {
            $directDebitPayin = $mangopayService->directDebitPayin(
                $user,
                $mandateId,
                $amount,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay directDebitPayin ' . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_DIRECT_DEBIT_PAYIN_FAILED,
                $e,
            );
        }
        /** @var \App\Repository\DirectDebitRepository */
        $repository = $this->doctrine->getManager()->getRepository(DirectDebit::class);
        $directDebit = $repository->findOneBy(['user' => $user]);

        $time = new \DateTime();
        $directDebit->setLastSettlementDate($time);

        $this->doctrine->getManager()->persist($directDebit);
        $this->doctrine->getManager()->flush();

        $formatAmount = number_format(((float) $amount / 100) + 0.60, 2, '.', '');
        $directDebitManager->sendDirectDebitPaymentProcessedMail($user, $formatAmount);

        return $this->redirect($this->generateUrl('direct_debit_payins'));
    }
}
