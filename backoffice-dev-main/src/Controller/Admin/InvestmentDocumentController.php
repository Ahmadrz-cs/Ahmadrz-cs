<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Entity\InvestmentDocuments;
use App\Form\Type\InvestmentCertificateType;
use App\Form\Type\InvestmentDocumentType;
use App\Form\Type\QueryInvestmentAssetType;
use App\Form\Type\QueryInvestmentDocumentType;
use App\Repository\InvestmentDocumentRepository;
use App\Repository\InvestmentRepository;
use App\Service\DocumentService;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\InvestmentDocumentManagerV2;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/investmentdocument')]
class InvestmentDocumentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private InvestmentDocumentManagerV2 $investmentDocumentManager,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
        private InvestmentRepository $investmentRepository,
        private InvestmentDocumentRepository $investmentDocumentRepository,
    ) {}

    #[Route(path: '', name: 'admin_investmentdocument_index')]
    #[Route(path: '/list', name: 'admin_investment_document_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List investments');
        $form = $this->createForm(QueryInvestmentDocumentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->investmentDocumentRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/documents/investment/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/certificate-uploader', name: 'admin_share_certificate_upload')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function certificateUploader(Request $request): Response
    {
        $this->logger->info('Share certificate uploader');
        $formFilters = $this->createForm(QueryInvestmentAssetType::class, [
            'page' => 1,
        ]);
        $formFilters->handleRequest($request);

        $investments = $this->investmentRepository->findByWithAssociations(array_merge([
            'hasDocuments' => 0,
            'lifecycleStatus' => 'settled',
        ], $formFilters->getData() ?? []), ['id' => 'DESC'], $request->query->get('perPage') ?? 10, $request->query->get('page') ?? 1);
        $investmentDocument = new InvestmentDocuments();
        $investmentDocument->setDocument(new Document());
        $options = ['investments' => $investments];
        $form = $this->createForm(
            InvestmentCertificateType::class,
            $investmentDocument,
            $options,
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $investmentDocument = $this->investmentDocumentManager->createShareCertificate(
                    $investmentDocument,
                    $this->getUser(),
                );
                $this->doctrine->getManager()->persist($investmentDocument);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Succesfully uploaded document for '
                        . ' #'
                        . $investmentDocument->getInvestment()->getId()
                        . ' // '
                        . $investmentDocument
                            ->getInvestment()
                            ->getOffering()
                            ->getAsset()
                            ->getName()
                        . ' // '
                        . $investmentDocument
                            ->getInvestment()
                            ->getUser()
                            ->getUserIdentifier(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Unable to save document: ' . $e->getMessage());
                $this->addFlash(
                    'error',
                    'Unable to save document. Please contact admin.',
                );
                return $this->redirect($this->generateUrl(
                    'admin_share_certificate_upload',
                ));
            }
            return $this->redirectToRoute('admin_share_certificate_upload');
        }
        return $this->render('admin/pages/investments/certificate_manager.html.twig', [
            'objects' => $investments,
            'form' => $form->createView(),
            'formFilters' => $formFilters->createView(),
        ]);
    }

    #[Route(path: '/add', name: 'admin_investmentdocument_add')]
    #[Route(path: '/{id}/edit', name: 'admin_investmentdocument_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editAction(
        Request $request,
        ?InvestmentDocuments $investmentDocument = null,
    ): Response {
        if (!empty($investmentDocument)) {
            if (!$this->isGranted('CAN_UPDATE_DOC', $this->getUser())) {
                return $this->redirectToRoute('admin_investmentdocument_index');
            }
            $action = 'edit';
        } else {
            if (!$this->isGranted('CAN_CREATE_DOC', $this->getUser())) {
                return $this->redirectToRoute('admin_investmentdocument_index');
            }
            $action = 'add';
            $investmentDocument = new InvestmentDocuments();
            $newDocument = new Document();
            $investmentDocument->getDocument()->add($newDocument);
        }

        $form = $this->createForm(InvestmentDocumentType::class, $investmentDocument, [
            'action' => $action,
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

    #[Route(path: '/{id}/delete', name: 'admin_investmentdocument_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAction(
        Request $request,
        InvestmentDocuments $document,
    ): Response {
        if ($document === null) {
            return $this->redirectToRoute('admin_investmentdocument_index');
        }

        try {
            $this->documentService->delete(
                $document->getDocument()->getDocumentUrl(),
                $this->documentManager->getVisibilityForDocType('investment'),
            );
        } catch (\Throwable $e) {
            $this->addFlash(
                'warning',
                'Document may not have been delete from file store (e.g. S3) due to error: '
                    . $e->getMessage(),
            );
        }

        $em = $this->doctrine->getManager();
        $em->remove($document);
        $em->flush();

        return $this->redirectToRoute('admin_investmentdocument_index');
    }
}
