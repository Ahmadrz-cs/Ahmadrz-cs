<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service;

use AppBundle\Entity\UserCustomInfo as UserInfo;
use ClientBundle\Service\CrowdTekService;
use ClientBundle\Service\DocumentService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DocumentServiceTest extends KernelTestCase
{
    private DocumentService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(DocumentService::class);
    }

    public function testFindMostRecentDocument(): void
    {
        $documents = [];
        $documents[] = $this->createDocumentArray(
            56,
            'proof_of_address',
            new \DateTime('-45 minutes')
        );
        $documents[] = $this->createDocumentArray(
            78,
            'proof_of_id',
            new \DateTime('-47 minutes')
        );
        $documents[] = $this->createDocumentArray(
            99,
            'source_of_funds',
            new \DateTime('-24 minutes')
        );

        $actual = $this->service->findMostRecentDocument($documents);
        $this->assertEquals(99, $actual['id']);
    }

    public function testFindMostRecentDocumentWithTag(): void
    {
        $documents = [];
        $documents[] = $this->createDocumentArray(
            56,
            'proof_of_address',
            new \DateTime('-70 minutes')
        );
        $documents[] = $this->createDocumentArray(
            105,
            'proof_of_address',
            new \DateTime('-88 minutes')
        );
        $documents[] = $this->createDocumentArray(
            58,
            'proof_of_address',
            new \DateTime('-80 minutes')
        );
        $documents[] = $this->createDocumentArray(
            78,
            'proof_of_id',
            new \DateTime('-47 minutes')
        );
        $documents[] = $this->createDocumentArray(
            99,
            'source_of_funds',
            new \DateTime('-24 minutes')
        );

        $actual = $this->service->findMostRecentDocument($documents, 'proof_of_address');
        $this->assertEquals(56, $actual['id']);
    }

    public function testFindMostRecentDocumentEmpty(): void
    {
        $actual = $this->service->findMostRecentDocument([]);
        $this->assertEquals([], $actual);
    }

    private function createDocumentArray(int $id, string $tag, \DateTime $createdAt): array
    {
        return [
            'id' => $id,
            'tag' => $tag,
            'created_at' => $createdAt->format(\DateTime::ATOM),
            'updated_at' => $createdAt->format(\DateTime::ATOM),
            'file_name' => 'test_doc_' . bin2hex(random_bytes(8)),
            'file_description' => 'test_doc_description_' . bin2hex(random_bytes(8)),
            'file_type' => 'image/jpeg',
        ];
    }
}
