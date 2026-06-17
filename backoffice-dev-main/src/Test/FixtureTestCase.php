<?php

namespace App\Test;

use Doctrine\ORM\EntityManager;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class FixtureTestCase extends WebTestCase
{
    protected const MIN_COMMIT = 100;

    protected const USER_SUPER_ADMIN = 'superadmin@test.yielderverse.co.uk';
    protected const USER_ADMIN = 'admin.auto@test.yielderverse.co.uk';
    protected const USER_FINOPS = 'finops.auto@test.yielderverse.co.uk';
    protected const USER_OPERATIONS = 'operations.auto@test.yielderverse.co.uk';
    protected const USER_ANALYST = 'analyst.auto@test.yielderverse.co.uk';
    protected const USER_TECHOPS = 'techops.auto@test.yielderverse.co.uk';
    protected const USER_REGULAR = 'ben.auto@test.yielderverse.co.uk';
    protected const USER_REGULAR_2 = 'holly.auto@test.yielderverse.co.uk';
    protected const USER_REGULAR_3 = 'jim.auto@test.yielderverse.co.uk';
    protected const USER_LOW_BALANCE = 'anne.auto@test.yielderverse.co.uk';
    protected const USER_STAMP_DUTY = 'stampduty@yielders.co.uk';
    protected const USER_VIP = 'freya.auto@test.yielderverse.co.uk';
    protected const USER_VENDOR = 'lorna.auto@test.yielderverse.co.uk';
    protected const USER_REG_KYC_GREEN = 'kycgreen.auto@test.yielderverse.co.uk';
    protected const USER_REG_KYC_AMBER = 'kycamber.auto@test.yielderverse.co.uk';
    protected const USER_REG_KYC_RED = 'kycred.auto@test.yielderverse.co.uk';
    protected const USER_EMAIL_UNVERIFIED = 'yalta_1signupd@test.yielderverse.co.uk';
    protected const USER_EMAIL_VERIFIED = 'yorran_2sverified@test.yielderverse.co.uk';
    protected const USER_PASSWORD_STANDARD = 'HarvestBounty!756';
    protected const OAUTH2_CLIENT_DEFAULT = [
        'clientId' => '904c1b4d9a15529ed70ff5e686345a9f',
        'clientSecret' => '71dcf8066c14b07a772a3c8af9217c318dc4385f9fa8215ab5eb11e511fd2cbda50714463088226b8ceefed0126482f2c36f2f4fe7ec4f5ba7c47935b02a9c8e',
    ];
    protected const OAUTH2_CLIENT_VENDOR = [
        'clientId' => '79343e58d4a87af9c9b61ea8a57bad2b',
        'clientSecret' => 'bfe242f58b97f7a6836987f4174710507c905062fdab82e5b07c675a808930691818f630dfaef1414fbbf678f552d64756678223f4ec3df859e18e65234ee7d6',
    ];

    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;
    protected ?EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();
        /**
         * Use classes that implement doctrine FixtureLoader
         * Forces liip/test-fixtures-bundle to use cache properly for performance
         */
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\DevViewFixtures',
            'App\DataFixtures\DevTestFixtures',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // https://symfony.com/doc/current/testing/database.html
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @return array<int|string>
     */
    protected function convertToIds(
        iterable $objects,
        bool $castToString = false,
    ): array {
        $ids = [];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($objects as $object) {
            $objectId = $propertyAccessor->getValue($object, 'id');
            // $ids[] = $object->getId();
            $objectId = $castToString ? (string) $objectId : $objectId;
            $ids[] = $objectId;
        }
        return $ids;
    }

    /**
     * @template T
     * @param class-string<T> $entityClassName
     * @return T[]|int[]
     */
    protected function searchFixtures(
        string $entityClassName,
        array $criteria = [],
        bool $asId = false,
        bool $clear = false,
    ): array {
        $objects = $this->getAllOfType($entityClassName, $clear);
        $criteria = $this->normaliseCriteria($criteria);
        switch ($entityClassName) {
            case \App\Entity\Asset::class:
                $matches = $this->filterAssets($objects, $criteria, $asId);
                break;
            case \App\Entity\AssetDocuments::class:
                $matches = $this->filterAssetDocs($objects, $criteria, $asId);
                break;
            case \App\Entity\Investment::class:
                $matches = $this->filterInvestments($objects, $criteria, $asId);
                break;
            case \App\Entity\InvestmentDocuments::class:
                $matches = $this->filterInvestmentDocs($objects, $criteria, $asId);
                break;
            case \App\Entity\Offering::class:
                $matches = $this->filterOfferings($objects, $criteria, $asId);
                break;
            case \App\Entity\OfferingDocuments::class:
                $matches = $this->filterOfferingDocs($objects, $criteria, $asId);
                break;
            case \App\Entity\Payout::class:
                $matches = $this->filterPayouts($objects, $criteria, $asId);
                break;
            case \App\Entity\User::class:
                $matches = $this->filterUsers($objects, $criteria, $asId);
                break;
            case \App\Entity\UserDocument::class:
                $matches = $this->filterUserDocs($objects, $criteria, $asId);
                break;
            case \App\Entity\ContegoLog::class:
                $matches = $this->filterContegoLog($objects, $criteria, $asId);
                break;
            case \App\Entity\UserClient::class:
                $matches = $this->filterUserClients($objects, $criteria, $asId);
                break;
            case \App\Entity\Document::class:
                $matches = $this->filterDocuments($objects, $criteria, $asId);
                break;
            case \App\Entity\Transaction::class:
                $matches = $this->filterTransactions($objects, $criteria, $asId);
                break;
            case \App\Entity\PaymentOrder::class:
                $matches = $this->filterPaymentOrders($objects, $criteria, $asId);
                break;
            case \App\Entity\PaymentRequest::class:
                $matches = $this->filterPaymentRequests($objects, $criteria, $asId);
                break;
            case \App\Entity\TransferOrder::class:
                $matches = $this->filterTransferOrders($objects, $criteria, $asId);
                break;
            case \App\Entity\TransferRequest::class:
                $matches = $this->filterTransferRequests($objects, $criteria, $asId);
                break;
            case \App\Entity\BankAccount::class:
                $matches = $this->filterBankAccountRegistrations(
                    $objects,
                    $criteria,
                    $asId,
                );
                break;

            default:
                $matches = [];
                $this->fail('Trying to search for unknown entity: .'
                . $entityClassName);
        }
        if (empty($matches)) {
            $this->fail('Unable to find suitable fixture for tests');
        }
        return $matches;
    }

    /**
     * @param \App\Entity\Asset[] $haystack
     */
    private function filterAssets(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        /**
         * Supported criteria - criteria is an dictionary of lists {a:[], b:[]}
         * - id
         * - name
         * - assetType
         * - companyNumber
         * - investmentTerm
         * - blockForSale
         * - status (lifecycleStatus)
         */
        // $exampleCriteria = [
        //     "id" => [1, 2, 3],
        //     "assetType"=> ["Commercial"],
        //     "status"=> ["approved", "published",]
        // ];
        $matches = [];
        foreach ($haystack as $item) {
            /**
             * Fall-through filter system
             * - if the criteria has been specified AND it fails to meet the criteria
             *   - skip to next item (current item not added to matches)
             *
             * - if the criteria isn't specified
             *   - pass onto next filter
             *
             * - if no criteria specified, no filtering occurs
             */
            if (
                isset($criteria['status'])
                && !in_array(
                    $item->getStatus()->getLifecycleStatus(),
                    $criteria['status'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['name'])
                && !in_array($item->getName(), $criteria['name'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getAssetType(), $criteria['type'])
            ) {
                continue;
            }
            if (
                isset($criteria['companyNumber'])
                && !in_array($item->getCompanyNumber(), $criteria['companyNumber'])
            ) {
                continue;
            }
            if (
                isset($criteria['investmentTerm'])
                && !in_array($item->getInvestmentTerm(), $criteria['investmentTerm'])
            ) {
                continue;
            }
            if (
                isset($criteria['blockForSale'])
                && !in_array($item->getBlockedForSale(), $criteria['blockForSale'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\AssetDocuments[] $haystack
     */
    private function filterAssetDocs(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['asset'])
                && !in_array($item->getAsset()->getId(), $criteria['asset'])
            ) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array(
                    $item->getDocument()->getDescription(),
                    $criteria['description'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['tag'])
                && !in_array($item->getDocument()->getTag(), $criteria['tag'])
            ) {
                continue;
            }
            if (
                isset($criteria['filename'])
                && !in_array($item->getDocument()->getFileName(), $criteria['filename'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getDocument()->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['docId'])
                && !in_array($item->getDocument()->getId(), $criteria['docId'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\Investment[] $haystack
     */
    private function filterInvestments(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['status'])
                && !in_array(
                    $item->getStatus()->getLifecycleStatus(),
                    $criteria['status'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['createdBy'])
                && !in_array($item->getCreatedBy(), $criteria['createdBy'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (
                isset($criteria['for_sale'])
                && !in_array($item->getForSale(), $criteria['for_sale'])
            ) {
                continue;
            }
            if (
                isset($criteria['user'])
                && !in_array($item->getUser()->getId(), $criteria['user'])
            ) {
                continue;
            }
            if (
                isset($criteria['offering'])
                && !in_array($item->getOffering()->getId(), $criteria['offering'])
            ) {
                continue;
            }
            if (
                isset($criteria['asset'])
                && !in_array(
                    $item->getOffering()->getAsset()->getId(),
                    $criteria['asset'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['comments'])
                && !in_array($item->getComments(), $criteria['comments'])
            ) {
                continue;
            }
            if (
                isset($criteria['term'])
                && !in_array($item->getTerm(), $criteria['term'])
            ) {
                continue;
            }
            if (
                isset($criteria['name'])
                && !in_array($item->getName(), $criteria['name'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\InvestmentDocuments[] $haystack
     */
    private function filterInvestmentDocs(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['investment'])
                && !in_array($item->getInvestment()->getId(), $criteria['investment'])
            ) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array(
                    $item->getDocument()->getDescription(),
                    $criteria['description'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['tag'])
                && !in_array($item->getDocument()->getTag(), $criteria['tag'])
            ) {
                continue;
            }
            if (
                isset($criteria['filename'])
                && !in_array($item->getDocument()->getFileName(), $criteria['filename'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getDocument()->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['docId'])
                && !in_array($item->getDocument()->getId(), $criteria['docId'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\Offering[] $haystack
     */
    private function filterOfferings(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['status'])
                && !in_array(
                    $item->getStatus()->getLifecycleStatus(),
                    $criteria['status'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['createdBy'])
                && !in_array($item->getCreatedBy(), $criteria['createdBy'])
            ) {
                continue;
            }
            if (
                isset($criteria['name'])
                && !in_array($item->getName(), $criteria['name'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getOfferingType(), $criteria['type'])
            ) {
                continue;
            }
            if (
                isset($criteria['additionalType'])
                && !in_array($item->getAdditionalType(), $criteria['additionalType'])
            ) {
                continue;
            }
            if (
                isset($criteria['category'])
                && !in_array($item->getCategory(), $criteria['category'])
            ) {
                continue;
            }
            if (
                isset($criteria['isFeatured'])
                && !in_array($item->getIsFeatured(), $criteria['isFeatured'])
            ) {
                continue;
            }
            if (
                isset($criteria['isSecondaryMrkt'])
                && !in_array($item->getIsSecondaryMrkt(), $criteria['isSecondaryMrkt'])
            ) {
                continue;
            }
            if (
                isset($criteria['offeringTerm'])
                && !in_array($item->getOfferingTerm(), $criteria['offeringTerm'])
            ) {
                continue;
            }
            if (
                isset($criteria['comments'])
                && !in_array($item->getComments(), $criteria['comments'])
            ) {
                continue;
            }
            if (
                isset($criteria['investment'])
                && !(
                    $item->getSellInvestment()
                    && in_array(
                        $item->getSellInvestment()->getId(),
                        $criteria['investment'],
                    )
                )
            ) {
                continue;
            }
            if (
                isset($criteria['asset'])
                && !in_array($item->getAsset()->getId(), $criteria['asset'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\OfferingDocuments[] $haystack
     */
    private function filterOfferingDocs(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['offering'])
                && !in_array($item->getOffering()->getId(), $criteria['offering'])
            ) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array(
                    $item->getDocument()->getDescription(),
                    $criteria['description'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['tag'])
                && !in_array($item->getDocument()->getTag(), $criteria['tag'])
            ) {
                continue;
            }
            if (
                isset($criteria['filename'])
                && !in_array($item->getDocument()->getFileName(), $criteria['filename'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getDocument()->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['docId'])
                && !in_array($item->getDocument()->getId(), $criteria['docId'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\Payout[] $haystack
     */
    private function filterPayouts(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            // if (isset($criteria['status']) && !in_array($item->getStatus()->getLifecycleStatus(), $criteria['status'])) {
            //     continue;
            // }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['createdBy'])
                && !in_array($item->getCreatedBy(), $criteria['createdBy'])
            ) {
                continue;
            }
            if (
                isset($criteria['additionalType'])
                && !in_array($item->getAdditionalType(), $criteria['additionalType'])
            ) {
                continue;
            }
            if (
                isset($criteria['payoutType'])
                && !in_array($item->getPayoutType(), $criteria['payoutType'])
            ) {
                continue;
            }
            if (
                isset($criteria['dueDate'])
                && !in_array($item->getDueDate()->format('d-m-Y'), $criteria['dueDate'])
            ) {
                continue;
            }
            /**
             * The following filters are all nullable relations
             * Must check that the relation is NOT null, before trying to extract an id
             * If relation is null, the AND condition will short-circuit
             * This will skip an attempt to access a property from null
             */
            if (
                isset($criteria['user'])
                && !(
                    $item->getInvestment()
                    && in_array(
                        $item->getInvestment()->getUser()->getId(),
                        $criteria['user'],
                    )
                )
            ) {
                continue;
            }
            if (
                isset($criteria['creditedUser'])
                && !(
                    $item->getCreditedUser()
                    && in_array(
                        $item->getCreditedUser()->getId(),
                        $criteria['creditedUser'],
                    )
                )
            ) {
                continue;
            }
            if (
                isset($criteria['investment'])
                && !(
                    $item->getInvestment()
                    && in_array(
                        $item->getInvestment()->getId(),
                        $criteria['investment'],
                    )
                )
            ) {
                continue;
            }
            if (
                isset($criteria['asset'])
                && !(
                    $item->getAsset()
                    && in_array($item->getAsset()->getId(), $criteria['asset'])
                )
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\User[] $haystack
     */
    private function filterUsers(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['status'])
                && !in_array(
                    $item->getStatus()->getLifecycleStatus(),
                    $criteria['status'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['username'])
                && !in_array($item->getUserIdentifier(), $criteria['username'])
            ) {
                continue;
            }
            if (
                isset($criteria['firstName'])
                && !in_array($item->getFirstname(), $criteria['firstName'])
            ) {
                continue;
            }
            if (
                isset($criteria['lastname'])
                && !in_array($item->getLastname(), $criteria['lastname'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (
                isset($criteria['additionalType'])
                && !in_array($item->getAdditionalType(), $criteria['additionalType'])
            ) {
                continue;
            }
            if (
                isset($criteria['isVip'])
                && !in_array($item->getisVIP(), $criteria['isVip'])
            ) {
                continue;
            }
            if (
                isset($criteria['referralCode'])
                && !in_array($item->getReferralCode(), $criteria['referralCode'])
            ) {
                continue;
            }
            if (
                isset($criteria['gdprAccepted'])
                && !in_array($item->isGDPRAccepted(), $criteria['gdprAccepted'])
            ) {
                continue;
            }
            if (
                isset($criteria['gender'])
                && !in_array($item->getGender(), $criteria['gender'])
            ) {
                continue;
            }
            if (
                isset($criteria['honoricPrefix'])
                && !in_array($item->getHonoricPrefix(), $criteria['honoricPrefix'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\UserDocument[] $haystack
     */
    private function filterUserDocs(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['user'])
                && !in_array($item->getUser()->getId(), $criteria['user'])
            ) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array(
                    $item->getDocument()->getDescription(),
                    $criteria['description'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['tag'])
                && !in_array($item->getDocument()->getTag(), $criteria['tag'])
            ) {
                continue;
            }
            if (
                isset($criteria['filename'])
                && !in_array($item->getDocument()->getFileName(), $criteria['filename'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getDocument()->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['docId'])
                && !in_array($item->getDocument()->getId(), $criteria['docId'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\ContegoLog[] $haystack
     */
    private function filterContegoLog(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['rag']) && !in_array($item->getRAG(), $criteria['rag'])
            ) {
                continue;
            }
            if (
                isset($criteria['score'])
                && !in_array($item->getKycScore(), $criteria['score'])
            ) {
                continue;
            }
            if (
                isset($criteria['username'])
                && !in_array($item->getUser(), $criteria['username'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\UserClient[] $haystack
     */
    private function filterUserClients(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['alias'])
                && !in_array($item->getAlias(), $criteria['alias'])
            ) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array($item->getDescription(), $criteria['description'])
            ) {
                continue;
            }
            if (
                isset($criteria['user'])
                && !in_array($item->getUser()->getId(), $criteria['user'])
            ) {
                continue;
            }
            if (
                isset($criteria['identifer'])
                && !in_array(
                    $item->getClient()->getIdentifier(),
                    $criteria['identifer'],
                )
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\Document[] $haystack
     */
    private function filterDocuments(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['description'])
                && !in_array($item->getDescription(), $criteria['description'])
            ) {
                continue;
            }
            if (
                isset($criteria['tag']) && !in_array($item->getTag(), $criteria['tag'])
            ) {
                continue;
            }
            if (
                isset($criteria['filename'])
                && !in_array($item->getFileName(), $criteria['filename'])
            ) {
                continue;
            }
            if (
                isset($criteria['type'])
                && !in_array($item->getType(), $criteria['type'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\Transaction[] $haystack
     */
    private function filterTransactions(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\PaymentOrder[] $haystack
     */
    private function filterPaymentOrders(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['asset'])
                && !in_array($item->getAsset()->getId(), $criteria['asset'])
            ) {
                continue;
            }
            if (
                isset($criteria['status'])
                && !in_array($item->getStatus(), $criteria['status'])
            ) {
                continue;
            }
            if (
                isset($criteria['paymentType'])
                && !in_array($item->getPaymentType(), $criteria['paymentType'])
            ) {
                continue;
            }
            if (
                isset($criteria['scheduledFor'])
                && !in_array(
                    $item->getScheduledFor()->format('Y-m-d'),
                    $criteria['scheduledFor'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array($item->getDescription(), $criteria['description'])
            ) {
                continue;
            }
            if (
                isset($criteria['hasPayments'])
                && in_array($item->getPayments()->isEmpty(), $criteria['hasPayments'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\PaymentRequest[] $haystack
     */
    private function filterPaymentRequests(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['paymentOrder'])
                && !in_array(
                    $item->getPaymentOrder()->getId(),
                    $criteria['paymentOrder'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['status'])
                && !in_array($item->getStatus(), $criteria['status'])
            ) {
                continue;
            }
            if (
                isset($criteria['paymentType'])
                && !in_array(
                    $item->getPaymentOrder()->getPaymentType(),
                    $criteria['paymentType'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['scheduledFor'])
                && !in_array(
                    $item->getPaymentOrder()->getScheduledFor()->format('Y-m-d'),
                    $criteria['scheduledFor'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['payee'])
                && !in_array($item->getPayee()->getId(), $criteria['payee'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\TransferOrder[] $haystack
     */
    private function filterTransferOrders(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['asset'])
                && !in_array($item->getAsset()->getId(), $criteria['asset'])
            ) {
                continue;
            }
            if (
                isset($criteria['status'])
                && !in_array($item->getStatus(), $criteria['status'])
            ) {
                continue;
            }
            if (
                isset($criteria['scheduledFor'])
                && !in_array(
                    $item->getScheduledFor()->format('Y-m-d'),
                    $criteria['scheduledFor'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array($item->getDescription(), $criteria['description'])
            ) {
                continue;
            }
            if (
                isset($criteria['hasTransfers'])
                && in_array($item->getTransfers()->isEmpty(), $criteria['hasTransfers'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\TransferRequest[] $haystack
     */
    private function filterTransferRequests(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['transferOrder'])
                && !in_array(
                    $item->getTransferOrder()->getId(),
                    $criteria['transferOrder'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['status'])
                && !in_array($item->getStatus(), $criteria['status'])
            ) {
                continue;
            }
            if (
                isset($criteria['scheduledFor'])
                && !in_array(
                    $item->getTransferOrder()->getScheduledFor()->format('Y-m-d'),
                    $criteria['scheduledFor'],
                )
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array($item->getDescription(), $criteria['description'])
            ) {
                continue;
            }
            if (
                isset($criteria['debitWalletId'])
                && !in_array($item->getDebitWalletId(), $criteria['debitWalletId'])
            ) {
                continue;
            }
            if (
                isset($criteria['creditWalletId'])
                && !in_array($item->getCreditWalletId(), $criteria['creditWalletId'])
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    /**
     * @param \App\Entity\BankAccount[] $haystack
     */
    private function filterBankAccountRegistrations(
        array $haystack,
        array $criteria = [],
        bool $asId = false,
    ): array {
        $matches = [];
        foreach ($haystack as $item) {
            if (
                isset($criteria['status'])
                && !in_array($item->getStatus()->value, $criteria['status'])
            ) {
                continue;
            }
            if (isset($criteria['id']) && !in_array($item->getId(), $criteria['id'])) {
                continue;
            }
            if (
                isset($criteria['userId'])
                && !in_array($item->getUser()->getId(), $criteria['userId'])
            ) {
                continue;
            }
            if (
                isset($criteria['description'])
                && !in_array($item->getDescription(), $criteria['description'])
            ) {
                continue;
            }
            if (
                isset($criteria['accountType'])
                && !in_array($item->getAccountType()->value, $criteria['accountType'])
            ) {
                continue;
            }
            if (
                isset($criteria['accountNumber'])
                && !in_array($item->getAccountNumber(), $criteria['accountNumber'])
            ) {
                continue;
            }
            if (
                isset($criteria['bankIdentifierCode'])
                && !in_array(
                    $item->getBankIdentifierCode(),
                    $criteria['bankIdentifierCode'],
                )
            ) {
                continue;
            }
            if (
                isset($criteria['accountHolderType'])
                && !in_array(
                    $item->getAccountHolderType(),
                    $criteria['accountHolderType'],
                )
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $asId ? $this->convertToIds($matches) : $matches;
    }

    private function getAllOfType(string $entityClassName, bool $clear = false): array
    {
        if ($clear) {
            $this->entityManager->clear();
        }
        return $this->entityManager->getRepository($entityClassName)->findAll();
    }

    private function normaliseCriteria(array $criteria): array
    {
        /**
         * Ensure that each criteria is in array format for the filters
         * Allows criteria to be specified as string/numerical instead of array if singular
         */
        $normalisedCriteria = [];
        foreach ($criteria as $key => $value) {
            $normalisedCriteria[$key] = is_array($value) ? $value : [$value];
        }
        return $normalisedCriteria;
    }
}
