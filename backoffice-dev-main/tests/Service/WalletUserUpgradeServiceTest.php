<?php

namespace App\Tests\Service;

use App\Entity\Enum\WalletUserVersion;
use App\Entity\User;
use App\Service\MangopayWalletService;
use App\Service\WalletUserUpgradeService;
use MangoPay\UserNaturalSca;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WalletUserUpgradeServiceTest extends KernelTestCase
{
    private WalletUserUpgradeService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(WalletUserUpgradeService::class);
    }

    public function testUpgradeUserCategory(): void
    {
        $user = new User();
        $user->setMangoPayUserId('TestUserCategoryUpgrade');

        $mpUser = new UserNaturalSca($user->getMangoPayUserId());

        /** @var MangopayWalletService|\PHPUnit\Framework\MockObject\MockObject $mangopayWalletServiceMock */
        $mangopayWalletServiceMock = $this->createMock(MangopayWalletService::class);
        $mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getScaUser')
            ->willReturn($mpUser);
        $mangopayWalletServiceMock
            ->expects($this->once())
            ->method('updateScaUser')
            ->willReturn($mpUser);
        $this->service = new WalletUserUpgradeService(
            static::getContainer()->get(LoggerInterface::class),
            $mangopayWalletServiceMock,
        );

        $this->service->upgradeUserCategory($user);

        $this->assertEquals(
            WalletUserVersion::UserCategoryUpdate,
            $user->getWalletUserVersion(),
        );
    }
}
