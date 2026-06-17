<?php

namespace App\Tests\Service\Mapper;

use App\Dto\ShareTrade\ShareTradeResponseDto;
use App\Entity\Asset;
use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Service\Mapper\ShareTradeMapper;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShareTradeMapperTest extends KernelTestCase
{
    private ShareTradeMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ShareTradeMapper::class);
    }

    public function testMapToDto(): void
    {
        $seller = EntityIdTestUtil::setEntityId(new User(), 8791);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 6615);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset6->setName('Test Asset 6');
        $asset6->setNetProjectedYield('0.0489');

        $sellOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset6,
            user: $seller,
            type: TradeOrderType::Initial,
        );
        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset6,
            user: $buyer,
            type: TradeOrderType::Market,
        );
        $input = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder,
                sellOrder: $sellOrder,
                numberOfShares: 804,
                pricePerShare: new Number('3.77'),
            ),
            27768,
        );
        $input->setCreatedAt(new \DateTime('2024-05-18 17:15:56'));
        $input->setUpdatedAt(new \DateTime('2024-06-02 12:55:12'));

        $statusLog = new ShareTradeStatusLog(
            status: TradeStatus::Suspended,
            occuredAt: new \DateTime('2024-06-02 12:55:12'),
        );
        $input->addStatusLog($statusLog);

        $expected = new ShareTradeResponseDto(
            id: '27768',
            uuid: $input->getUuid(),
            assetId: '6',
            assetName: 'Test Asset 6',
            sellerId: '8791',
            buyerId: '6615',
            pricePerShare: new Number('3.770000'),
            numberOfShares: new Number(804 ?? 0),
            tradeValue: new Number('3031.08'),
            status: TradeStatus::Suspended,
            statusOccuredAt: new \DateTime('2024-06-02 12:55:12'),
            type: ShareTradeType::FirstParty,
            createdAt: $input->getCreatedAt(),
            updatedAt: $input->getUpdatedAt(),
        );

        $actual = $this->service->mapToDto($input);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        $this->assertSame(json_encode($expected), json_encode($actual));
    }
}
