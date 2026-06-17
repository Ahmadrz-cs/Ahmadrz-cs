<?php

namespace App\Tests\Service;

use App\Entity\Enum\ReportStatus;
use App\Entity\Report;
use App\Entity\ReportSet;
use App\Service\MangopayReportDownloadService;
use App\Service\MangopayReportService;
use App\Service\ReportStorageService;
use App\Test\Util\EntityIdTestUtil;
use League\Csv\Reader;
use MangoPay\FilterReports;
use MangoPay\ReportRequest;
use MangoPay\ReportStatus as MangoPayReportStatus;
use MangoPay\ReportType;
use MangoPay\TransactionStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MangopayReportServiceTest extends KernelTestCase
{
    private const REPORT_DATA_1 = [
        [
            'Id' => '68594213',
            'ExecutionDate' => '1686000563',
            'DebitedFundsAmount' => '7782',
            'CreditedFundsAmount' => '7782',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68594222',
            'ExecutionDate' => '1685705563',
            'DebitedFundsAmount' => '2',
            'CreditedFundsAmount' => '2',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68594145',
            'ExecutionDate' => '1685702563',
            'DebitedFundsAmount' => '168',
            'CreditedFundsAmount' => '168',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68595561',
            'ExecutionDate' => '1685975306',
            'DebitedFundsAmount' => '10000',
            'CreditedFundsAmount' => '10000',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68592451',
            'ExecutionDate' => '1686102306',
            'DebitedFundsAmount' => '158028',
            'CreditedFundsAmount' => '157028',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            // This is a duplicate to test duplicate removal
            'Id' => '68592451',
            'ExecutionDate' => '1686102306',
            'DebitedFundsAmount' => '158028',
            'CreditedFundsAmount' => '157028',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
    ];

    private const REPORT_DATA_2 = [
        [
            // This is a duplicate from REPORT_DATA_1 to test duplicate removal
            'Id' => '68594213',
            'ExecutionDate' => '1686000563',
            'DebitedFundsAmount' => '7782',
            'CreditedFundsAmount' => '7782',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68594256',
            'ExecutionDate' => '1684500563',
            'DebitedFundsAmount' => '8694',
            'CreditedFundsAmount' => '8694',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68594221',
            'ExecutionDate' => '1686105243',
            'DebitedFundsAmount' => '478025',
            'CreditedFundsAmount' => '474025',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68595245',
            'ExecutionDate' => '1685798563',
            'DebitedFundsAmount' => '4820',
            'CreditedFundsAmount' => '4820',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            // This is a duplicate from REPORT_DATA_1 to test duplicate removal
            'Id' => '68592451',
            'ExecutionDate' => '1686102306',
            'DebitedFundsAmount' => '158028',
            'CreditedFundsAmount' => '157028',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68675561',
            'ExecutionDate' => '1685955206',
            'DebitedFundsAmount' => '15',
            'CreditedFundsAmount' => '15',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
        [
            'Id' => '68544458',
            'ExecutionDate' => '1686142306',
            'DebitedFundsAmount' => '26199',
            'CreditedFundsAmount' => '26199',
            'CreditedWalletId' => 'cw10000001',
            'DebitedWalletId' => 'dw10000001',
            'Status' => TransactionStatus::Succeeded,
        ],
    ];

    private MangopayReportService $service;
    private MangopayReportDownloadService|MockObject $mangopayReportDownloadServiceMock;
    private ReportStorageService|MockObject $reportStorageService;

    public function setUp(): void
    {
        self::bootKernel();
        $this->mangopayReportDownloadServiceMock = $this->createMock(MangopayReportDownloadService::class);
        $this->reportStorageService = $this->createMock(ReportStorageService::class);
        static::getContainer()->set(
            MangopayReportDownloadService::class,
            $this->mangopayReportDownloadServiceMock,
        );
        static::getContainer()->set(
            ReportStorageService::class,
            $this->reportStorageService,
        );
        $this->service = static::getContainer()->get(MangopayReportService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('chunkDateRangeProvider')]
    public function testChunkDateRange(
        array $expected,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?\DateInterval $interval = null,
        bool $zeroTime = true,
    ): void {
        $actual = $this->service->chunkDateRange($start, $end, $interval, $zeroTime);
        $actualAsString = array_map(fn(\DateTimeInterface $dt): string => $dt->format(
            'Y-m-d H:i:s',
        ), $actual);
        $this->assertSame($expected, $actualAsString);
        $this->assertContainsOnlyInstancesOf(\DateTimeImmutable::class, $actual);
    }

    public static function chunkDateRangeProvider(): \Generator
    {
        yield 'Default' => [
            [
                '2019-05-12 00:00:00',
                '2019-11-12 00:00:00',
                '2020-05-12 00:00:00',
                '2020-11-12 00:00:00',
                '2021-05-12 00:00:00',
                '2021-11-12 00:00:00',
                '2022-05-12 00:00:00',
                '2022-09-27 00:00:00',
            ],
            new \DateTime('2019-05-12 12:48:11'),
            new \DateTime('2022-09-27 21:21:21'),
            null,
            true,
        ];
        yield 'Different chunk size' => [
            [
                '2019-05-12 00:00:00',
                '2020-04-12 00:00:00',
                '2021-03-12 00:00:00',
                '2022-02-12 00:00:00',
                '2022-09-27 00:00:00',
            ],
            new \DateTimeImmutable('2019-05-12 12:48:11'),
            new \DateTimeImmutable('2022-09-27 21:21:21'),
            new \DateInterval('P11M'),
            true,
        ];
        yield 'No time zeroing' => [
            [
                '2019-05-12 12:48:11',
                '2020-04-12 12:48:11',
                '2021-03-12 12:48:11',
                '2022-02-12 12:48:11',
                '2022-09-27 21:21:21',
            ],
            new \DateTimeImmutable('2019-05-12 12:48:11'),
            new \DateTimeImmutable('2022-09-27 21:21:21'),
            new \DateInterval('P11M'),
            false,
        ];
        yield 'End greater than start' => [
            [],
            new \DateTimeImmutable('2019-05-12'),
            new \DateTimeImmutable('2012-09-27'),
            null,
            true,
        ];
    }

    public function testGenerateReportRequestsWithDateCheckpoints(): void
    {
        $dateStart = new \DateTime('-14 months');
        $dateEnd = new \DateTime('-5 months');
        $dateCheckpoints = $this->service->chunkDateRange($dateStart, $dateEnd);
        $template = $this->service->createReportRequest(bin2hex(random_bytes(8)));
        $template->Filters->WalletId = bin2hex(random_bytes(8));
        $template->Columns = MangopayReportService::REPORT_COLUMNS_REQUIRED;

        $actual = $this->service->generateReportRequestsWithDateCheckpoints(
            $template,
            $dateCheckpoints,
        );
        foreach ($actual as $index => $reportRequest) {
            $this->assertEquals($template->Columns, $reportRequest->Columns);
            $this->assertEquals($template->ReportType, $reportRequest->ReportType);
            $this->assertEquals($template->Status, $reportRequest->Status);
            $this->assertEquals($template->CallbackURL, $reportRequest->CallbackURL);
            $this->assertEquals(
                $template->Filters->WalletId,
                $reportRequest->Filters->WalletId,
            );
            $this->assertEquals(
                $dateCheckpoints[$index]->getTimestamp(),
                $reportRequest->Filters->AfterDate,
            );
            $this->assertEquals(
                $dateCheckpoints[$index + 1]->getTimestamp(),
                $reportRequest->Filters->BeforeDate,
            );
            $this->assertEmpty($reportRequest->Id);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Too few date checkpoints given. Minimum 2 required. 1 given.',
        );
        $dateCheckpointsSingle = array_slice($dateCheckpoints, 0, 1);
        $this->service->generateReportRequestsWithDateCheckpoints(
            $template,
            $dateCheckpointsSingle,
        );
    }

    public static function mergeSafeTemplateProvider(): \Generator
    {
        yield 'Empty' => [new ReportRequest()];

        $minimum = new ReportRequest();
        $minimum->Columns = MangopayReportService::REPORT_COLUMNS_REQUIRED;
        yield 'Minimum' => [$minimum];

        $default = new ReportRequest();
        $default->Columns = MangopayReportService::REPORT_COLUMNS_DEFAULT;
        yield 'Default' => [$default];

        $all = new ReportRequest();
        $all->Columns = MangopayReportService::REPORT_COLUMNS_ALL;
        yield 'All' => [$default];

        $missingSome = new ReportRequest();
        $missingSome->Columns = array_diff(MangopayReportService::REPORT_COLUMNS_DEFAULT, [
            'Id',
            'ExecutionDate',
        ]);
        yield 'Missing Id and ExecutionDate' => [$missingSome];

        $onlyId = new ReportRequest();
        $onlyId->Columns = ['Id'];
        yield 'Only Id' => [$onlyId];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mergeSafeTemplateProvider')]
    public function testPrepareMergeSafeTemplate(ReportRequest $template): void
    {
        $actual = $this->service->prepareMergeSafeTemplate($template);
        $this->assertEmpty(array_diff(
            MangopayReportService::REPORT_COLUMNS_REQUIRED,
            $actual->Columns,
        ));
    }

    public function testCreateReportRequest(): void
    {
        $actual = $this->service->createReportRequest();
        $this->assertEquals([TransactionStatus::Succeeded], $actual->Filters->Status);
        $this->assertEquals(
            MangopayReportService::REPORT_COLUMNS_DEFAULT,
            $actual->Columns,
        );
        $this->assertEquals(ReportType::Transactions, $actual->ReportType);
        $this->assertNull($actual->CallbackURL);

        $sampleCallbackUrl = bin2hex(random_bytes(12));
        $actual = $this->service->createReportRequest($sampleCallbackUrl);
        $this->assertEquals($sampleCallbackUrl, $actual->CallbackURL);
    }

    public function testCreateReportRecord(): void
    {
        $reportRequestId = bin2hex(random_bytes(4));
        $walletId = bin2hex(random_bytes(12));
        $description = 'Test report description' . bin2hex(random_bytes(4));
        $reportRequest = new ReportRequest();
        $reportRequest->Id = $reportRequestId;
        $reportRequest->Tag = $description;
        $reportFilters = new FilterReports();
        $reportFilters->WalletId = $walletId;
        $reportRequest->Filters = $reportFilters;
        $reportRequest->Status = MangoPayReportStatus::Pending;

        $expectedReport = new Report();
        $expectedReport->setDescription($description);
        $expectedReport->setOrigin(Report::ORIGIN_MANGOPAY);
        $expectedReport->setResourceId($walletId);
        $expectedReport->setReferenceId($reportRequestId);
        $expectedReport->setStatus(ReportStatus::Pending);

        $actual = $this->service->createReportRecord($reportRequest);
        $this->assertEquals($expectedReport, $actual);

        $reportRequest->Status = MangoPayReportStatus::ReadyForDownload;
        $expectedReport->setStatus(ReportStatus::Available);
        $actual = $this->service->createReportRecord($reportRequest);
        $this->assertEquals($expectedReport, $actual);

        $this->expectException(\InvalidArgumentException::class);
        $reportRequest->Status = MangoPayReportStatus::Expired;
        $this->service->createReportRecord($reportRequest);
    }

    public function testCreateMergedReportRecord(): void
    {
        $reportSet = EntityIdTestUtil::setEntityId(new ReportSet(), 415);
        $reportSet->setDescription('Test merged report record');

        $walletId = bin2hex(random_bytes(12));

        $expectedReport = new Report();
        $expectedReport->setDescription(
            'Test merged report record - Merged and processed report',
        );
        $expectedReport->setOrigin(Report::ORIGIN_MERGED);
        $expectedReport->setResourceId($walletId);
        $expectedReport->setReferenceId(415);
        $expectedReport->setStatus(ReportStatus::Pending);

        $actual = $this->service->createMergedReportRecord($reportSet, $walletId);
        $this->assertEquals($expectedReport, $actual);
    }

    public function testGetMergeableReports(): void
    {
        $reportSet = new ReportSet();
        $walletId = bin2hex(random_bytes(12));

        $mergeableReport1 = new Report();
        $mergeableReport1->setOrigin(Report::ORIGIN_MANGOPAY);
        $mergeableReport1->setResourceId($walletId);
        $mergeableReport1->setStatus(ReportStatus::Available);
        $reportSet->addReport($mergeableReport1);

        $pendingReport = new Report();
        $pendingReport->setOrigin(Report::ORIGIN_MANGOPAY);
        $pendingReport->setResourceId($walletId);
        $pendingReport->setStatus(ReportStatus::Pending);
        $reportSet->addReport($pendingReport);

        $mergeableReport2 = new Report();
        $mergeableReport2->setOrigin(Report::ORIGIN_MANGOPAY);
        $mergeableReport2->setResourceId($walletId);
        $mergeableReport2->setStatus(ReportStatus::Available);
        $reportSet->addReport($mergeableReport2);

        $diffWalletReport = new Report();
        $diffWalletReport->setOrigin(Report::ORIGIN_MANGOPAY);
        $diffWalletReport->setResourceId($walletId . 'diff');
        $diffWalletReport->setStatus(ReportStatus::Available);
        $reportSet->addReport($diffWalletReport);

        $mergedReport = new Report();
        $mergedReport->setOrigin(Report::ORIGIN_MERGED);
        $mergedReport->setResourceId($walletId);
        $mergedReport->setStatus(ReportStatus::Available);
        $reportSet->addReport($mergedReport);

        // Without resourceId filtering
        $actual = $this->service->getMergeableReports($reportSet);
        $expected = [$mergeableReport1, $mergeableReport2, $diffWalletReport];
        $this->assertEquals($expected, $actual);

        // With resourceId filtering
        $actual = $this->service->getMergeableReports($reportSet, $walletId);
        $expected = [$mergeableReport1, $mergeableReport2];
        $this->assertEquals($expected, $actual);
    }

    public function testGetMergedReports(): void
    {
        $reportSet = new ReportSet();

        $mangopayReport = new Report();
        $mangopayReport->setOrigin(Report::ORIGIN_MANGOPAY);
        $mangopayReport->setStatus(ReportStatus::Available);
        $reportSet->addReport($mangopayReport);

        $mergedReport1 = new Report();
        $mergedReport1->setOrigin(Report::ORIGIN_MERGED);
        $mergedReport1->setStatus(ReportStatus::Available);
        $reportSet->addReport($mergedReport1);

        $pendingReport = new Report();
        $pendingReport->setOrigin(Report::ORIGIN_MERGED);
        $pendingReport->setStatus(ReportStatus::Pending);
        $reportSet->addReport($pendingReport);

        $mergedReport2 = new Report();
        $mergedReport2->setOrigin(Report::ORIGIN_MERGED);
        $mergedReport2->setStatus(ReportStatus::Available);
        $reportSet->addReport($mergedReport2);

        $actual = $this->service->getMergedReports($reportSet);
        $expected = [$mergedReport1, $mergedReport2];
        $this->assertEquals($expected, $actual);
    }

    public static function storeReportProvider(): \Generator
    {
        yield 'Has Url' => [true, 0];
        yield 'No url' => [false, 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('storeReportProvider')]
    public function testStoreReportErrors(bool $hasUrl, int $expectedCalls): void
    {
        $report = new Report();
        $reportRequest = new ReportRequest();
        $sampleUrl = bin2hex(random_bytes(12));
        $reportRequest->DownloadURL = $sampleUrl;
        if ($hasUrl) {
            $report->setUrl($sampleUrl);
        }

        // Checking function calls
        $this->mangopayReportDownloadServiceMock
            ->expects(self::exactly($expectedCalls))
            ->method('downloadFromUrlToString')
            ->with($sampleUrl)
            ->willReturn('exampleFileStringContents');
        $this->reportStorageService
            ->expects(self::exactly($expectedCalls))
            ->method('upload')
            ->with($report, 'exampleFileStringContents');

        $this->service->storeReport($report, $reportRequest);
    }

    public function testMergeCsvFromUrls(): void
    {
        $this->mangopayReportDownloadServiceMock
            ->expects(self::exactly(2))
            ->method('downloadFromUrlToString')
            ->willReturnOnConsecutiveCalls(
                $this->convertArrayToCsvString(self::REPORT_DATA_1),
                $this->convertArrayToCsvString(self::REPORT_DATA_2),
            );

        $expected = [
            [
                'Id' => '68544458',
                'ExecutionDate' => '1686142306',
                'DebitedFundsAmount' => '26199',
                'CreditedFundsAmount' => '26199',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594221',
                'ExecutionDate' => '1686105243',
                'DebitedFundsAmount' => '478025',
                'CreditedFundsAmount' => '474025',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68592451',
                'ExecutionDate' => '1686102306',
                'DebitedFundsAmount' => '158028',
                'CreditedFundsAmount' => '157028',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594213',
                'ExecutionDate' => '1686000563',
                'DebitedFundsAmount' => '7782',
                'CreditedFundsAmount' => '7782',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68595561',
                'ExecutionDate' => '1685975306',
                'DebitedFundsAmount' => '10000',
                'CreditedFundsAmount' => '10000',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68675561',
                'ExecutionDate' => '1685955206',
                'DebitedFundsAmount' => '15',
                'CreditedFundsAmount' => '15',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68595245',
                'ExecutionDate' => '1685798563',
                'DebitedFundsAmount' => '4820',
                'CreditedFundsAmount' => '4820',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594222',
                'ExecutionDate' => '1685705563',
                'DebitedFundsAmount' => '2',
                'CreditedFundsAmount' => '2',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594145',
                'ExecutionDate' => '1685702563',
                'DebitedFundsAmount' => '168',
                'CreditedFundsAmount' => '168',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594256',
                'ExecutionDate' => '1684500563',
                'DebitedFundsAmount' => '8694',
                'CreditedFundsAmount' => '8694',
                'CreditedWalletId' => 'cw10000001',
                'DebitedWalletId' => 'dw10000001',
                'Status' => TransactionStatus::Succeeded,
            ],
        ];

        $result = $this->service->mergeCsvFromUrls('', '')->toString();

        // Read the csv string back into a Reader object
        $actual = Reader::createFromString($result, 'r');
        // Set header offset to tell Reader that there is a header in the csv
        $actual->setHeaderOffset(0);
        $this->assertSame(count($expected), $actual->count());
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testMergeCsvFromUrlsMissingColumns(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'CSV headers missing required columns for processing: DebitedFundsAmount, ExecutionDate',
            'Status',
        );

        $this->mangopayReportDownloadServiceMock
            ->expects(self::once())
            ->method('downloadFromUrlToString')
            ->willReturn($this->convertArrayToCsvString([
                [
                    'Id' => '68592451',
                    'CreditedFundsAmount' => '157028',
                    'CreditedWalletId' => 'cw10000001',
                    'DebitedWalletId' => 'dw10000001',
                ],
            ]));

        $this->service->mergeCsvFromUrls('', '')->toString();
    }

    public function testMergeCsvFromUrlsNonMatchingColumns(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'CSV headers from urls do not match. Report 1 contains extra: ExecutionDate:ISO, AuthorId. Report 2 contains extra: FeesAmount',
        );

        $this->mangopayReportDownloadServiceMock
            ->expects(self::exactly(2))
            ->method('downloadFromUrlToString')
            ->willReturnOnConsecutiveCalls(
                $this->convertArrayToCsvString([
                    [
                        'Id' => '68594256',
                        'ExecutionDate' => '1684500563',
                        'DebitedFundsAmount' => '8694',
                        'CreditedFundsAmount' => '8694',
                        'ExecutionDate:ISO' => '19/05/2023 12:49:23',
                        'AuthorId' => '1711413',
                        'CreditedWalletId' => 'cw10000001',
                        'DebitedWalletId' => 'dw10000001',
                        'Status' => TransactionStatus::Succeeded,
                    ],
                ]),
                $this->convertArrayToCsvString([
                    [
                        'Id' => '68594256',
                        'ExecutionDate' => '1684500563',
                        'DebitedFundsAmount' => '8694',
                        'CreditedFundsAmount' => '8694',
                        'FeesAmount' => '0',
                        'CreditedWalletId' => 'cw10000001',
                        'DebitedWalletId' => 'dw10000001',
                        'Status' => TransactionStatus::Succeeded,
                    ],
                ]),
            );

        $this->service->mergeCsvFromUrls('', '')->toString();
    }

    public function testMergeAndProcessReports(): void
    {
        // Note that this effectively tests mergeReportData()
        // Since mergeAndProcessReports() is a wrapper around mergeReportData()
        // That also handles column headers and the saving of the resulting csv
        $walletId = bin2hex(random_bytes(8));
        $counterpartWalletId = bin2hex(random_bytes(8));
        $input1 = [
            [
                'Id' => '68594213',
                'ExecutionDate' => '1686000563',
                'DebitedFundsAmount' => '177821',
                'CreditedFundsAmount' => '177821',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68595561',
                'ExecutionDate' => '1685975306',
                'DebitedFundsAmount' => '10000',
                'CreditedFundsAmount' => '10000',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Created,
            ],
            [
                'Id' => '68592451',
                'ExecutionDate' => '1686102306',
                'DebitedFundsAmount' => '158028',
                'CreditedFundsAmount' => '157028',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
            ],
        ];
        $input2 = [
            [
                'Id' => '68594222',
                'ExecutionDate' => '1685705563',
                'DebitedFundsAmount' => '2',
                'CreditedFundsAmount' => '2',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594145',
                'ExecutionDate' => '1685702563',
                'DebitedFundsAmount' => '16889',
                'CreditedFundsAmount' => '16889',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68592451',
                'ExecutionDate' => '1686102306',
                'DebitedFundsAmount' => '158028',
                'CreditedFundsAmount' => '157028',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
            ],
        ];

        // Should get rid of any duplicates and order newest to oldest by ExecutionDate
        $expected = [
            [
                'Id' => '68592451',
                'ExecutionDate' => '1686102306',
                'DebitedFundsAmount' => '158028',
                'CreditedFundsAmount' => '157028',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '-1580.28',
                'RunningBalance' => '0.00',
            ],
            [
                'Id' => '68594213',
                'ExecutionDate' => '1686000563',
                'DebitedFundsAmount' => '177821',
                'CreditedFundsAmount' => '177821',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '1778.21',
                'RunningBalance' => '1580.28',
            ],
            [
                'Id' => '68595561',
                'ExecutionDate' => '1685975306',
                'DebitedFundsAmount' => '10000',
                'CreditedFundsAmount' => '10000',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Created,
                'Amount' => '0.00',
                'RunningBalance' => '-197.93',
            ],
            [
                'Id' => '68594222',
                'ExecutionDate' => '1685705563',
                'DebitedFundsAmount' => '2',
                'CreditedFundsAmount' => '2',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '0.00',
                'RunningBalance' => '-197.93',
            ],
            [
                'Id' => '68594145',
                'ExecutionDate' => '1685702563',
                'DebitedFundsAmount' => '16889',
                'CreditedFundsAmount' => '16889',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '-168.89',
                'RunningBalance' => '-197.93',
            ],
        ];
        $mergedReport = EntityIdTestUtil::setEntityId(new Report(), 681);

        $this->reportStorageService
            ->expects(self::exactly(2))
            ->method('download')
            ->willReturnOnConsecutiveCalls(
                $this->convertArrayToCsvString($input1),
                $this->convertArrayToCsvString($input2),
            );
        $this->reportStorageService
            ->expects(self::once())
            ->method('upload')
            ->with($mergedReport, $this->convertArrayToCsvString($expected));

        $inputReport = new Report();
        $inputReport->setResourceId($walletId);

        $result = $this->service->mergeAndProcessReports(
            $mergedReport,
            [$inputReport, $inputReport],
            true,
            true,
        );
        $this->assertEquals(ReportStatus::Available, $result->getStatus());
    }

    public function testMergeAndProcessReportsMissingRecordId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Report record does not have an Id');
        $this->service->mergeAndProcessReports(new Report(), [new Report()]);
    }

    public function testMergeAndProcessReportsMissingColumns(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'CSV headers missing required columns for processing: DebitedFundsAmount, ExecutionDate',
            'DebitedWalletId',
            'Status',
        );

        $this->reportStorageService
            ->expects(self::once())
            ->method('download')
            ->willReturn($this->convertArrayToCsvString([
                [
                    'Id' => '68592451',
                    'CreditedFundsAmount' => '157028',
                    'CreditedWalletId' => 'cw10000001',
                ],
            ]));

        $mergedReport = EntityIdTestUtil::setEntityId(new Report(), 681);
        $inputReport = new Report();
        $inputReport->setResourceId('dw10000001');
        $this->service->mergeAndProcessReports(
            $mergedReport,
            [$inputReport],
            true,
            true,
        );
    }

    public function testMergeAndProcessReportsNonMatchingColumns(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'CSV headers from different reports do not match. Report A contains extra: ExecutionDate:ISO, AuthorId. Report B contains extra: FeesAmount',
        );

        $this->reportStorageService
            ->expects(self::exactly(2))
            ->method('download')
            ->willReturnOnConsecutiveCalls(
                $this->convertArrayToCsvString([
                    [
                        'Id' => '68594256',
                        'ExecutionDate' => '1684500563',
                        'DebitedFundsAmount' => '8694',
                        'CreditedFundsAmount' => '8694',
                        'ExecutionDate:ISO' => '19/05/2023 12:49:23',
                        'AuthorId' => '1711413',
                        'CreditedWalletId' => 'cw10000001',
                        'DebitedWalletId' => 'dw10000001',
                        'Status' => TransactionStatus::Succeeded,
                    ],
                ]),
                $this->convertArrayToCsvString([
                    [
                        'Id' => '68594256',
                        'ExecutionDate' => '1684500563',
                        'DebitedFundsAmount' => '8694',
                        'CreditedFundsAmount' => '8694',
                        'FeesAmount' => '0',
                        'CreditedWalletId' => 'cw10000001',
                        'DebitedWalletId' => 'dw10000001',
                        'Status' => TransactionStatus::Succeeded,
                    ],
                ]),
            );

        $mergedReport = EntityIdTestUtil::setEntityId(new Report(), 681);
        $inputReport = new Report();
        $inputReport->setResourceId('dw10000001');
        $this->service->mergeAndProcessReports(
            $mergedReport,
            [$inputReport, $inputReport],
            true,
            true,
        );
    }

    public function testProcessReport(): void
    {
        $walletId = bin2hex(random_bytes(8));
        $counterpartWalletId = bin2hex(random_bytes(8));
        $input = [
            [
                'Id' => '68592451',
                'ExecutionDate' => '1686102306',
                'DebitedFundsAmount' => '158028',
                'CreditedFundsAmount' => '157028',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594213',
                'ExecutionDate' => '1686000563',
                'DebitedFundsAmount' => '177821',
                'CreditedFundsAmount' => '177821',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68595561',
                'ExecutionDate' => '1685975306',
                'DebitedFundsAmount' => '10000',
                'CreditedFundsAmount' => '10000',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Created,
            ],
            [
                'Id' => '68594222',
                'ExecutionDate' => '1685705563',
                'DebitedFundsAmount' => '2',
                'CreditedFundsAmount' => '2',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594120',
                'ExecutionDate' => '1685702543',
                'DebitedFundsAmount' => '15732',
                'CreditedFundsAmount' => '15732',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
            ],
            [
                'Id' => '68594145',
                'ExecutionDate' => '1685702563',
                'DebitedFundsAmount' => '16889',
                'CreditedFundsAmount' => '16889',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
            ],
        ];
        $expected = [
            [
                'Id' => '68592451',
                'ExecutionDate' => '1686102306',
                'DebitedFundsAmount' => '158028',
                'CreditedFundsAmount' => '157028',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '-1580.28',
                'RunningBalance' => '9.76',
            ],
            [
                'Id' => '68594213',
                'ExecutionDate' => '1686000563',
                'DebitedFundsAmount' => '177821',
                'CreditedFundsAmount' => '177821',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '1778.21',
                'RunningBalance' => '1590.04',
            ],
            // Transactions not in succeeded state are considered an amount of 0
            [
                'Id' => '68595561',
                'ExecutionDate' => '1685975306',
                'DebitedFundsAmount' => '10000',
                'CreditedFundsAmount' => '10000',
                'CreditedWalletId' => $walletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Created,
                'Amount' => '0.00',
                'RunningBalance' => '-188.17',
            ],
            // If walletId is not in either for some reason, its amount is treated as 0
            // Because it is not considered a debit or credit on that wallet
            [
                'Id' => '68594222',
                'ExecutionDate' => '1685705563',
                'DebitedFundsAmount' => '2',
                'CreditedFundsAmount' => '2',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $counterpartWalletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '0.00',
                'RunningBalance' => '-188.17',
            ],
            [
                'Id' => '68594145',
                'ExecutionDate' => '1685702563',
                'DebitedFundsAmount' => '16889',
                'CreditedFundsAmount' => '16889',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '-168.89',
                'RunningBalance' => '-188.17',
            ],
            [
                'Id' => '68594120',
                'ExecutionDate' => '1685702543',
                'DebitedFundsAmount' => '15732',
                'CreditedFundsAmount' => '15732',
                'CreditedWalletId' => $counterpartWalletId,
                'DebitedWalletId' => $walletId,
                'Status' => TransactionStatus::Succeeded,
                'Amount' => '-157.32',
                'RunningBalance' => '-19.28',
            ],
        ];

        $actual = $this->service->processReport($input, $walletId, true, 976);
        $this->assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('currencyDivisorProvider')]
    public function testApplyCurrencyDivisor(
        string $input,
        string $expected,
        ?int $divisor = null,
    ): void {
        if (is_null($divisor)) {
            $actual = $this->service->applyCurrencyDivisor($input);
        } else {
            $actual = $this->service->applyCurrencyDivisor($input, $divisor);
        }
        $this->assertSame($expected, $actual);
    }

    public static function currencyDivisorProvider(): \Generator
    {
        yield 'Sub 1000 integer' => ['789', '7.89'];
        yield 'Multi 1000 integer' => ['1869182', '18691.82'];
        yield 'Custom divisor' => ['26845', '1677.81', 16];
        yield 'Sub 1000 float' => ['789.67', '7.90'];
        yield 'Multi 1000 float' => ['67824.41', '678.24'];
    }

    /**
     * Leverages fputcsv to convert array to csv. Reuses the same function parameters defaults
     */
    private function convertArrayToCsvString(
        array $csvAsArray,
        $delimiter = ',',
        $enclosure = '"',
        $escape_char = "\\",
    ): string {
        $f = fopen('php://memory', 'r+');
        // Add the header first
        fputcsv($f, array_keys($csvAsArray[0]), $delimiter, $enclosure, $escape_char);
        // Add each row to the output
        foreach ($csvAsArray as $item) {
            fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
        }
        rewind($f);
        $arrayAsCsvString = stream_get_contents($f);
        // Remember to close the resource stream!
        fclose($f);
        return $arrayAsCsvString;
    }
}
