<?php

namespace App\Tests\Service\Porting;

use App\Entity\Asset;
use App\Entity\Document;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\OfferingDocuments;
use App\Entity\OfferingStatus;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Porting\OfferingPorter;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OfferingPorterTest extends KernelTestCase
{
    private OfferingPorter $service;

    private UserRepository|MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('portOfferingGenerator')]
    public function testPortOffering(
        TradeOrder $expected,
        Offering $input,
        bool $superadminSearch,
    ): void {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        $this->service = static::getContainer()->get(OfferingPorter::class);

        $idFindCount = $superadminSearch ? 0 : 1;
        $roleFindCount = $superadminSearch ? 1 : 0;
        // Check how the seller is being retrieved
        $this->userRepositoryMock
            ->expects(self::exactly($idFindCount))
            ->method('find')
            ->with($input->getCreatedById())
            ->willReturn($expected->getUser());
        $this->userRepositoryMock
            ->expects(self::exactly($roleFindCount))
            ->method('findByRole')
            ->with('ROLE_SUPER_ADMIN')
            ->willReturn([$expected->getUser()]);

        $actual = $this->service->portOffering($input);

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);

        // Check offering has relation set
        $this->assertEquals($input->getTradeOrder(), $actual);
    }

    public static function portOfferingGenerator(): \Generator
    {
        // Some common entities
        $assetFull = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $assetFull->setAmountOfShares(85709);
        $assetFull->setPricePerShare('4.72');
        $assetFull->setFundingGoal('404546.48');

        $assetNoPrice = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $assetNoPrice->setAmountOfShares(85709);
        $assetNoPrice->setFundingGoal('404546.48');

        $seller = EntityIdTestUtil::setEntityId(new User(), 412);

        $activeStatus = new OfferingStatus();
        $activeStatus->setSubmittedOn(new \DateTime('2022-05-14 15:06:08'));
        $activeStatus->setPublishedOn(new \DateTime('2022-05-21 12:55:12'));

        $cancelledStatus = new OfferingStatus();
        $cancelledStatus->setLifecycleStatus(OfferingLifecycle::STATE_CANCELLED);
        $cancelledStatus->setSubmittedOn(new \DateTime('2022-01-14 15:06:08'));
        $cancelledStatus->setPublishedOn(new \DateTime('2022-01-21 12:55:12'));
        $cancelledStatus->setCancelledOn(new \DateTime('2022-02-22 09:11:56'));

        $justPublished = new OfferingStatus();
        $justPublished->setPublishedOn(new \DateTime('2022-05-21 12:55:12'));

        $justSubmitted = new OfferingStatus();
        $justSubmitted->setSubmittedOn(new \DateTime('2022-05-21 12:41:17'));

        $investment1 = EntityIdTestUtil::setEntityId(new Investment(), 1441);
        $investment1->setCreatedAt(new \DateTime('2022-06-22 11:54:55'));
        // Note investment 2 is BEFORE the submittedOn and publishedOn dates besides the cancelled on
        $investment2 = EntityIdTestUtil::setEntityId(new Investment(), 1423);
        $investment2->setCreatedAt(new \DateTime('2022-03-22 11:06:32'));
        $investment3 = EntityIdTestUtil::setEntityId(new Investment(), 1476);
        $investment3->setCreatedAt(new \DateTime('2022-08-11 16:23:44'));

        // Start of scenario 1
        $firstPartyOfferingActive = EntityIdTestUtil::setEntityId(new Offering(), 178);
        $firstPartyOfferingActive->setAsset($assetFull);
        $firstPartyOfferingActive->setIsSecondaryMrkt(1);
        $firstPartyOfferingActive->setFundingGoal($assetFull->getFundingGoal());
        $firstPartyOfferingActive->setStatus($activeStatus);
        $firstPartyOfferingActive->setCreatedAt(new \DateTime('2022-04-12 12:35:15'));

        $reflection = new \ReflectionClass($firstPartyOfferingActive);
        $partialRaisedProperty = $reflection->getProperty('raised_percent');
        $partialRaisedProperty->setValue($firstPartyOfferingActive, random_int(0, 99));

        $firstPartyOfferingActive->addInvestment($investment1);
        $firstPartyOfferingActive->addInvestment($investment2);
        $firstPartyOfferingActive->addInvestment($investment3);

        $firstPartyFullActive = new TradeOrder(
            TradeDirection::Sell,
            $assetFull,
            $seller,
            $assetFull->getAmountOfShares(),
            new Number($assetFull->getPricePerShare()),
            TradeOrderType::Initial,
        );
        $firstPartyFullActive->setCreatedAt($firstPartyOfferingActive->getCreatedAt());
        $firstPartyFullActive->setCreatedBy($seller);
        $firstPartyFullActive->setNotes('port:o178');
        $activeLog = new TradeOrderStatusLog(
            $firstPartyFullActive,
            TradeOrderStatus::Active,
            $investment2->getCreatedAt(),
        );
        $firstPartyFullActive->addStatusLog($activeLog);

        /**
         * Scenario 1
         * - Asset share price is set and used (offering share price not set)
         * - Offering is 100% of the asset's funding goal
         * - Has 3 investments, won't be ordered by createdAt
         * - Offering is not bought out yet
         * - Offering is missing createdById - so will fallback to asking for superadmin
         * - Offering has no min-max
         */
        yield 'First party missing createdById asset share price' => [
            $firstPartyFullActive,
            $firstPartyOfferingActive,
            true,
        ];

        $firstPartyOfferingPartialCompleted = EntityIdTestUtil::setEntityId(
            new Offering(),
            541,
        );
        $firstPartyOfferingPartialCompleted->setAsset($assetNoPrice);
        $firstPartyOfferingPartialCompleted->setIsSecondaryMrkt(1);
        $firstPartyOfferingPartialCompleted->setFundingGoal('198240'); // 42k shares
        $firstPartyOfferingPartialCompleted->setCreatedById(3124); // not the "superadmin" seller
        $firstPartyOfferingPartialCompleted->setStatus($justPublished);
        $firstPartyOfferingPartialCompleted->setMinCommitUser(250);
        $firstPartyOfferingPartialCompleted->setCreatedAt(
            new \DateTime('2022-04-12 12:35:15'),
        );

        $reflection = new \ReflectionClass($firstPartyOfferingPartialCompleted);
        $fullyRaisedProperty = $reflection->getProperty('raised_percent');
        $fullyRaisedProperty->setValue($firstPartyOfferingPartialCompleted, 100);

        $firstPartyOfferingPartialCompleted->addInvestment($investment1);
        $firstPartyOfferingPartialCompleted->addInvestment($investment2);
        $firstPartyOfferingPartialCompleted->addInvestment($investment3);

        $firstPartyPartialCompleted = new TradeOrder(
            TradeDirection::Sell,
            $assetNoPrice,
            $seller,
            42000,
            new Number('4.72'), // should be the same as the assetFull for simplicity
            TradeOrderType::Initial,
        );
        // £250 on share price of 4.72 == 52.9... == round up to 53
        $firstPartyPartialCompleted->setMinimumShares(53);
        $firstPartyPartialCompleted->setCreatedAt($firstPartyOfferingPartialCompleted->getCreatedAt());
        $firstPartyPartialCompleted->setCreatedBy($seller);
        $firstPartyPartialCompleted->setNotes('port:o541');

        $activeLog2 = new TradeOrderStatusLog(
            $firstPartyPartialCompleted,
            TradeOrderStatus::Active,
            $investment2->getCreatedAt(),
        );
        $firstPartyPartialCompleted->addStatusLog($activeLog2);
        $completeLog1 = new TradeOrderStatusLog(
            $firstPartyPartialCompleted,
            TradeOrderStatus::Completed,
            $investment3->getCreatedAt(),
        );
        $firstPartyPartialCompleted->addStatusLog($completeLog1);

        /**
         * Scenario 2
         * - Asset share price is not set so must be derived (offering share price not set)
         * - Offering is only part of the asset's total funding goal
         * - Offering is a relisting
         * - Has 3 investments, won't be ordered by createdAt
         * - Offering is fully funded
         * - Offering is has a createdById set
         * - Offering only has min commit set
         * - Has a non superadmin createdById - should be ignored for first party
         */
        yield 'First party derived share price completed' => [
            $firstPartyPartialCompleted,
            $firstPartyOfferingPartialCompleted,
            true,
        ];

        $relistedOfferingCancelled = EntityIdTestUtil::setEntityId(
            new Offering(),
            4827,
        );
        $relistedOfferingCancelled->setAsset($assetNoPrice);
        $relistedOfferingCancelled->setIsSecondaryMrkt(1);
        $relistedOfferingCancelled->setSellInvestment($investment2);
        $relistedOfferingCancelled->setFundingGoal('3638.04'); // 854 shares
        $relistedOfferingCancelled->setPricePerShare('4.26');
        $relistedOfferingCancelled->setCreatedById($seller->getId());
        $relistedOfferingCancelled->setStatus($cancelledStatus);
        $relistedOfferingCancelled->setMinCommitUser(100);
        $relistedOfferingCancelled->setMaxCommitUser(500);
        $relistedOfferingCancelled->setCreatedAt(new \DateTime('2022-12-12 12:00:08'));

        $relistedOfferingCancelled->addInvestment($investment3);

        $relistedCancelled = new TradeOrder(
            TradeDirection::Sell,
            $assetNoPrice,
            $seller,
            854,
            new Number('4.26'), // Less than original
            TradeOrderType::Market,
        );
        // £100 on share price of 4.26 == 23.47... == round up to 24
        $relistedCancelled->setMinimumShares(24);
        // £500 on share price of 4.26 == 117.37... == round up to 118
        $relistedCancelled->setMaximumShares(118);
        $relistedCancelled->setCreatedAt($relistedOfferingCancelled->getCreatedAt());
        $relistedCancelled->setCreatedBy($seller);
        $relistedCancelled->setNotes('port:o4827');

        $activeLog3 = new TradeOrderStatusLog(
            $relistedCancelled,
            TradeOrderStatus::Active,
            $cancelledStatus->getPublishedOn(),
        );
        $relistedCancelled->addStatusLog($activeLog3);
        $cancelLog = new TradeOrderStatusLog(
            $relistedCancelled,
            TradeOrderStatus::Cancelled,
            $cancelledStatus->getCancelledOn(),
        );
        $relistedCancelled->addStatusLog($cancelLog);

        /**
         * Scenario 3
         * - Offering has share price so will be used
         * - Offering has been cancelled
         * - Offering has a different share price to the asset
         * - Offering has a min and max
         * - 1 investment
         * - Published date is earlier than first investment (as it should be)
         */
        yield 'Relisting cancelled mix-max' => [
            $relistedCancelled,
            $relistedOfferingCancelled,
            false,
        ];

        $relistedOfferingPending = EntityIdTestUtil::setEntityId(new Offering(), 3368);
        $relistedOfferingPending->setAsset($assetNoPrice);
        $relistedOfferingPending->setIsSecondaryMrkt(1);
        $relistedOfferingPending->setSellInvestment($investment2);
        $relistedOfferingPending->setFundingGoal('4363.94'); // 854 shares
        $relistedOfferingPending->setPricePerShare('5.11');
        $relistedOfferingPending->setCreatedById($seller->getId());
        $relistedOfferingPending->setStatus($justSubmitted);
        $relistedOfferingPending->setMaxCommitUser(500);
        $relistedOfferingPending->setCreatedAt(new \DateTime('2022-12-12 12:00:08'));

        $relistedOfferingPending->addInvestment($investment3);

        $relistedPending = new TradeOrder(
            TradeDirection::Sell,
            $assetNoPrice,
            $seller,
            854,
            new Number('5.11'), // More than original
            TradeOrderType::Market,
        );
        // £500 on share price of 5.11 == 97.84... == round up to 98
        $relistedPending->setMaximumShares(98);
        $relistedPending->setCreatedAt($relistedOfferingPending->getCreatedAt());
        $relistedPending->setCreatedBy($seller);
        $relistedPending->setNotes('port:o3368');

        $cancelLog2 = new TradeOrderStatusLog(
            $relistedPending,
            TradeOrderStatus::Cancelled,
            new \DateTime()->setTime(0, 0, 0),
        );
        $relistedPending->addStatusLog($cancelLog2);

        /**
         * Scenario 4
         * - Unpublished relisted offering that will be auto-cancelled
         * - Cancelled on date will be start of current day
         * - Offering only has max commit set
         *
         */
        yield 'Relisting unpublished cancelled' => [
            $relistedPending,
            $relistedOfferingPending,
            false,
        ];
    }

    public function testPortOfferingDocument(): void
    {
        $this->service = static::getContainer()->get(OfferingPorter::class);
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 441);
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 1476);
        $offering->setAsset($asset);
        $offeringDocument = EntityIdTestUtil::setEntityId(
            new OfferingDocuments(),
            5867,
        );
        $offeringDocument->setOffering($offering);
        $offeringDocument->setCreatedAt(new \DateTime('2024-09-17 18:14:25'));
        $offeringDocument->setCreatedBy(
            'Test offdoc porting creator' . bin2hex(random_bytes(6)),
        );
        $document = EntityIdTestUtil::setEntityId(new Document(), 23845);
        $document->setTag('Test offdoc porting' . bin2hex(random_bytes(6)));
        $document->setDocumentUrl('Test offdoc porting url' . bin2hex(random_bytes(6)));
        $offeringDocument->setDocument($document);

        $actual = $this->service->portOfferingDocument($offeringDocument);

        $this->assertEquals($document, $actual->getDocument());
        $this->assertEquals($asset, $actual->getAsset());
        $this->assertEquals($offeringDocument->getCreatedAt(), $actual->getCreatedAt());
        $this->assertEquals($offeringDocument->getCreatedBy(), $actual->getCreatedBy());
        // CreatedById field used as an indicator of porting complete
        $this->assertEquals($asset->getId(), $offeringDocument->getCreatedById());
    }
}
