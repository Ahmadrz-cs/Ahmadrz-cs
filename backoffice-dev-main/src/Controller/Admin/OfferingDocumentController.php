<?php

namespace App\Controller\Admin;

use App\Entity\AssetDocuments;
use App\Entity\Document;
use App\Entity\OfferingDocuments;
use App\Entity\User;
use App\Form\Type\OfferingDocumentType;
use App\Form\Type\QueryOfferingDocumentType;
use App\Repository\AssetDocumentRepository;
use App\Repository\OfferingDocumentRepository;
use App\Repository\OfferingRepository;
use App\Service\DocumentService;
use App\Service\Manager\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/offeringdocument')]
class OfferingDocumentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private AssetDocumentRepository $assetDocumentRepository,
        private OfferingDocumentRepository $offeringDocumentRepository,
        private OfferingRepository $offeringRepository,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
    ) {}

    #[Route(path: '', name: 'admin_offeringdocument_index')]
    #[Route(path: '/list', name: 'admin_offering_document_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List offerings');
        $form = $this->createForm(QueryOfferingDocumentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->offeringDocumentRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        if ($request->query->get('compare', false)) {
            $docIds = [];
            /**
             * @var OfferingDocuments[] $results
             */
            foreach ($results as $offeringDoc) {
                $docIds[] = $offeringDoc->getDocument()->getId();
            }
            /**
             * @var AssetDocuments[] $matchingAssetDocs
             */
            $matchingAssetDocs = $this->assetDocumentRepository->buildQueryWithAssociations([
                'documentId' => $docIds,
            ])->getResult();
            $compare = [];
            foreach ($matchingAssetDocs as $assetDoc) {
                $compare[$assetDoc->getDocument()->getId()] = $assetDoc;
            }
        }
        return $this->render('admin/pages/documents/offering/list.html.twig', [
            'objects' => $results,
            'compare' => $compare ?? [],
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/add', name: 'admin_offeringdocument_add')]
    #[Route(path: '/{id}/edit', name: 'admin_offeringdocument_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editAction(
        Request $request,
        ?OfferingDocuments $offeringDocument = null,
    ): Response {
        if (!empty($offeringDocument)) {
            $action = 'edit';
        } else {
            $action = 'add';
            $offeringDocument = new OfferingDocuments();
            $newDocument = new Document();
            $offeringDocument->getDocument()->add($newDocument);
            $offeringId = $request->query->get('offering');
            if ($offeringId) {
                $offering = $this->offeringRepository->find($offeringId);
                $offeringDocument->setOffering($offering);
            }
        }

        $form = $this->createForm(OfferingDocumentType::class, $offeringDocument, [
            'action' => $action,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $allDocument = $form->getData()->getDocument();

            foreach ($allDocument as $singleDocument) {
                if ($action == 'add') {
                    $docPathPrefix =
                        'offering/' . $form->getData()->getOffering()->getId();
                    try {
                        $newDocument = $this->documentManager->linkDocument(
                            $newDocument,
                            $singleDocument->getFile(),
                            'public',
                            $docPathPrefix,
                        );
                        /** @var User $user */
                        $user = $this->getUser();
                        $newDocument->setCreatedById($user->getId());
                        $offeringDocument->setDocument($newDocument);
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
                            'admin_offeringdocument_index',
                        ));
                    }
                } else {
                    //on an edit on the description and tag can be set
                    $offeringDocument->setDocument($allDocument);
                }
            }

            // save changes
            $em = $this->doctrine->getManager();
            $em->persist($offeringDocument);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_offeringdocument_index', [
                'id' => $offeringDocument->getId(),
            ]));
        }
        return $this->render('admin/pages/documents/offering/edit.html.twig', [
            'offeringdocument' => $offeringDocument,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'admin_offeringdocument_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAction(
        Request $request,
        OfferingDocuments $document,
    ): Response {
        $assetId = $document->getOffering()->getAsset()->getId();
        if ($document === null) {
            return $this->redirectToRoute('admin_offeringdocument_index');
        }

        try {
            $this->documentService->delete(
                $document->getDocument()->getDocumentUrl(),
                $this->documentManager->getVisibilityForDocType('offering'),
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

        if (in_array(
            $request->query->get('redirectRoute'),
            ProductController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
            return $this->redirectToRoute(
                $redirectToRoute,
                ['id' => $assetId],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->redirectToRoute('admin_offeringdocument_index');
    }
}
