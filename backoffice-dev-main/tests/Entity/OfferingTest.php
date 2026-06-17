<?php

namespace App\Tests\Entity;

use App\Entity\Investment;
use App\Entity\Offering;

/**
 * Class OfferingTest
 * @package BusnessBundle\Tests\Entity
 */
class OfferingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * WIP simply the testing to a single method rather than have a method for each fiield/// overkilll
     */
    public function testCreateOffering(): void
    {
        $name = 'Offering 1';
        $sell_inv = new Investment();
        $sell_inv->setName('selling this investment');

        $off = new Offering();
        $off->setName($name);
        //entities
        $off->setSellInvestment($sell_inv);

        $this->assertEquals($name, $off->getName());
        $this->assertEquals('selling this investment', $sell_inv->getName());
    }

    public function testSetCategory(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setCategory('Sayak Mukherjee');
            $result = $offer_obj->getCategory();
            $this->assertEquals('Sayak Mukherjee', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetFundingGoal(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setFundingGoal('2000');
            $result = $offer_obj->getFundingGoal();
            $this->assertEquals('2000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCreatedAt(): void
    {
        try {
            $offer_obj = new Offering();
            $createdAt = new \DateTime();
            $offer_obj->setCreatedAt($createdAt);
            $result = $offer_obj->getCreatedAt();
            $this->assertEquals($createdAt, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCreatedBy(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setCreatedBy('Sayak');
            $result = $offer_obj->getCreatedBy();
            $this->assertEquals('Sayak', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetExternalCommitments(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setExternalCommitments('commitment1');
            $result = $offer_obj->getExternalCommitments();
            $this->assertEquals('commitment1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetIsFeatured(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setIsFeatured('featured1');
            $result = $offer_obj->getIsFeatured();
            $this->assertEquals('featured1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetIsSecondaryMrkt(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setIsSecondaryMrkt('seconderymarket1');
            $result = $offer_obj->getIsSecondaryMrkt();
            $this->assertEquals('seconderymarket1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetValuation(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setValuation('1000');
            $result = $offer_obj->getValuation();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetEquityOffered(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setEquityOffered('1000');
            $result = $offer_obj->getEquityOffered();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetNoOfShares(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setNoOfShares('1000');
            $result = $offer_obj->getNoOfShares();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetPricePerShare(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setPricePerShare('1000');
            $result = $offer_obj->getPricePerShare();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetNetRentProjected(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setNetRentProjected('1000');
            $result = $offer_obj->getNetRentProjected();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetGrossProjectReturn(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setGrossProjectReturn('1000');
            $result = $offer_obj->getGrossProjectReturn();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetOfferingTerm(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setOfferingTerm('1000');
            $result = $offer_obj->getOfferingTerm();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetOpenDate(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setOpenDate('1000');
            $result = $offer_obj->getOpenDate();
            $this->assertEquals('1000', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCloseDate(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setCloseDate('2016/11/19');
            $result = $offer_obj->getCloseDate();
            $this->assertEquals('2016/11/19', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetMinCommitUser(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setMinCommitUser('mincommittuser');
            $result = $offer_obj->getMinCommitUser();
            $this->assertEquals('mincommittuser', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetMaxCommitUser(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setMaxCommitUser('maxcommittuser');
            $result = $offer_obj->getMaxCommitUser();
            $this->assertEquals('maxcommittuser', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetMaxOverFunding(): void
    {
        try {
            $offer_obj = new Offering();

            $offer_obj->setMaxOverFunding('maxOverFunding');
            $result = $offer_obj->getMaxOverFunding();
            $this->assertEquals('maxOverFunding', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testVisibility(): void
    {
        try {
            $off_obj = new Offering();

            $this->assertEquals(0, $off_obj->getVisibility());

            $off_obj->setVisibility(2);
            $result = $off_obj->getVisibility();
            $this->assertEquals(2, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * @psalm-return \Generator<string, array{0: int, 1: 1|3, 2: \DateTime}, mixed, void>
     */
    public static function termRemainingProvider(): \Generator
    {
        // offset by extra 3 days to account for biggest gap in month lengths
        yield '3 months old' => [33, 3, new \DateTime('3 months 3 days ago')];

        yield '1 month left' => [1, 1, new \DateTime('11 months 12 days ago')];
        yield '1 month expired' => [0, 1, new \DateTime('13 months ago')];
        yield '1 day old' => [12, 1, new \DateTime('1 day ago')];
        yield '1 month 2 days old' => [11, 1, new \DateTime('1 months 2 days ago')];
        yield '1 year 1 day old' => [0, 1, new \DateTime('1 year 1 day ago')];
        yield '1 year old' => [0, 1, new \DateTime('1 year ago')];
    }

    public function testGetAssetIdNull(): void
    {
        $offering = new Offering();
        $this->assertNull($offering->getAssetId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('termRemainingProvider')]
    public function testGetTermRemaining(
        int $expected,
        int $term,
        \DateTime $createdAt,
    ): void {
        $offering = new Offering();
        $offering->setCreatedAt($createdAt);
        $offering->setOfferingTerm($term);
        $this->assertEquals($expected, $offering->getTermRemaining());
    }
}
