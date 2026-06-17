<?php

namespace App\Tests\Service\Mapper;

use App\Dto\Portfolio\PortfolioPositionResponseDto;
use App\Dto\Portfolio\PortfolioResponseDto;
use App\Dto\Struct\Portfolio;
use App\Dto\Struct\PortfolioPosition;
use App\Entity\Asset;
use App\Entity\User;
use App\Service\Mapper\PortfolioMapper;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PortfolioMapperTest extends KernelTestCase
{
    private PortfolioMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PortfolioMapper::class);
    }

    public function testMapToDto(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset6->setName('Test Asset 6');
        $asset6->setNetProjectedYield('0.0489');
        $asset6->setTermStart(new \DateTime('first friday of -20 months'));
        $asset6->setInvestmentTerm(24);
        $asset76 = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset76->setName('Test Asset Seventy Six');
        $asset76->setNetProjectedYield('0.0427');
        $asset76->setTermStart(new \DateTime('first tuesday of -37 months'));
        $asset76->setInvestmentTerm(36);
        $asset154 = EntityIdTestUtil::setEntityId(new Asset(), 154);
        $asset154->setName('Test Asset One 5 Four');
        $asset154->setNetProjectedYield('0.0511');

        $input = new Portfolio(
            userId: $user->getId(),
            value: new Number('18337.00'),
            dividends: new Number('740.81'),
            capitalGains: new Number('546.96'),
            positions: [
                new PortfolioPosition(
                    asset: $asset6,
                    averagePrice: new Number('1.25'),
                    shares: new Number('2420'),
                    value: new Number('3025.00'),
                    dividends: new Number('151.89'),
                    capitalGains: new Number('606.40'),
                    buyShares: new Number('10000'),
                    buyValue: new Number('12500.00'),
                    sellShares: new Number('7580'),
                    sellValue: new Number('10081.40'),
                    sharesAvailable: new Number('420'),
                ),
                new PortfolioPosition(
                    asset: $asset76,
                    averagePrice: new Number('1.34'),
                    shares: new Number('0'),
                    value: new Number('0.00'),
                    dividends: new Number('588.92'),
                    capitalGains: new Number('-59.44'),
                    buyShares: new Number('10000'),
                    buyValue: new Number('13400.00'),
                    sellShares: new Number('10000'),
                    sellValue: new Number('13340.56'),
                    sharesAvailable: new Number('0'),
                ),
                new PortfolioPosition(
                    asset: $asset154,
                    averagePrice: new Number('2.64'),
                    shares: new Number('5800'),
                    value: new Number('15312.00'),
                    dividends: new Number('0.00'),
                    capitalGains: new Number('0.00'),
                    buyShares: new Number('5800'),
                    buyValue: new Number('15312.00'),
                    sellShares: new Number('0'),
                    sellValue: new Number('0.00'),
                    sharesAvailable: new Number('5800'),
                ),
            ],
        );

        $expected = new PortfolioResponseDto(
            userId: $user->getId(),
            value: new Number('18337.00'),
            dividends: new Number('740.81'),
            capitalGains: new Number('546.96'),
            positions: [
                new PortfolioPositionResponseDto(
                    assetId: '6',
                    assetName: $asset6->getName(),
                    assetYield: '4.89%',
                    // 24 months term, 20 WHOLE months elapsed, 3 WHOLE months (N-1) months remaining
                    assetTermRemaining: $asset6->getTermRemaining(),
                    averagePrice: new Number('1.25'),
                    shares: new Number('2420'),
                    value: new Number('3025.00'),
                    dividends: new Number('151.89'),
                    capitalGains: new Number('606.40'),
                    buyShares: new Number('10000'),
                    buyValue: new Number('12500.00'),
                    sellShares: new Number('7580'),
                    sellValue: new Number('10081.40'),
                    sharesAvailable: new Number('420'),
                ),
                new PortfolioPositionResponseDto(
                    assetId: '76',
                    assetName: $asset76->getName(),
                    assetYield: '4.27%',
                    // Asset term concluded and exited, hence fully divested
                    assetTermRemaining: '0',
                    averagePrice: new Number('1.34'),
                    shares: new Number('0'),
                    value: new Number('0.00'),
                    dividends: new Number('588.92'),
                    capitalGains: new Number('-59.44'),
                    buyShares: new Number('10000'),
                    buyValue: new Number('13400.00'),
                    sellShares: new Number('10000'),
                    sellValue: new Number('13340.56'),
                    sharesAvailable: new Number('0'),
                ),
                new PortfolioPositionResponseDto(
                    assetId: '154',
                    assetName: $asset154->getName(),
                    assetYield: '5.11%',
                    // Asset term info missing, defaults to 0
                    assetTermRemaining: '0',
                    averagePrice: new Number('2.64'),
                    shares: new Number('5800'),
                    value: new Number('15312.00'),
                    dividends: new Number('0.00'),
                    capitalGains: new Number('0.00'),
                    buyShares: new Number('5800'),
                    buyValue: new Number('15312.00'),
                    sellShares: new Number('0'),
                    sellValue: new Number('0.00'),
                    sharesAvailable: new Number('5800'),
                ),
            ],
        );

        $actual = $this->service->mapToDto($input);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testMapToDtoEmpty(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);

        $expected = new PortfolioResponseDto(
            userId: $user->getId(),
            value: new Number('0.00'),
            dividends: new Number('0.00'),
            capitalGains: new Number('0.00'),
            positions: [],
        );

        $actual = $this->service->mapToDto(new Portfolio($user->getId()));
        $this->assertEquals($expected, $actual);
        $this->assertSame(json_encode($expected), json_encode($actual));
    }
}
