<?php

namespace App\Service;

use App\Entity\Report;
use Psr\Log\LoggerInterface;

/**
 * Handles storage of App\Entity\Report files
 */
class ReportStorageService
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentService $documentService,
    ) {}

    public function upload(
        Report $report,
        string $filestring,
        ?string $fileName = null,
    ): void {
        $this->logger->debug("Uploading report: {$report->getId()}");
        $report->setUrl($this->createReportUrl($report, $fileName));
        $this->documentService->put($report->getUrl(), $filestring, 'private');
    }

    public function download(Report $report): string
    {
        $this->logger->info("Downloading report: {$report->getId()}");
        $response = $this->documentService->read($report->getUrl(), 'private');
        return $response;
    }

    public function delete(Report $report): void
    {
        $this->logger->debug("Deleting report: {$report->getId()}");
        $this->documentService->delete($report->getUrl(), 'private');
    }

    public function isStored(Report $report): bool
    {
        $response = $this->documentService->has($report->getUrl(), 'private');
        return $response;
    }

    public function createReportUrl(Report $report, ?string $fileName = null): string
    {
        // Bit superfluous at the moment
        // But possible in future to support pdf reports
        $expectedFileType = match ($report->getOrigin()) {
            Report::ORIGIN_MANGOPAY, Report::ORIGIN_MERGED => 'csv',
            default => 'csv',
        };
        $fileName =
            $fileName ?? "{$report->getResourceId()}_{$report->getReferenceId()}";
        return "reports/{$report->getId()}/{$fileName}.{$expectedFileType}";
    }
}
