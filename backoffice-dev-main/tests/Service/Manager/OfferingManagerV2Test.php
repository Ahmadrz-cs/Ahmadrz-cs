<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Service\Manager\OfferingManagerV2;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OfferingManagerV2Test extends KernelTestCase
{
    private OfferingManagerV2 $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(OfferingManagerV2::class);
    }

    public function testSetSharedFields(): void
    {
        $asset = new Asset();
        $asset->setName('Test Associated Asset');
        $asset->setPricePerShare(5.29);
        $asset->setAmountOfShares(84248);
        $asset->setInvestmentTerm(48);
        $sample = new Offering();
        $sample->setAsset($asset);
        $actual = $this->service->setSharedFields($sample);

        $this->assertEquals($asset->getName(), $actual->getName());
        $this->assertEquals($asset->getPricePerShare(), $actual->getPricePerShare());
        $this->assertEquals($asset->getAmountOfShares(), $actual->getNoOfShares());
        $this->assertEquals(4, $actual->getOfferingTerm());
    }

    public function testSetSharedFieldsNoAsset(): void
    {
        $actual = $this->service->setSharedFields(new Offering());

        $this->assertEmpty($actual->getName());
        $this->assertEmpty($actual->getPricePerShare());
        $this->assertEmpty($actual->getNoOfShares());
        $this->assertEmpty($actual->getOfferingTerm());
    }

    public function testSetFundingGoal(): void
    {
        $sample = new Offering();
        $actual = $this->service->setFundingGoal($sample);

        $this->assertIsNumeric($actual->getFundingGoal());

        $sample->setNoOfShares(12500);
        $sample->setPricePerShare(1.86);
        $actual = $this->service->setFundingGoal($sample);

        $this->assertEquals(12500 * 1.86, $actual->getFundingGoal());
    }

    public static function investmentClassificationProvider(): \Generator
    {
        yield 'Empty offering' => [0, 0, new Offering()];

        $offering = new Offering();
        $offering->setPricePerShare(1.87);
        $offering->setMinCommitUser(256);
        yield 'Min only' => [256.19, 0, $offering];

        $offering = new Offering();
        $offering->setPricePerShare(1.87);
        $offering->setMaxCommitUser(10429);
        yield 'Max only' => [0, 10428.99, $offering];

        $offering = new Offering();
        $offering->setPricePerShare(1.87);
        $offering->setMinCommitUser(256);
        $offering->setFundingGoal(18700);
        yield 'Min and goal' => [256.19, 18700, $offering];

        $offering = new Offering();
        $offering->setPricePerShare(1.87);
        $offering->setMinCommitUser(256);
        $offering->setMaxCommitUser(10429);
        yield 'Min and max' => [256.19, 10428.99, $offering];

        $offering = new Offering();
        $offering->setPricePerShare(1.87);
        $offering->setMinCommitUser(323.51);
        $offering->setMaxCommitUser(10496.31);
        yield 'Min and max exact' => [323.51, 10496.31, $offering];

        $offering = new Offering();
        $offering->setPricePerShare(1.87);
        $offering->setMinCommitUser(12000);
        $offering->setMaxCommitUser(10496.31);
        yield 'Min greater than max' => [12001.66, 12001.66, $offering];

        $offering = new Offering();
        $offering->setMinCommitUser(256);
        $offering->setMaxCommitUser(10429);
        yield 'Missing share price' => [256, 10429, $offering];

        $offering = new Offering();
        $offering->setPricePerShare('0.00');
        $offering->setMinCommitUser(256);
        $offering->setMaxCommitUser(10429);
        yield 'Zero string share price' => [256, 10429, $offering];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentClassificationProvider')]
    public function testRoundMinMaxCommit(
        ?float $expectedMin,
        ?float $expectedMax,
        Offering $offering,
    ): void {
        $actual = $this->service->roundMinMaxCommit($offering);

        $this->assertEquals($expectedMin, $actual->getMinCommitUser());
        $this->assertEquals($expectedMax, $actual->getMaxCommitUser());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringStatusTransitionProvider')]
    public function testTransitionToPublished(
        string $startStatus,
        string $expected,
    ): void {
        $offering = new Offering();
        $offering->setLifecycleStatus($startStatus);
        $actual = $this->service->transitionToPublished($offering);
        $this->assertEquals($expected, $actual->getLifecycleStatus());
        // Check gradual transitions if from earlier stages
        if (OfferingLifecycle::STATE_DRAFT == $startStatus) {
            $this->assertNotNull($offering->getStatus()->getSubmittedOn());
            $this->assertNotNull($offering->getStatus()->getApprovedOn());
            $this->assertNotNull($offering->getStatus()->getPublishedOn());
            $this->assertTrue($offering->getStatus()->getIsPublished());
        }
        if (OfferingLifecycle::STATE_SUBMITTED == $startStatus) {
            $this->assertNotNull($offering->getStatus()->getSubmittedOn());
            $this->assertNotNull($offering->getStatus()->getApprovedOn());
            $this->assertNotNull($offering->getStatus()->getPublishedOn());
            $this->assertTrue($offering->getStatus()->getIsPublished());
        }
        if (OfferingLifecycle::STATE_APPROVED == $startStatus) {
            $this->assertNotNull($offering->getStatus()->getApprovedOn());
            $this->assertNotNull($offering->getStatus()->getPublishedOn());
            $this->assertTrue($offering->getStatus()->getIsPublished());
        }
    }

    public static function offeringStatusTransitionProvider(): \Generator
    {
        yield 'draft' => [
            OfferingLifecycle::STATE_DRAFT,
            OfferingLifecycle::STATE_PUBLISHED,
        ];
        yield 'submitted' => [
            OfferingLifecycle::STATE_SUBMITTED,
            OfferingLifecycle::STATE_PUBLISHED,
        ];
        yield 'approved' => [
            OfferingLifecycle::STATE_APPROVED,
            OfferingLifecycle::STATE_PUBLISHED,
        ];
        yield 'published' => [
            OfferingLifecycle::STATE_PUBLISHED,
            OfferingLifecycle::STATE_PUBLISHED,
        ];

        yield 'rejected' => [
            OfferingLifecycle::STATE_REJECTED,
            OfferingLifecycle::STATE_REJECTED,
        ];
        yield 'restricted' => [
            OfferingLifecycle::STATE_RESTRICTED,
            OfferingLifecycle::STATE_RESTRICTED,
        ];
        yield 'live' => [OfferingLifecycle::STATE_LIVE, OfferingLifecycle::STATE_LIVE];
        yield 'closed' => [
            OfferingLifecycle::STATE_CLOSED,
            OfferingLifecycle::STATE_CLOSED,
        ];
        yield 'settled' => [
            OfferingLifecycle::STATE_SETTELED,
            OfferingLifecycle::STATE_SETTELED,
        ];
        yield 'cancelled' => [
            OfferingLifecycle::STATE_CANCELLED,
            OfferingLifecycle::STATE_CANCELLED,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentOutcomeProvider')]
    public function testProcessPaymentOutcome(
        bool $success,
        string $startStatus,
        string $endStatus,
    ): void {
        $offering = new Offering();
        $offering->setLifecycleStatus($startStatus);
        $this->service->processPaymentOutcome($offering, $success);
        $this->assertEquals($endStatus, $offering->getLifecycleStatus());
    }

    public static function paymentOutcomeProvider(): \Generator
    {
        yield 'success draft to submit' => [
            true,
            OfferingLifecycle::STATE_DRAFT,
            OfferingLifecycle::STATE_SUBMITTED,
        ];
        yield 'fail draft to cancel' => [
            false,
            OfferingLifecycle::STATE_DRAFT,
            OfferingLifecycle::STATE_CANCELLED,
        ];
        yield 'success but already submitted' => [
            true,
            OfferingLifecycle::STATE_SUBMITTED,
            OfferingLifecycle::STATE_SUBMITTED,
        ];
        yield 'success but already cancelled' => [
            true,
            OfferingLifecycle::STATE_CANCELLED,
            OfferingLifecycle::STATE_CANCELLED,
        ];
        yield 'fail but already submitted' => [
            false,
            OfferingLifecycle::STATE_SUBMITTED,
            OfferingLifecycle::STATE_SUBMITTED,
        ];
        yield 'fail but already cancelled' => [
            false,
            OfferingLifecycle::STATE_CANCELLED,
            OfferingLifecycle::STATE_CANCELLED,
        ];
    }
}
