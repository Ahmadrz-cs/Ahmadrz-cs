<?php

namespace App\Tests\Service;

use App\Test\FixtureTestCase;

class SuperadminFixtureSmokeTest extends FixtureTestCase
{
    /**
     * Smoketest to check if the id of superadmin in fixtures has changed
     * If this fails, you'll need to update the following fixtures' createdById field
     *   - src/DataFixtures/templates/baseAsset.yaml
     *   - src/DataFixtures/templates/baseOffering.yaml
     */
    public function testSuperadminId(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_SUPER_ADMIN],
            true,
        )[0];
        $this->assertEquals(1, $user);
    }
}
