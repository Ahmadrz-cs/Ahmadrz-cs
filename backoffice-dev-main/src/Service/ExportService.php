<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Enum\ExportReportType;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class ExportService
{
    public const REPORT_ASSET = 'assets';
    public const REPORT_USER = 'users';
    public const REPORT_PAYOUT = 'payouts';
    public const REPORT_TRANSACTION = 'transactions';
    public const REPORT_CONTEGO = 'contego_logs';
    public const REPORT_LEGACY_SHAREHOLDINGS = 'legacy_shareholdings';
    public const REPORT_LEGACY_SHARE_TRADES = 'legacy_share_trades';
    public const REPORT_INVESTMENT_PAYOUT = 'investment_payouts';
    public const REPORT_SHARE_REGISTER = 'share_register';
    public const REPORT_LEGACY_SHAREHOLDINGS_EXT = 'legacy_extended_shareholdings';
    public const REPORT_SHARE_REGISTER_OLD = 'share_register_with_wallet_ids';
    public const REPORT_OFFERING = 'offerings';
    public const REPORT_INVESTMENT = 'investments';

    public const SUPPORTED_USER_FIELDS = [
        'userId',
        'creditedUserId',
        'buyerId',
        'sellerId',
        'user_id',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    public function getAvailableCustomReports(): array
    {
        /**
         * Basically a verbose implementation of an enum for custom reports we support
         * Instead of doing `string $reportName` in our function params
         * With enums we'd have: `CustomReports $reportName`
         */
        $reflectionClass = new ReflectionClass($this);
        $constants = $reflectionClass->getConstants();
        $notCustomisable = [
            self::REPORT_LEGACY_SHAREHOLDINGS_EXT,
            self::REPORT_LEGACY_SHAREHOLDINGS,
            self::REPORT_LEGACY_SHARE_TRADES,
        ];
        $customReports = [];
        foreach ($constants as $key => $value) {
            if ('REPORT_' == substr($key, 0, 7)) {
                $customReports[$value] = (int) !in_array($value, $notCustomisable);
            }
        }
        return $customReports;
    }

    public function isSupportedReport(string $reportName): bool
    {
        return in_array($reportName, array_keys($this->getAvailableCustomReports()));
    }

    public function getFieldNames(string $reportName): array
    {
        $viewName = $this->getViewName($reportName);
        $qb = $this->em->getConnection()->createQueryBuilder();
        $qb->select('*')->from($viewName)->setMaxResults(1);
        $viewSample = $qb->executeQuery()->fetchAssociative();
        if (is_array($viewSample)) {
            return array_keys($viewSample);
        }
        return [];
    }

    public function getReportData(
        string $reportName,
        array $columns = [],
        array $filters = [],
    ): array {
        $viewName = $this->getViewName($reportName);
        if (!$viewName) {
            return [];
        }

        $conn = $this->em->getConnection();
        $qb = $conn->createQueryBuilder();

        // ensure only valid column names are used in the query
        $permittedFields = $this->getFieldNames($reportName);
        $columns = array_intersect($permittedFields, $columns);

        if ($columns) {
            $qb->select(...$this->prepareColumnNames($columns));
        } else {
            $qb->select('*');
        }
        $qb->from($viewName, 'base');

        // special handling for extended shareholdings
        if (self::REPORT_LEGACY_SHAREHOLDINGS_EXT == $reportName) {
            $qb = $this->extendShareholdingsQuery($qb);
        }

        if (in_array('createdAt', $permittedFields)) {
            if (
                isset($filters['createdAt_gte'])
                && $filters['createdAt_gte'] instanceof \DateTime
            ) {
                $qb->andwhere('createdAt >= :createdAt_gte');
                $qb->setParameter(
                    'createdAt_gte',
                    $filters['createdAt_gte']->format('Y-m-d'),
                );
            }
            if (
                isset($filters['createdAt_lt'])
                && $filters['createdAt_lt'] instanceof \DateTime
            ) {
                $qb->andwhere('createdAt < :createdAt_lt');
                $qb->setParameter(
                    'createdAt_lt',
                    $filters['createdAt_lt']->format('Y-m-d'),
                );
            }
        }

        if (in_array('assetId', $permittedFields)) {
            if (isset($filters['assetId']) && $filters['assetId'] instanceof Asset) {
                $qb->andwhere('assetId = :assetId');
                $qb->setParameter('assetId', (string) $filters['assetId']->getId());
            }
        }

        // Check if any supported user fields are in the filters
        $userColumns = array_intersect(self::SUPPORTED_USER_FIELDS, $permittedFields);
        if (!empty($userColumns)) {
            foreach ($userColumns as $fieldName) {
                if (isset($filters[$fieldName])) {
                    $qb->andwhere("{$fieldName} = :userId");
                    $qb->setParameter('userId', $filters[$fieldName]);
                }
            }
        }

        // sort by reverse chronological with id if possible
        if (in_array('id', $permittedFields)) {
            $qb->addOrderBy('id', 'DESC');
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function prepareColumnNames(array $columns): array
    {
        $prepared = [];
        foreach ($columns as $columnName) {
            // Use backtick to handle spaces and dots in column names
            $prepared[] = '`' . $columnName . '`';
        }
        return $prepared;
    }

    /**
     * Returns the array where
     * - Key is the filter form identifier (as filters themselves can have similarities between entities)
     * - Value is a key-value pair where
     *   - Key is the name of the filter (used in the query and the exact form field name)
     *   - Value is the default values (an array) it should be set to
     */
    public function getReportSpecificFilters(ExportReportType $type): array
    {
        return match ($type) {
            ExportReportType::ShareTrades => ['tradeStatus' => []],
            ExportReportType::TradeOrders => [
                'tradeOrderStatus' => [],
                'tradeOrderType' => ['type' => TradeOrderType::allTradingTypes()],
                'direction' => [],
            ],
            default => [],
        };
    }

    /**
     * ORM field names combine the default non-associative fields with customised association
     * field definitions
     * - Non-associative fields are pulled from the Doctrine ORM metadata
     *   - These values are also used as keys so the report builder can get nice display
     *     names with just array_keys(), otherwise you'd just see the integer array keys
     * - Customised associations (relations) fields are defined as key-value pairs
     *   - Key represents the field (column) name, equivalent to the AS field alias in SQL
     *   - The value represents the dot notation property access chain (similar to twig)
     *     - This uses the Symfony property accessor component, you can use entity methods
     *       to format the output or provide custom data like buyOrder.expectedStampDuty
     *     - E.g. For a ShareTrade, "buyOrder.user.id" will eventually do:
     *       shareTrade->getBuyOrder()->getUser()->getId()
     * - This associative array is passed to the Sonata\Exporter DoctrineORMQuerySourceIterator
     */
    public function getOrmFieldNames(ExportReportType $type): array
    {
        return match ($type) {
            ExportReportType::ShareTradeRegister => $this->getShareTradeFields(true),
            ExportReportType::ShareTrades => $this->getShareTradeFields(),
            ExportReportType::TradeOrders => $this->getTradeOrderFields(),
            default => [],
        };
    }

    /**
     * The query generated MUST be iterable by Doctrine for memory optimisation
     *
     * For entities with one-to-many relations like status logs
     * use distinct() to tell doctrine how to handle the relation
     *
     * The Sonata\Exporter DoctrineORMQuerySourceIterator will do 100 per batch
     * by default before clearing the entity manager to save memory
     */
    public function getOrmQuery(ExportReportType $type, array $filters): Query
    {
        return match ($type) {
            ExportReportType::ShareTradeRegister => $this->getShareRegisterData(
                $filters,
            ),
            ExportReportType::ShareTrades
                => $this->shareTradeRepository->buildQueryWithAssociations(
                filters: $filters,
            ),
            ExportReportType::TradeOrders
                => $this->tradeOrderRepository->buildQueryWithAssociations(
                filters: $filters,
            ),
        };
    }

    private function extendShareholdingsQuery(QueryBuilder $qb): QueryBuilder
    {
        $additionalColumns = [
            'ast.companyNumber AS assetSPV',
            'usr.firstname',
            'usr.lastname',
            'usr.email AS userContactEmail',
        ];
        $qb
            ->addSelect(...$additionalColumns)
            ->leftJoin('base', 'assets', 'ast', 'base.assetId = ast.id')
            ->leftJoin('base', 'users', 'usr', 'base.userId = usr.id');
        return $qb;
    }

    private function getViewName(string $reportName): string
    {
        $viewsMap = [
            self::REPORT_SHARE_REGISTER_OLD => 'getShareRegister',
            self::REPORT_SHARE_REGISTER => 'shareRegister',
            self::REPORT_LEGACY_SHAREHOLDINGS_EXT => 'getShareHoldings',
            self::REPORT_ASSET => 'getAssetData',
            self::REPORT_OFFERING => 'getOfferingData',
            self::REPORT_INVESTMENT => 'getInvestmentData',
            self::REPORT_USER => 'getUserData',
            self::REPORT_PAYOUT => 'payoutsReport',
            self::REPORT_INVESTMENT_PAYOUT => 'getInvestmentPayouts',
            self::REPORT_TRANSACTION => 'getTransactionData',
            self::REPORT_CONTEGO => 'getContegoLog',
            self::REPORT_LEGACY_SHAREHOLDINGS => 'getShareHoldings',
            self::REPORT_LEGACY_SHARE_TRADES => 'getShareTrades',
        ];
        return $viewsMap[$reportName] ?? '';
    }

    private function getShareRegisterData(array $filters): Query
    {
        $filters = array_merge($filters, [
            'sellOrderType' => TradeOrderType::tradingSellTypes(),
            'buyOrderType' => TradeOrderType::tradingBuyTypes(),
            'status' => [TradeStatus::Settled],
        ]);
        return $this->shareTradeRepository
            ->extendShareTradeQuery($this->shareTradeRepository->buildQueryWithAssociations(
                filters: $filters,
                asBuilder: true,
            ))
            ->getQuery();
    }

    private function getTradeOrderFields(): array
    {
        $fields = $this->em->getClassMetadata(TradeOrder::class)->getFieldNames();
        $fields = array_combine($fields, $fields);

        $fields = array_merge($fields, [
            'status' => 'status',
            'statusOccuredAt' => 'currentStatusLog.occuredAt',
            'assetId' => 'asset.id',
            'assetName' => 'asset.name',
            'assetSpv' => 'asset.companyNumber',
            'userId' => 'user.id',
            'userUsername' => 'user.userIdentifier',
            'userContactEmail' => 'user.email',
        ]);
        return $fields;
    }

    private function getShareTradeFields(bool $extend = false): array
    {
        $fields = $this->em->getClassMetadata(ShareTrade::class)->getFieldNames();
        // $baseKeys = array_map(fn(string $field) => 'trade' . ucfirst($field), $fields);
        $fields = array_combine($fields, $fields);

        // Note that to preset column ordering, we'll just repeat ourselves rather than conditionally merge
        if ($extend) {
            // Extended mode returns a large number of user info fields, similar to the classic share register
            $fields = array_merge($fields, [
                'status' => 'status',
                'statusOccuredAt' => 'currentStatusLog.occuredAt',
                'expectedStampDuty' => 'buyOrder.expectedStampDuty',
                'assetId' => 'buyOrder.asset.id',
                'assetName' => 'buyOrder.asset.name',
                'assetSpv' => 'buyOrder.asset.companyNumber',
                'buyOrderId' => 'buyOrder.id',
                'buyOrderType' => 'buyOrder.type',
                'buyOrderFees' => 'buyOrder.fees',
                'buyOrderTaxes' => 'buyOrder.taxes',
                'buyOrderTransactionId' => 'buyOrder.transactionReference',
                'buyerId' => 'buyOrder.user.id',
                'buyerUsername' => 'buyOrder.user.userIdentifier',
                'buyerContactEmail' => 'buyOrder.user.email',
                'buyerTitle' => 'buyOrder.user.honoricPrefix',
                'buyerName' => 'buyOrder.user.fullname',
                'buyerAddress' => 'buyOrder.user.mainAddress',
                'buyerCompanyName' => 'buyOrder.user.company.name',
                'buyerCompanyRegNumber' => 'buyOrder.user.company.registrationNumber',
                'buyerCompanyAddress1' => 'buyOrder.user.company.regAddress1',
                'buyerCompanyPostCode' => 'buyOrder.user.company.postCode',
                'buyerCompanyApprovedOn' => 'buyOrder.user.customCompanyApprovedOn',
                'buyerJoinDate' => 'buyOrder.user.createdAt',
                'buyerObpCategory' => 'buyOrder.user.onboardingProfile.category',
                'buyerObpAssessmentPassed' => 'buyOrder.user.onboardingProfile.assessmentPassed',
                'buyerFatca' => 'buyOrder.user.customFatca',
                'buyerLegacyHnw' => 'buyOrder.user.investor.cxbWorthInvestor',
                'buyerLegacySophisticated' => 'buyOrder.user.investor.cxbSophisticatedInvestor',
                'buyerLegacyRestricted' => 'buyOrder.user.investor.cxbRestrictedUser',
                'buyerLegacyCorporate' => 'buyOrder.user.investor.corporateInvestor',
                'sellOrderId' => 'sellOrder.id',
                'sellOrderType' => 'sellOrder.type',
                'sellOrderFees' => 'sellOrder.fees',
                'sellOrderTaxes' => 'sellOrder.taxes',
                'sellOrderTransactionId' => 'sellOrder.transactionReference',
                'sellerId' => 'sellOrder.user.id',
                'sellerUsername' => 'sellOrder.user.userIdentifier',
                'sellerContactEmail' => 'sellOrder.user.email',
                'sellerTitle' => 'sellOrder.user.honoricPrefix',
                'sellerName' => 'sellOrder.user.fullname',
                'sellerAddress' => 'sellOrder.user.mainAddress',
            ]);
        } else {
            // Share trdaes should generally be accompanied by the respective trade orders info
            // As by they are not entirely useful without it
            $fields = array_merge($fields, [
                'status' => 'status',
                'statusOccuredAt' => 'currentStatusLog.occuredAt',
                'assetId' => 'buyOrder.asset.id',
                'assetName' => 'buyOrder.asset.name',
                'assetSpv' => 'buyOrder.asset.companyNumber',
                'buyOrderId' => 'buyOrder.id',
                'buyOrderType' => 'buyOrder.type',
                'buyerId' => 'buyOrder.user.id',
                'buyerUsername' => 'buyOrder.user.userIdentifier',
                'buyerContactEmail' => 'buyOrder.user.email',
                'sellOrderId' => 'sellOrder.id',
                'sellOrderType' => 'sellOrder.type',
                'sellerId' => 'sellOrder.user.id',
                'sellerUsername' => 'sellOrder.user.userIdentifier',
                'sellerContactEmail' => 'sellOrder.user.email',
            ]);
        }
        return $fields;
    }
}
