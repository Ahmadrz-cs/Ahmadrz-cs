<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserDocument;
use App\Form\Type\QueryMangopayKycDocType;
use App\Service\DocumentService;
use App\Service\MangopayKycService;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/kyc/mangopay')]
#[IsGranted('ROLE_ANALYST')]
class MangopayKycController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayKycService $mangopayKycService,
        private DocumentService $documentService,
    ) {}

    // See about optional params https://symfony.com/doc/current/routing.html#optional-parameters
    #[Route('/documents/{user?}', name: 'admin_kyc_mangopay_docs', methods: ['GET'])]
    public function mangopayDocs(
        Request $request,
        #[MapEntity(id: 'user')] ?User $user = null,
    ): Response {
        // $this->logger->debug('Showing Mangopay kyc docs');
        $pagination = new \MangoPay\Pagination();
        $sorting = new \MangoPay\Sorting();
        $sorting->AddField('CreationDate', 'DESC');
        $queryConfig = [
            'page' => $pagination->Page,
            'perPage' => $pagination->ItemsPerPage,
            'filters' => null,
        ];
        $form = $this->createForm(QueryMangopayKycDocType::class, $queryConfig);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $queryConfig = $form->getData();
            $pagination->Page = $queryConfig['page'] ?? 1;
            $pagination->ItemsPerPage = $queryConfig['perPage'];
        }
        try {
            if ($user) {
                $results = $this->mangopayKycService->getAllUserKYCDocuments(
                    $user->getMangoPayUserId(),
                    $pagination,
                    $sorting,
                    $queryConfig['filters'],
                );
            } else {
                $results = $this->mangopayKycService->getAllKYCDocuments(
                    $pagination,
                    $sorting,
                    $queryConfig['filters'],
                );
            }
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving Mangopay kyc docs', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error retrieving Mangopay kyc docs ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving Mangopay kyc docs', [$e->getMessage()]);
            $this->addFlash(
                'error',
                'Error retrieving Mangopay kyc docs ' . $e->getMessage(),
            );
        }
        // Clamp pagination for rendering
        $pagination->Page = min($pagination->Page ?? 1, $pagination->TotalPages);
        return $this->render('admin/pages/kyc/mangopay/list_docs.html.twig', [
            'form' => $form->createView(),
            'results' => $results ?? [],
            'pagination' => $pagination,
        ]);
    }

    #[Route(
        '/check/document/{id}',
        name: 'admin_kyc_mangopay_check_document',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function mangopayCheckDocument(
        Request $request,
        #[MapEntity(id: 'id')] UserDocument $userDocument,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $kycReport = $this->mangopayKycService->submitDocument($userDocument);
                $this->addFlash(
                    'success',
                    'Mangopay KYC Document successfully submitted. You can view this under the Mangopay > Docs section.',
                );
                $this->logger->debug('Mangopay KYC document: ', [
                    'userId' => $userDocument->getUser()->getId(),
                    'userdocId' => $userDocument->getId(),
                ]);
            } catch (\Throwable $th) {
                $this->addFlash(
                    'error',
                    'Issue submitting KYC document: ' . $th->getMessage(),
                );
                $this->logger->warning(
                    'Issue submitting KYC document: ' . $th->getMessage(),
                );
                return $this->redirectToRoute('admin_user_dashboard_documents', [
                    'id' => $userDocument->getUser()->getId(),
                ]);
            }
            // return $this->render('admin/pages/kyc/reports/view.html.twig', [
            //     'kycReport' => $kycReport ?? [],
            // ]);
            return $this->redirectToRoute('admin_user_dashboard_kyc', [
                'id' => $userDocument->getUser()->getId(),
            ]);
        }
        try {
            if ($userDocument->getDocument()->getDocumentUrl()) {
                $fileSize = $this->documentService->fileSize(
                    $userDocument->getDocument()->getDocumentUrl(),
                    'private',
                );
            }
        } catch (\Throwable $th) {
            $this->addFlash('warning', 'Unable to get file size of document');
            $this->logger->warning('Unable to get file size of document');
        }
        return $this->render('admin/pages/kyc/mangopay/check_document.html.twig', [
            'form' => $form,
            'userDocument' => $userDocument,
            'fileSize' => $fileSize ?? null,
        ]);
    }
}
