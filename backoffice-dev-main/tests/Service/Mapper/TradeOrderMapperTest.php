<?php

namespace App\Tests\Service\Mapper;

use App\Dto\TradeOrder\TradeOrderResponseDto;
use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Service\Mapper\TradeOrderMapper;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TradeOrderMapperTest extends KernelTestCase
{
    private TradeOrderMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(TradeOrderMapper::class);
    }

    public function testMapToDto(): void
    {
        $seller = EntityIdTestUtil::setEntityId(new User(), 8791);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset6->setName('Test Asset 6');
        $asset6->setNetProjectedYield('0.0489');

        $input = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset6,
            user: $seller,
            numberOfShares: 8500,
            pricePerShare: new Number('2.75'),
            type: TradeOrderType::Initial,
        );
        $input->setMinimumShares(40);
        $input->setMaximumShares(4500);
        $input->setFees('45.80');
        $input->setTaxes('20');
        $input->setSharesTraded(9000); // greater than shares available
        $input->setCreatedAt(new \DateTime('2024-05-18 17:15:56'));
        $input->setUpdatedAt(new \DateTime('2024-06-02 12:55:12'));
        $input->setNotes('Test mapping to Dto ' . bin2hex(random_bytes(6)));
        $input = EntityIdTestUtil::setEntityId($input, 7462);

        $statusLog = new TradeOrderStatusLog(
            status: TradeOrderStatus::Suspended,
            occuredAt: new \DateTime('2024-06-02 12:55:12'),
        );
        $input->addStatusLog($statusLog);

        $expected = new TradeOrderResponseDto(
            id: '7462',
            uuid: $input->getUuid(),
            assetId: '6',
            assetName: 'Test Asset 6',
            userId: '8791',
            pricePerShare: new Number('2.750000'),
            numberOfShares: 8500,
            minimumShares: 40,
            maximumShares: 4500,
            sharesTraded: 9000,
            sharesAvailable: 0, // lower bound to 0
            status: TradeOrderStatus::Suspended,
            statusOccuredAt: new \DateTime('2024-06-02 12:55:12'),
            direction: TradeDirection::Sell,
            fees: new Number('45.80'),
            taxes: new Number('20.00'),
            notes: $input->getNotes(),
            type: TradeOrderType::Initial,
            createdAt: $input->getCreatedAt(),
            updatedAt: $input->getUpdatedAt(),
        );

        $actual = $this->service->mapToDto($input);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        $this->assertSame(json_encode($expected), json_encode($actual));

        // Change shares traded back to amount less than listed
        $input->setSharesTraded(1000);
        $actual = $this->service->mapToDto($input);
        $this->assertEquals(1000, $actual->sharesTraded);
        $this->assertEquals(7500, $actual->sharesAvailable);
    }
}
