<?php

namespace App\Service;

use App\Entity\Enum\ReportStatus;
use App\Entity\Report;
use App\Entity\ReportSet;
use League\Csv\AbstractCsv;
use League\Csv\Reader;
use League\Csv\Writer;
use MangoPay\FilterReports;
use MangoPay\ReportRequest;
use MangoPay\ReportStatus as MangoPayReportStatus;
use MangoPay\ReportType;
use MangoPay\TransactionStatus;
use Psr\Log\LoggerInterface;

class MangopayReportService
{
    public const REPORT_COLUMNS_ALL = [
        'Id',
        'Tag',
        'CreationDate',
        'CreationDate:ISO',
        'ExecutionDate',
        'ExecutionDate:ISO',
        'AuthorId',
        'CreditedUserId',
        'DebitedFundsAmount',
        'DebitedFundsCurrency',
        'CreditedFundsAmount',
        'CreditedFundsCurrency',
        'FeesAmount',
        'FeesCurrency',
        'Status',
        'ResultCode',
        'ResultMessage',
        'Type',
        'Nature',
        'CreditedWalletId',
        'DebitedWalletId',
        'Alias',
        'BankAccountId',
        'BankWireRef',
        'CardId',
        'CardType',
        'Country',
        'Culture',
        'DeclaredDebitedFundsAmount',
        'DeclaredDebitedFundsCurrency',
        'DeclaredFeesAmount',
        'DeclaredFeesCurrency',
        'ExecutionType',
        'ExpirationDate',
        'ExpirationDate:ISO',
        'PaymentType',
        'PreauthorizationId',
        'WireReference',
    ];

    public const REPORT_COLUMNS_DEFAULT = [
        'Id',
        'Tag',
        'CreationDate',
        'CreationDate:ISO',
        'ExecutionDate',
        'ExecutionDate:ISO',
        'DebitedFundsAmount',
        'CreditedFundsAmount',
        'FeesAmount',
        'Status',
        'Type',
        'Nature',
        'CreditedWalletId',
        'DebitedWalletId',
    ];

    public const REPORT_COLUMNS_REQUIRED = [
        'Id',
        'DebitedFundsAmount',
        'CreditedFundsAmount',
        'ExecutionDate',
        'CreditedWalletId',
        'DebitedWalletId',
        'Status',
    ];

    public const REPORT_COLUMN_DIRECTIONAL_AMOUNT = 'Amount';
    public const REPORT_COLUMN_RUNNING_BALANCE = 'RunningBalance';

    public const PROGRESS_REPORTS_STORED = 10;
    public const PROGRESS_MERGED = 20;

    public function __construct(
        private LoggerInterface $logger,
        private MangopayReportDownloadService $mangopayReportDownloadService,
        private ReportStorageService $reportStorageService,
    ) {}

    /**
     * @return \DateTimeImmutable[]
     */
    public function chunkDateRange(
        \DateTimeInterface $dateStart,
        \DateTimeInterface $dateEnd,
        ?\DateInterval $interval = null,
        bool $zeroTimes = true,
    ): array {
        // Ensure we are working with \DateTimeImmutable
        $dateStart = \DateTimeImmutable::createFromInterface($dateStart);
        $dateEnd = \DateTimeImmutable::createFromInterface($dateEnd);
        if ($zeroTimes) {
            $dateStart = $dateStart->setTime(0, 0);
            $dateEnd = $dateEnd->setTime(0, 0);
        }
        if (is_null($interval)) {
            // Mangopay only supports up to 6 month chunks - set as default
            $interval = new \DateInterval('P6M');
        }

        // In PHP 8.2, can just use the INCLUDE_END_DATE option
        // $datePeriod = new \DatePeriod($dateStart, $interval, $dateEnd, \DatePeriod::INCLUDE_END_DATE);
        // return iterator_to_array($datePeriod);

        $datePeriod = new \DatePeriod($dateStart, $interval, $dateEnd);
        $dateCheckpoints = iterator_to_array($datePeriod);
        if (count($dateCheckpoints) > 0 && end($dateCheckpoints) < $dateEnd) {
            $dateCheckpoints[] = $dateEnd;
        }
        return $dateCheckpoints;
    }

    /**
     * @param \DateTimeInterface[] $dateCheckpoints
     * @throws \InvalidArgumentException
     */
    public function generateReportRequestsWithDateCheckpoints(
        ReportRequest $reportRequestTemplate,
        array $dateCheckpoints,
    ): array {
        $checkpointCount = count($dateCheckpoints);
        if ($checkpointCount < 2) {
            throw new \InvalidArgumentException(
                "Too few date checkpoints given. Minimum 2 required. {$checkpointCount} given.",
            );
        }
        // Ensure array is non-associative (i.e. a list, not a dict) so Mangopay can process properly
        $reportRequestTemplate->Columns = array_values($reportRequestTemplate->Columns);
        $numberOfReports = $checkpointCount - 1;
        $reportRequests = [];
        foreach (range(0, $numberOfReports - 1) as $dateCheckpointIndex) {
            $reportRequests[] = $this->createTemplatedReportRequest(
                $reportRequestTemplate,
                $dateCheckpoints[$dateCheckpointIndex],
                $dateCheckpoints[$dateCheckpointIndex + 1],
            );
        }
        return $reportRequests;
    }

    public function prepareMergeSafeTemplate(ReportRequest $reportRequestTemplate): ReportRequest
    {
        $reportRequestTemplate->Columns = array_unique(array_merge(
            self::REPORT_COLUMNS_REQUIRED,
            $reportRequestTemplate->Columns ?? [],
        ));
        return $reportRequestTemplate;
    }

    public function createReportRequest(?string $callbackUrl = null): ReportRequest
    {
        $reportFilters = new FilterReports();
        $reportFilters->Status = [TransactionStatus::Succeeded];
        $reportRequest = new ReportRequest();
        $reportRequest->Filters = $reportFilters;
        $reportRequest->Columns = MangopayReportService::REPORT_COLUMNS_DEFAULT;
        $reportRequest->ReportType = ReportType::Transactions;

        if (!is_null($callbackUrl)) {
            $reportRequest->CallbackURL = $callbackUrl;
        }

        return $reportRequest;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createReportRecord(ReportRequest $reportRequest): Report
    {
        $report = new Report();
        $report->setDescription($reportRequest->Tag);
        $report->setOrigin(Report::ORIGIN_MANGOPAY);
        $report->setResourceId($reportRequest->Filters->WalletId);
        $report->setReferenceId($reportRequest->Id);
        $report->setStatus(match ($reportRequest->Status) {
            MangoPayReportStatus::ReadyForDownload => ReportStatus::Available,
            MangoPayReportStatus::Pending => ReportStatus::Pending,
            default => throw new \InvalidArgumentException(
                'Report request must be pending or ready for download',
            ),
        });
        return $report;
    }

    public function createMergedReportRecord(
        ReportSet $reportSet,
        string $resourceId,
    ): Report {
        $report = new Report();
        $report->setDescription(
            $reportSet->getDescription() . ' - Merged and processed report',
        );
        $report->setOrigin(Report::ORIGIN_MERGED);
        $report->setResourceId($resourceId);
        $report->setReferenceId($reportSet->getId());
        $report->setStatus(ReportStatus::Pending);
        return $report;
    }

    /**
     * @return Report[]
     */
    public function getMergeableReports(
        ReportSet $reportSet,
        ?string $resourceId = null,
    ): array {
        $mergeableReports = [];
        foreach ($reportSet->getReports() as $report) {
            if (
                Report::ORIGIN_MANGOPAY == $report->getOrigin()
                && ReportStatus::Available == $report->getStatus()
            ) {
                // Skip iteration if filtering resourceId and doesn't match
                if (!is_null($resourceId) && $resourceId != $report->getResourceId()) {
                    continue;
                }
                $mergeableReports[] = $report;
            }
        }
        return $mergeableReports;
    }

    /**
     * @return Report[]
     */
    public function getMergedReports(ReportSet $reportSet): array
    {
        $mergedReports = [];
        foreach ($reportSet->getReports() as $report) {
            if (
                Report::ORIGIN_MERGED == $report->getOrigin()
                && ReportStatus::Available == $report->getStatus()
            ) {
                $mergedReports[] = $report;
            }
        }
        return $mergedReports;
    }

    /**
     * @param Report[] $reports
     */
    public function mergeAndProcessReports(
        Report $reportRecord,
        array $reports,
        bool $directionalAmount = false,
        bool $runningBalance = false,
        int $startingBalance = 0,
    ): Report {
        if (is_null($reportRecord->getId())) {
            throw new \InvalidArgumentException('Report record does not have an Id');
        }
        $walletId = $reports[0]->getResourceId();
        $mergedReportData = $this->mergeReportData($reports);

        $csvHeaders = array_shift($mergedReportData);
        if ($directionalAmount) {
            $csvHeaders[] = self::REPORT_COLUMN_DIRECTIONAL_AMOUNT;
            // Note that running balance cannot be computed without a directional amount
            if ($runningBalance) {
                $csvHeaders[] = self::REPORT_COLUMN_RUNNING_BALANCE;
            }
        }

        $csvMerged = Writer::createFromPath('php://temp', 'r+');
        $csvMerged->insertOne($csvHeaders);
        // $this->logger->debug('merged report row', $mergedReportData[0]);
        $csvMerged->insertAll($this->processReport(
            $mergedReportData,
            $directionalAmount ? $walletId : null,
            $runningBalance,
            $startingBalance,
        ));

        $reportRecord->setStatus(ReportStatus::Available);
        $this->reportStorageService->upload($reportRecord, $csvMerged->toString());
        return $reportRecord;
    }

    /**
     * @param Report[] $reports
     * @throws \RuntimeException if header missing required columns or do not match
     */
    public function mergeReportData(array $reports): array
    {
        $combinedRecords = [];
        $csvHeaders = [];
        foreach ($reports as $report) {
            $csvString = $this->reportStorageService->download($report);
            $csv = Reader::createFromString($csvString, 'r');
            $csv->setHeaderOffset(0);

            // Header/column validation
            $columnsMissing = array_diff(
                self::REPORT_COLUMNS_REQUIRED,
                $csv->getHeader(),
            );
            if (!empty($columnsMissing)) {
                throw new \RuntimeException(
                    'CSV headers missing required columns for processing: '
                        . join(', ', $columnsMissing),
                );
            }
            if (empty($csvHeaders)) {
                $csvHeaders = $csv->getHeader();
            } else {
                // $this->logger->debug('header1', $csvHeaders);
                if ($csvHeaders != $csv->getHeader()) {
                    $csv1HeaderDiff = array_diff($csvHeaders, $csv->getHeader());
                    $csv2HeaderDiff = array_diff($csv->getHeader(), $csvHeaders);
                    throw new \RuntimeException(
                        'CSV headers from different reports do not match'
                        . (
                            empty($csv1HeaderDiff)
                                ? ''
                                : '. Report A contains extra: '
                                . join(', ', $csv1HeaderDiff)
                        )
                        . (
                            empty($csv2HeaderDiff)
                                ? ''
                                : '. Report B contains extra: '
                                . join(', ', $csv2HeaderDiff)
                        ),
                    );
                }
            }
            // Append current report's records to the combined one
            $combinedRecords = [...$combinedRecords, ...$csv->getRecords()];
        }
        // Prepend the header before returning
        return [$csvHeaders, ...$combinedRecords];
    }

    public function storeReport(Report $report, ReportRequest $reportRequest): Report
    {
        if (empty($report->getUrl())) {
            $reportContents = $this->mangopayReportDownloadService->downloadFromUrlToString($reportRequest->DownloadURL);
            $this->reportStorageService->upload($report, $reportContents);
        }
        return $report;
    }

    // public function mergeCsvFromUrls(string ...$urls): AbstractCsv
    // {
    //     $reader = Reader::createFromPath('/path/to/your/csv/file.csv', 'r');
    // }

    /**
     * @throws \RuntimeException if header missing required columns or do not match
     */
    public function mergeCsvFromUrls(string $url1, string $url2): AbstractCsv
    {
        // Can't use createFromPath as fopen behind the scenes does not support seeking streams
        // file_get_contents will load the entire file as a string
        // This is memory inefficient but is seekable (as it is now "local")
        // We can change this in future if we use S3 to cache our reports
        // $csv1 = Reader::createFromPath($url1, 'r');
        // $csv2 = Reader::createFromPath($url2, 'r');

        // Get the first report and check it has the required headers for processing
        $csvString1 =
            $this->mangopayReportDownloadService->downloadFromUrlToString($url1);
        $csv1 = Reader::createFromString($csvString1, 'r');
        $csv1->setHeaderOffset(0);

        $columnsMissing = array_diff(self::REPORT_COLUMNS_REQUIRED, $csv1->getHeader());
        if (!empty($columnsMissing)) {
            throw new \RuntimeException(
                'CSV headers missing required columns for processing: '
                    . join(', ', $columnsMissing),
            );
        }

        // Get second report and check that the headers match, otherwise the CSVs cannot be merged
        $csvString2 =
            $this->mangopayReportDownloadService->downloadFromUrlToString($url2);
        $csv2 = Reader::createFromString($csvString2, 'r');
        $csv2->setHeaderOffset(0);

        if ($csv1->getHeader() != $csv2->getHeader()) {
            $csv1HeaderDiff = array_diff($csv1->getHeader(), $csv2->getHeader());
            $csv2HeaderDiff = array_diff($csv2->getHeader(), $csv1->getHeader());
            throw new \RuntimeException(
                'CSV headers from urls do not match'
                . (
                    empty($csv1HeaderDiff)
                        ? ''
                        : '. Report 1 contains extra: ' . join(', ', $csv1HeaderDiff)
                )
                . (
                    empty($csv2HeaderDiff)
                        ? ''
                        : '. Report 2 contains extra: ' . join(', ', $csv2HeaderDiff)
                ),
            );
        }

        // Prepare merged csv
        $csvMerged = Writer::createFromPath('php://temp', 'r+');
        $csvMerged->insertOne($csv1->getHeader());

        // Merge reports and process them
        $mergedData = [...$csv1->getRecords(), ...$csv2->getRecords()];
        $csvMerged->insertAll($this->processReport($mergedData));
        return $csvMerged;
    }

    public function processReport(
        array $report,
        ?string $walletId = null,
        bool $runningBalance = false,
        int $startingBalance = 0,
    ): array {
        // Sort by reverse chronological order
        usort($report, fn($a, $b) => $b['ExecutionDate'] <=> $a['ExecutionDate']);

        // Keep a record of Ids to eliminate duplicates
        $recordIds = [];
        $processedReport = [];
        $currentBalance = null;
        $changeFromPrevious = null;
        foreach ($report as $row) {
            // $this->logger->warning('', $row);
            if (!in_array($row['Id'], $recordIds)) {
                // Additional processing if enabled
                // Running balance requires a walletId to work
                // Will silently skip if walletId null and currentBalance is enabled
                if (!is_null($walletId)) {
                    $isCredit = true;
                    $amount = 0;
                    if (TransactionStatus::Succeeded == $row['Status']) {
                        if ($walletId == $row['DebitedWalletId']) {
                            $amount = $row['DebitedFundsAmount'];
                            $isCredit = false;
                        } elseif ($walletId == $row['CreditedWalletId']) {
                            $amount = $row['CreditedFundsAmount'];
                        }
                    }

                    $prefix = $isCredit ? '' : '-';
                    $row[self::REPORT_COLUMN_DIRECTIONAL_AMOUNT] =
                        $prefix . $this->applyCurrencyDivisor($amount);

                    if ($runningBalance) {
                        if (is_null($currentBalance)) {
                            $currentBalance = $startingBalance;
                        } else {
                            $currentBalance += $changeFromPrevious;
                        }
                        // Note that since we are in reverse chronological order
                        // To calculate the the running balance after the current row's amount
                        // Need to apply the "previous" (newer) transaction amount, in the opposite direction, e.g.
                        // Row, Amount, Running Balance
                        // 1, 1020,     0
                        // 2, -598, -1020 // For Row 1 to be at 0 AFTER an additon of 1020, we must have started at -1020
                        // 3,   90,  -422 // For Row 2 to be at -1020 AFTER a deducation of 598, we must have started at -422
                        $changeFromPrevious = $isCredit ? 0 - $amount : $amount;
                        $row[self::REPORT_COLUMN_RUNNING_BALANCE] =
                            $this->applyCurrencyDivisor($currentBalance);
                    }
                }

                $processedReport[] = $row;
                $recordIds[] = $row['Id'];
            }
        }
        return $processedReport;
    }

    public function applyCurrencyDivisor(string $amount, int $divisor = 100): string
    {
        return number_format($amount / $divisor, 2, '.', '');
    }

    private function createTemplatedReportRequest(
        ReportRequest $reportRequestTemplate,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): ReportRequest {
        $reportFilters = clone $reportRequestTemplate->Filters;
        $reportFilters->AfterDate = $start->getTimestamp();
        $reportFilters->BeforeDate = $end->getTimestamp();
        $newReportRequest = clone $reportRequestTemplate;
        $newReportRequest->Filters = $reportFilters;
        return $newReportRequest;
    }
}
