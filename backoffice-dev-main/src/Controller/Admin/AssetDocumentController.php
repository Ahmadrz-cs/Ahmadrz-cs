<?php

namespace App\Controller\Admin;

use App\Entity\AssetDocuments;
use App\Entity\Document;
use App\Entity\User;
use App\Form\Type\AssetDocumentType;
use App\Form\Type\QueryAssetDocumentType;
use App\Repository\AssetDocumentRepository;
use App\Repository\AssetRepository;
use App\Service\DocumentService;
use App\Service\Manager\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/assetdocument')]
class AssetDocumentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private AssetDocumentRepository $assetDocumentRepository,
        private AssetRepository $assetRepository,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
    ) {}

    #[Route(path: '', name: 'admin_assetdocument_index')]
    #[Route(path: '/list', name: 'admin_asset_document_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List assets');
        $form = $this->createForm(QueryAssetDocumentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->assetDocumentRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/documents/asset/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param AssetDocuments $assetDocument
     */
    #[Route(path: '/add', name: 'admin_assetdocument_add')]
    #[Route(path: '/{id}/edit', name: 'admin_assetdocument_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editAction(
        Request $request,
        ?AssetDocuments $assetDocument = null,
    ): Response {
        if (!empty($assetDocument)) {
            $action = 'edit';
        } else {
            $action = 'add';
            $assetDocument = new AssetDocuments();
            $newDocument = new Document();
            $assetDocument->setDocument($newDocument);
            $assetId = $request->query->get('asset');
            if ($assetId) {
                $asset = $this->assetRepository->find($assetId);
                $assetDocument->setAsset($asset);
            }
        }

        $form = $this->createForm(AssetDocumentType::class, $assetDocument, [
            'action' => $action,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $documentUpload = $form->getData()->getDocument();

            if ($action == 'add') {
                $docPathPrefix = 'asset/' . $form->getData()->getAsset()->getId();
                try {
                    $newDocument = $this->documentManager->linkDocument(
                        $newDocument,
                        $documentUpload->getFile(),
                        'public',
                        $docPathPrefix,
                    );
                    /** @var User $user */
                    $user = $this->getUser();
                    $newDocument->setCreatedById($user->getId());
                    $assetDocument->setDocument($newDocument);
                } catch (\Exception $e) {
                    $this->logger->error(
                        'Unable to upload document to filestore: ' . $e->getMessage(),
                    );
                    $this->addFlash(
                        'error',
                        'Unable to upload document to filestore (S3). Please contact admin.',
                    );
                    return $this->redirect($this->generateUrl(
                        'admin_assetdocument_index',
                    ));
                }
            } else {
                //on an edit on the description and tag can be set
                $assetDocument->setDocument($documentUpload);
            }

            // save changes
            $em = $this->doctrine->getManager();
            $em->persist($assetDocument);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_assetdocument_index', [
                'id' => $assetDocument->getId(),
            ]));
        } else {
            return $this->render('admin/pages/documents/asset/edit.html.twig', [
                'assetdocument' => $assetDocument,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param AssetDocuments $document
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    #[Route(path: '/{id}/delete', name: 'admin_assetdocument_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAction(Request $request, AssetDocuments $document)
    {
        $assetId = $document->getAsset()->getId();
        if ($document === null) {
            return $this->redirectToRoute('admin_offeringdocument_index');
        }

        try {
            $this->documentService->delete(
                $document->getDocument()->getDocumentUrl(),
                $this->documentManager->getVisibilityForDocType('asset'),
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

        return $this->redirectToRoute('admin_assetdocument_index');
    }
}
