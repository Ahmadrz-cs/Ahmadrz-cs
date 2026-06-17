<?php

namespace App\Tests\Service;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\Enum\MangopayBlockActionCode;
use App\Entity\KycReport;
use App\Entity\KycReview;
use App\Entity\User;
use App\Repository\KycReviewRepository;
use App\Service\ContegoKycService;
use App\Service\KycReportService;
use App\Service\MangopayKycService;
use App\Service\NotificationService;
use App\Test\Util\EntityIdTestUtil;
use MangoPay\PersonType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class KycReportServiceTest extends KernelTestCase
{
    private KycReportService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(KycReportService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('similarKycReportProvider')]
    public function testIsSimilarReport(
        bool $expected,
        KycReport $input1,
        KycReport $input2,
    ): void {
        $actual = $this->service->isSimilarReport($input1, $input2);
        $this->assertSame($expected, $actual);
    }

    public static function similarKycReportProvider(): \Generator
    {
        $user1 = EntityIdTestUtil::setEntityId(new User(), 4141);
        $user1->setMangoPayUserId('testMpUser1');
        $user2 = EntityIdTestUtil::setEntityId(new User(), 7781);
        $user2->setMangoPayUserId('testMpUser2');
        $mangoNatLightDayOld = new KycReport(
            $user1,
            MangopayKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            PersonType::Natural,
            'LIGHT',
            '0',
            false,
            new \DateTime('-1 day'),
            'notes1',
        );
        $mangoNatLightWeekOld = new KycReport(
            $user1,
            MangopayKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            PersonType::Natural,
            'LIGHT',
            '0',
            false,
            new \DateTime('-1 week'),
            'notes2',
        );
        $mangoNatLightDayOld2 = new KycReport(
            $user2,
            MangopayKycService::PROVIDER_NAME,
            $user2->getMangoPayUserId(),
            PersonType::Natural,
            'LIGHT',
            '0',
            false,
            new \DateTime('-1 day'),
            'notes1',
        );
        $contego = new KycReport(
            $user1,
            ContegoKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            PersonType::Natural,
            'LIGHT',
            '0',
            false,
            new \DateTime('-1 day'),
            'notes1',
        );
        $mangoRegStatus = new KycReport(
            $user1,
            MangopayKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            'REGULATORY_STATUS',
            'LIGHT',
            '0',
            false,
            new \DateTime('-1 day'),
            'notes1',
        );
        $mangoNatRegularDayOld = new KycReport(
            $user1,
            MangopayKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            PersonType::Natural,
            'REGULAR',
            '0',
            false,
            new \DateTime('-1 day'),
            'notes1',
        );
        $mangoNatLightScore1DayOld = new KycReport(
            $user1,
            MangopayKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            PersonType::Natural,
            'LIGHT',
            '1',
            false,
            new \DateTime('-1 day'),
            'notes1',
        );
        $mangoNatLightVerifiedDayOld = new KycReport(
            $user1,
            MangopayKycService::PROVIDER_NAME,
            $user1->getMangoPayUserId(),
            PersonType::Natural,
            'LIGHT',
            '0',
            true,
            new \DateTime('-1 day'),
            'notes1',
        );

        yield 'Identical' => [
            true,
            $mangoNatLightDayOld,
            $mangoNatLightDayOld,
        ];
        yield 'Diff date time and notes' => [
            true,
            $mangoNatLightDayOld,
            $mangoNatLightWeekOld,
        ];
        yield 'Diff user' => [false, $mangoNatLightDayOld, $mangoNatLightDayOld2];
        yield 'Diff provider' => [false, $mangoNatLightDayOld, $contego];
        yield 'Diff checkType' => [false, $mangoNatLightDayOld, $mangoRegStatus];
        yield 'Diff result' => [false, $mangoNatLightDayOld, $mangoNatRegularDayOld];
        yield 'Diff score' => [false, $mangoNatLightDayOld, $mangoNatLightScore1DayOld];
        yield 'Diff outcome' => [
            false,
            $mangoNatLightDayOld,
            $mangoNatLightVerifiedDayOld,
        ];
    }
}
