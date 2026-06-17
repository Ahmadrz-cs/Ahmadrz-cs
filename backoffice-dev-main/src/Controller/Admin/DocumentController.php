<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Service\DocumentService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/document')]
class DocumentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentService $documentService,
    ) {}

    #[Route(path: '/{id}/download', name: 'admin_document_download')]
    public function downloadAction(Request $request, Document $document): Response
    {
        if (empty($document->getDocumentUrl())) {
            $this->addFlash(
                'error',
                'Unable to retrieve document without a url. Either replace the URL or delete the document and upload a new one.',
            );
            return $this->redirectToRoute('admin_dashboard_index');
        }
        try {
            if ($request->query->get('type') == 'private') {
                // $file = stream_get_contents($document->getDocumentContent());
                $file = $this->documentService->read(
                    $document->getDocumentUrl(),
                    'private',
                );
            } else {
                $file = $this->documentService->read(
                    $document->getDocumentUrl(),
                    'public',
                );
            }
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                "Unabled to retrieve document from file store. Please ensure file is synced. [{$e->getMessage()}]",
            );
            $this->logger->error(
                'Unable to retrieve document from file store: ' . $e->getMessage(),
            );
            return $this->redirectToRoute('admin_administration_doc_dashboard');
        }

        $mode = (bool) $request->query->get('preview', false) ? 'inline' : 'attachment';
        $response = new Response($file, 200, [
            'Content-Type' => $document->getType(),
            'Content-Disposition' =>
                $mode . '; filename="' . $document->getFilename() . '"',
        ]);

        return $response;
    }
}
