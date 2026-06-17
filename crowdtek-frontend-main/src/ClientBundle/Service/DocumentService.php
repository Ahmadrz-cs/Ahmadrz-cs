<?php

namespace ClientBundle\Service;

use Psr\Log\LoggerInterface;

class DocumentService
{
    public function __construct(
        private LoggerInterface $logger,
        private CrowdTekClient $crowdTekClient,
        private string $network,
    ) {
        // $this->network = $this->requestStack->getSession()->get('cv_network')
        //     ? $this->requestStack->getSession()->get('cv_network')
        //     : $this->container->getParameter('cv_network');
    }

    /**
     * Get document by id
     * @param $network
     * @param $document_id
     * @return mixed
     */
    public function getDocument($document_id)
    {
        $this->logger->info("==================IN getDocument=====================");

        $uri = 'v1/' . $this->network . '/documents/' . $document_id;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    /**
     * Create a new document.
     *
     */
    public function create($params)
    {
        $this->logger->info("==================IN create=====================");

        $uri = 'v1/' . $this->network . '/self/documents';
        $options = [
            'json' => $params
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    /**
     * Update a new document.
     *
     */
    public function update($params, $id)
    {
        $this->logger->info("==================IN update=====================");

        $uri = 'v1/' . $this->network . '/documents/' . $id;
        $options = [
            'json' => $params
        ];

        return $this->crowdTekClient->sendRequest('PATCH', $uri, $options);
    }

    /**
     * Delete a document.
     *
     */
    public function delete($id)
    {
        $this->logger->info("==================IN delete=====================");

        $uri = 'v1/' . $this->network . '/documents/' . $id;

        return $this->crowdTekClient->sendRequest('DELETE', $uri);
    }

    /**
     * @deprecated use create() incread
     */
    public function addSelfDocument($network, $documentJson)
    {
        $uri = 'v1/' . $network . '/self/documents';
        $options = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $documentJson
        ];
        // Since $documentJson is already in JSON format, manually set it with body option instead of json option
        // the json option will apply json_encode to whatever the value is, which we don't want if it's already JSON!

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function findMostRecentDocument(array $documents, ?string $tag = null): array
    {
        if ($tag) {
            // $this->logger->debug("Filtering by tag");
            $documents = array_filter(
                $documents,
                fn(array $doc): bool => array_key_exists('tag', $doc) && $doc['tag'] == 'proof_of_address'
            );
        }

        // $this->logger->debug("Filtered docs", $documents);
        usort(
            $documents,
            fn(array $doc1, array $doc2): int => strtotime($doc1['created_at']) - strtotime($doc2['created_at'])
        );
        // $this->logger->debug("Sorted docs", $documents);
        if (empty($documents)) {
            return [];
        }
        return end($documents);
    }
}
