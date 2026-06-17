<?php

namespace App\Tests\Service\Mapper;

use App\Dto\Payout\PayoutResponseDto;
use App\Entity\Asset;
use App\Entity\Enum\PayoutType;
use App\Entity\Payout;
use App\Entity\User;
use App\Service\Mapper\PayoutMapper;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PayoutMapperTest extends KernelTestCase
{
    private PayoutMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PayoutMapper::class);
    }

    public function testMapToDto(): void
    {
        $payee = EntityIdTestUtil::setEntityId(new User(), 6615);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset6->setName('Test Asset 6');
        $asset6->setNetProjectedYield('0.0489');

        $input = EntityIdTestUtil::setEntityId(new Payout(), 215781);
        $input->setPayoutType(51);
        $input->setCreditedUser($payee);
        $input->setAsset($asset6);
        $input->setCreatedAt(new \DateTime('2024-05-18 17:15:56'));
        $input->setUpdatedAt(new \DateTime('2024-06-02 12:55:12'));

        // Check fallback behaviour
        $expected = new PayoutResponseDto(
            id: '215781',
            userId: '6615',
            assetId: '6',
            assetName: 'Test Asset 6',
            shares: new Number('0'),
            value: new Number('0.00'),
            type: null,
            createdAt: $input->getCreatedAt(),
            updatedAt: $input->getUpdatedAt(),
        );
        $actual = $this->service->mapToDto($input);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        $this->assertSame(json_encode($expected), json_encode($actual));

        // Check fully filled example
        $input->setShareholding(8512);
        $input->setPayoutAmount('27.89');
        $input->setPayoutType(PayoutType::Dividend->value);

        $expected = new PayoutResponseDto(
            id: '215781',
            userId: '6615',
            assetId: '6',
            assetName: 'Test Asset 6',
            shares: new Number('8512'),
            value: new Number('27.89'),
            type: PayoutType::Dividend,
            createdAt: $input->getCreatedAt(),
            updatedAt: $input->getUpdatedAt(),
        );

        $actual = $this->service->mapToDto($input);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        $this->assertSame(json_encode($expected), json_encode($actual));
    }
}
