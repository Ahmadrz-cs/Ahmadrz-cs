<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\Communication;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\Payout;
use App\Entity\User;
use App\Repository\CommunicationRepository;
use App\Service\Manager\PayoutManager;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class PayoutManagerTest extends KernelTestCase
{
    private PayoutManager $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PayoutManager::class);
    }

    private $testQueryParams = [
        'offest' => '0',
        'limit' => '1',
        'sort' => '+id,-updatedAt',
        'id' => '1,3,4',
        'status' => '4,5',
        'type' => '0,1',
        'term' => '1,3,5',
        'biscuits' => 'digestive,shortbread',
        'user' => 'whodat,whatder',
    ];

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetCriteria(): void
    {
        $expected = ['id', 'payoutType'];

        // only return allowed criteria
        $actual = $this->service->getCriteria($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass default type as null - may want to improve it to handle empty arrays
        $actual = $this->service->getCriteria(['type' => null]);
        $this->assertEmpty($actual);
    }
}
