<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\User;
use App\Service\Manager\PayoutManagerV2;
use App\Service\MangoPay;
use App\Test\FixtureTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PayoutManagerV2Test extends FixtureTestCase
{
    private PayoutManagerV2 $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(PayoutManagerV2::class);
    }

    public function testGetSuperadminWalletIdRepeat(): void
    {
        // Performance optimisations on subsequent calls in same kernel instance
        // ~18ms for db call, ~1.6us (microseconds) for in memory property retrieval
        // ~4 orders of magnitude (10^4) faster
        // Note that if the service is not cached, it takes around 10us
        // as the service needs to be discovered from the container again
        // Still ~3 orders of magnitude faster
        $t1 = hrtime(true);
        $id = $this->service->getSuperAdminAuthId();
        $t2 = hrtime(true);
        $id = $this->service->getSuperAdminAuthId();
        $t3 = hrtime(true);
        $this->assertNotEmpty($id);
        $this->assertLessThan($t2 - $t1, $t3 - $t2);
        // Uncomment to observe the difference in performance in nanoseconds
        // echo PHP_EOL . "Uncached: " . $t2 - $t1 . PHP_EOL . "Cached: " . $t3 - $t2;

        // Ensure that the value is cleared on kernel reset
        // First request will take the longer as it pulls from db again
        self::ensureKernelShutdown();
        self::bootKernel();
        // Refresh the cached service to be from the new kernel instance
        $this->service = static::getContainer()->get(PayoutManagerV2::class);
        $t4 = hrtime(true);
        $id = $this->service->getSuperAdminAuthId();
        $t5 = hrtime(true);
        $this->assertGreaterThan($t3 - $t2, $t5 - $t4);

        // Uncomment to observe the difference in performance in nanoseconds
        // echo PHP_EOL . "Prev cached: " . $t3 - $t2 . PHP_EOL . "New uncached: " . $t5 - $t4;
    }
}
