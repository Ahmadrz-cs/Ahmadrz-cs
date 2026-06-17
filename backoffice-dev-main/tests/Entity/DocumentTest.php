<?php

namespace App\Tests\Entity;

use App\Entity\Document;
use App\Entity\User;

class DocumentTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $document = new Document();

        $this->assertNotNull($document);

        $this->assertNull($document->getId());
    }

    public function testName(): void
    {
        $document = new Document();

        $testName = 'test';

        $this->assertNull($document->getName());

        $document->setName($testName);

        $this->assertEquals($testName, $document->getName());
    }

    public function testDescription(): void
    {
        $document = new Document();

        $testDescription = 'test';

        $this->assertNull($document->getDescription());

        $document->setDescription($testDescription);

        $this->assertEquals($testDescription, $document->getDescription());
    }

    public function testType(): void
    {
        $document = new Document();

        $testType = 'test';

        $this->assertNull($document->getType());

        $document->setType($testType);

        $this->assertEquals($testType, $document->getType());
    }

    public function testAlias(): void
    {
        $document = new Document();

        $testAlias = 'test';

        $this->assertNull($document->getAlias());

        $document->setAlias($testAlias);

        $this->assertEquals($testAlias, $document->getAlias());
    }

    public function testTag(): void
    {
        $document = new Document();

        $testTag = 'test';

        $this->assertNull($document->getTag());

        $document->setTag($testTag);

        $this->assertEquals($testTag, $document->getTag());
    }

    public function testCreatedOn(): void
    {
        $document = new Document();

        $testDatetime = new \DateTime();

        $this->assertNull($document->getCreatedAt());

        $document->setCreatedAt($testDatetime);

        $this->assertEquals($testDatetime, $document->getCreatedAt());

        $this->assertInstanceOf('DateTime', $document->getCreatedAt());
    }

    public function testCategory(): void
    {
        $document = new Document();

        $testCategory = 'test category';

        $this->assertNull($document->getCategory());

        $document->setCategory($testCategory);

        $this->assertEquals($testCategory, $document->getCategory());
    }

    public function testCreatedBy(): void
    {
        $document = new Document();

        $testUser = new User();

        $this->assertNull($document->getCreatedBy());

        $document->setCreatedBy($testUser);

        $this->assertEquals($testUser, $document->getCreatedBy());
    }

    public function testDocumentURL(): void
    {
        $document = new Document();

        $document_url = 'bucket/user/1234/filenane.jpg';
        $document->setDocumentUrl($document_url);

        $this->assertEquals($document_url, $document->getDocumentUrl());
    }
}
