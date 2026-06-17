<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Entity\UserDocument;
use App\Form\Type\QueryUserDocumentType;
use App\Form\Type\UserDocumentType;
use App\Repository\UserDocumentRepository;
use App\Repository\UserRepository;
use App\Service\DocumentService;
use App\Service\Manager\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/userdocument')]
class UserDocumentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private UserDocumentRepository $userDocumentRepository,
        private UserRepository $userRepository,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
    ) {}

    #[Route(path: '', name: 'admin_userdocument_index')]
    #[Route(path: '/list', name: 'admin_user_document_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List users');
        $form = $this->createForm(QueryUserDocumentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->userDocumentRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/documents/user/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/add', name: 'admin_userdocument_add')]
    #[Route(path: '/{id}/edit', name: 'admin_userdocument_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editAction(
        Request $request,
        ?UserDocument $userDocument = null,
    ): Response {
        if (!empty($userDocument)) {
            $action = 'edit';
        } else {
            $action = 'add';
            $userDocument = new UserDocument();
            $newDocument = new Document();
            $userDocument->getDocument()->add($newDocument);
            $userId = $request->query->get('user');
            if ($userId) {
                $user = $this->userRepository->find($userId);
                $userDocument->setUser($user);
            }
        }

        $form = $this->createForm(UserDocumentType::class, $userDocument, [
            'action' => $action,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $allDocument = $form->getData()->getDocument();

            foreach ($allDocument as $singleDocument) {
                if ($action == 'add') {
                    $docPathPrefix = 'user/' . $form->getData()->getUser()->getId();
                    try {
                        $newDocument = $this->documentManager->linkDocument(
                            $newDocument,
                            $singleDocument->getFile(),
                            'private',
                            $docPathPrefix,
                        );
                        $newDocument->setCreatedById($this->getUser()->getId());
                        $userDocument->setDocument($newDocument);
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
                            'admin_userdocument_index',
                        ));
                    }
                } else {
                    //on an edit on the description and tag can be set
                    $userDocument->setDocument($allDocument);
                }
            }

            // save changes
            $em = $this->doctrine->getManager();
            $em->persist($userDocument);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_userdocument_index', [
                'id' => $userDocument->getId(),
            ]));
        }
        return $this->render('admin/pages/documents/user/edit.html.twig', [
            'userdocument' => $userDocument,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'admin_userdocument_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteAction(UserDocument $document): Response
    {
        if ($document === null) {
            return $this->redirectToRoute('admin_userdocument_index');
        }

        try {
            $this->documentService->delete(
                $document->getDocument()->getDocumentUrl(),
                $this->documentManager->getVisibilityForDocType('user'),
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

        return $this->redirectToRoute('admin_userdocument_index');
    }
}
