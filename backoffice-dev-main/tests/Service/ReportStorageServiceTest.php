<?php

namespace App\Tests\Service;

use App\Entity\Report;
use App\Service\DocumentService;
use App\Service\ReportStorageService;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReportStorageServiceTest extends KernelTestCase
{
    private ReportStorageService $service;
    private DocumentService|MockObject $documentServiceMock;

    public function setUp(): void
    {
        self::bootKernel();
        $this->documentServiceMock = $this->createMock(DocumentService::class);
        static::getContainer()->set(DocumentService::class, $this->documentServiceMock);
        $this->service = static::getContainer()->get(ReportStorageService::class);
    }

    public function testCreateReportUrl(): void
    {
        $resourceId = 'res' . bin2hex(random_bytes(8));
        $referenceId = 'ref' . bin2hex(random_bytes(8));
        $report = EntityIdTestUtil::setEntityId(new Report(), 516);
        $report->setResourceId($resourceId);
        $report->setReferenceId($referenceId);

        $expected = "reports/516/{$resourceId}_{$referenceId}.csv";
        $actual = $this->service->createReportUrl($report);
        $this->assertSame($expected, $actual);

        $expected = 'reports/516/customised_filename.csv';
        $actual = $this->service->createReportUrl($report, 'customised_filename');
        $this->assertSame($expected, $actual);
    }

    public function testDocumentServiceWrappers(): void
    {
        $report = EntityIdTestUtil::setEntityId(new Report(), 516);
        $generatedUrl = 'reports/516/customised_filename.csv';

        // Check that the wrapper methods are using the private filesystem
        $this->documentServiceMock
            ->expects(self::once())
            ->method('put')
            ->with($generatedUrl, '', 'private');
        $this->documentServiceMock
            ->expects(self::once())
            ->method('read')
            ->with($generatedUrl, 'private');
        $this->documentServiceMock
            ->expects(self::once())
            ->method('has')
            ->with($generatedUrl, 'private');
        $this->documentServiceMock
            ->expects(self::once())
            ->method('delete')
            ->with($generatedUrl, 'private');

        $this->service->upload($report, '', 'customised_filename');
        $this->service->download($report, '', 'customised_filename');
        $this->service->isStored($report, '', 'customised_filename');
        $this->service->delete($report, '', 'customised_filename');
    }
}
