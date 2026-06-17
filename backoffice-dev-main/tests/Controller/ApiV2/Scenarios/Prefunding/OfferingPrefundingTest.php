<?php

namespace App\Tests\Controller\ApiV2\Scenarios\Prefunding;

use App\Entity\Offering;
use App\Test\FixtureWebTestCase;

class OfferingPrefundingTest extends FixtureWebTestCase
{
    public function testChangeOfferingTypePrefundingToRetail(): void
    {
        /**
         * @var Offering
         */
        $sample = $this->searchFixtures(Offering::class, [
            'name' => 'Nixis Plutona - Bristol',
        ])[0];
        $actual = $this->getAggregatedValues($sample);
        $expected = [
            'totalInvestments' => 6,
            'totalInvestors' => 3,
            'raisedAmount' => 19800.0,
            'sharesSold' => 11250,
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);

        $this->setOfferingType($sample, 'retail');

        // reload the offering from db to trigger updated fields
        $sample = $this->searchFixtures(
            Offering::class,
            ['name' => 'Nixis Plutona - Bristol'],
            false,
            true,
        )[0];
        $actual = $this->getAggregatedValues($sample);
        $expected = [
            'totalInvestments' => 2,
            'totalInvestors' => 2,
            'raisedAmount' => 3960.0,
            'sharesSold' => 2250,
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    private function getAggregatedValues(Offering $offering): array
    {
        return [
            'totalInvestments' => (int) $offering->getInvestmentCount(),
            'totalInvestors' => (int) $offering->getInvestorCount(),
            'raisedAmount' => (float) $offering->getRaisedAmount(),
            'sharesSold' => (int) $offering->getSharesSold(),
        ];
    }

    private function setOfferingType(Offering $offering, string $type): void
    {
        $offering->setOfferingType($type);
        $repository = $this->entityManager->getRepository(Offering::class);
        $repository->save($offering);
        $this->entityManager->flush();
    }
}
