<?php

namespace AppBundle\Controller;

use ClientBundle\Service\DocumentService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentService $documentService,
    ) {
    }

    /**
     * @Route("/document-download/{id}", name="document_download")
     */
    public function downloadAction($id): Response
    {
        $this->logger->info("==================IN downloadAction=====================");

        $api2 = $this->documentService->getDocument($id);

        try {
            if ($api2['outcome'] == 'success') {
                // CT LOGGING CHANGE.
                $this->logger->info('Got Document ' . $id);

                $fileName = $api2['data']['document']['file_name'];
                // $url = $api2['data']['document']['url'];

                if (empty($fileName)) {
                    // CT LOGGING CHANGE.
                    $this->logger->info('Cannot get the details of the document ' . $id);
                    throw new \InvalidArgumentException('fileName cannot empty.');
                }

                // CT CHANGE  - base 64 decode the content.
                // $this->logger->info('Convert to base64 ' . $id);
                $doc_data = base64_decode($api2['data']['document']['document_content']);


                $response = new Response();
                $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
                $response->headers->set('Content-Disposition', $disposition);

                $file_extension = strtolower(substr(strrchr($fileName, "."), 1));

                switch ($file_extension) {
                    case "png":
                        $contentType = "image/png";
                        break;
                    case "jpeg":
                        $contentType = "image/jpeg";
                        break;
                    case "jpg":
                        $contentType = "image/jpeg";
                        break;
                    case "pdf":
                        $contentType = "application/pdf";
                        break;
                    default:
                }

                $response->headers->set('Content-Type', $contentType);
                $response->setPublic();
                $response->setMaxAge(63072000);
                $response->setSharedMaxAge(63072000);

                // CT CHANGE - set the content to be doc data.
                $response->setContent($doc_data);

                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->error("Document retrieval error: " . $e->getMessage());
            $this->logger->error("Document not found: " . $id);
            throw $this->createNotFoundException('Document not found.');
        }
    }

    /**
     * @Route("/document-preview/{id}/preview.pdf", name="document_preview")
     *
     * @param $id
     * @return bool
     */
    public function previewAction($id)
    {
        $this->logger->info("==================IN previewAction=====================");


        $response = $this->documentService->getDocument($id);
        if ($response['outcome'] == 'success') {
            $fileName = $response['data']['document']['file_name'];
            // $url = $response['data']['document']['url'];


            // CT CHANGE  - base 64 decode the content.
            $this->logger->info('Convert to base64 ' . $id);
            $doc_data = base64_decode($response['data']['document']['document_content']);


            $response = new Response();
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->setPublic();
            $response->setMaxAge(63072000);
            $response->setSharedMaxAge(63072000);


            // CT CHANGE - set the content to be doc data.
            $response->setContent($doc_data);

            return $response;
        }
        return false;
    }
}
