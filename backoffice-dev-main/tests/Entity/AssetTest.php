<?php

namespace App\Tests\Entity;

use App\Entity\Asset;
use App\Entity\AssetFee;
use App\Entity\BaseEntity;
use App\Entity\User;

/**
 * Class AssetTest
 * @package App\Tests\Entity
 */
class AssetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function setNameTest
     */
    public function testSetName(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setName('Sayak Mukherjee');
            $result = $asset_obj->getName();
            $this->assertEquals('Sayak Mukherjee', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetAdditionalType(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setAdditionalType('type1');
            $result = $asset_obj->getAdditionalType();
            $this->assertEquals('type1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetAlternateName(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setAlternateName('alternate name');
            $result = $asset_obj->getAlternateName();
            $this->assertEquals('alternate name', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetBriefDescription(): void
    {
        try {
            //string is greater than 360
            $briefDesc = bin2hex(random_bytes(180));
            $asset_obj = new Asset();

            $asset_obj->setBriefDescription($briefDesc);
            $result = $asset_obj->getBriefDescription();
            $this->assertEquals($briefDesc, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCompanyNumber(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setCompanyNumber('123');
            $result = $asset_obj->getCompanyNumber();
            $this->assertEquals('123', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetContactPoint(): void
    {
        try {
            $asset_obj = new Asset();
            $user = new User();
            $user->setUsername('test123');
            $asset_obj->setContactPoint($user);
            $result = $asset_obj->getContactPoint();
            $this->assertEquals($user, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetDetailedDesc(): void
    {
        try {
            $asset_obj = new Asset();
            $detailDesc = bin2hex(random_bytes(180));

            $asset_obj->setDetailedDesc($detailDesc);
            $result = $asset_obj->getDetailedDesc();
            $this->assertEquals($detailDesc, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetDisplayName(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setDisplayName('name name');
            $result = $asset_obj->getDisplayName();
            $this->assertEquals('name name', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetLegalName(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setLegalName('Legal Name1');
            $result = $asset_obj->getLegalName();
            $this->assertEquals('Legal Name1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /*
     *
     * public function testSetMembers()
     * {
     * try
     * {
     * $asset_obj = new Asset();
     *
     * $asset_obj->setMembers('Member');
     * $result = $asset_obj->getMembers();
     * $this->assertEquals('Member', $result);
     * unset($result);
     *
     * }
     * catch (\Exception $e)
     * {
     * echo 'Caught exception: ',  $e->getMessage(), "\n";
     * }
     * }
     */

    public function testSetOrgEmail(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setOrgEmail('org@org.com');
            $result = $asset_obj->getOrgEmail();
            $this->assertEquals('org@org.com', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetSector(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setSector('Sector1');
            $result = $asset_obj->getSector();
            $this->assertEquals('Sector1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetTaxId(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setTaxId('tax1');
            $result = $asset_obj->getTaxId();
            $this->assertEquals('tax1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetTelephone(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setTelephone('+6628853535');
            $result = $asset_obj->getTelephone();
            $this->assertEquals('+6628853535', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetFundingGoal(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setFundingGoal('853535');
            $result = $asset_obj->getFundingGoal();
            $this->assertEquals('853535', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetAmountOfShares(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setAmountOfShares('100');
            $result = $asset_obj->getAmountOfShares();
            $this->assertEquals('100', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetSetupFee(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setSetupFee('100');
            $result = $asset_obj->getSetupFee();
            $this->assertEquals('100', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetAdminFee(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setAdminFee('100');
            $result = $asset_obj->getAdminFee();
            $this->assertEquals('100', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetManagementFee(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setManagementFee('100');
            $result = $asset_obj->getManagementFee();
            $this->assertEquals('100', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetProfitShare(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setProfitShare('10');
            $result = $asset_obj->getProfitShare();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testStampDutyUser(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setStampDutyUser('asset1');
            $result = $asset_obj->getStampDutyUser();
            $this->assertEquals('asset1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetAssetType(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setAssetType('asset1');
            $result = $asset_obj->getAssetType();
            $this->assertEquals('asset1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetInvestmentTerm(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setInvestmentTerm(3);
            $result = $asset_obj->getInvestmentTerm();
            $this->assertEquals(3, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetGrossRentalReturnPA(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setGrossRentalReturnPA('10');
            $result = $asset_obj->getGrossRentalReturnPA();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetNetRentalReturnPA(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setNetRentalReturnPA('10');
            $result = $asset_obj->getNetRentalReturnPA();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetGrossCapitalAppreciation(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setGrossCapitalAppreciation('10');
            $result = $asset_obj->getGrossCapitalAppreciation();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetNetCapitalCapitalAppreciation(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setNetCapitalAppreciation('10');
            $result = $asset_obj->getNetCapitalAppreciation();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetNetCapitalCapitalAppreciationYield(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setNetCapitalAppreciationYield('10');
            $result = $asset_obj->getNetCapitalAppreciationYield();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetPointsOfInterest(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setPointsOfInterest('10');
            $result = $asset_obj->getPointsOfInterest();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetBlockedForSale(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setBlockedForSale(true);
            $result = $asset_obj->getBlockedForSale();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetPricePerShare(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setPricePerShare('10');
            $result = $asset_obj->getPricePerShare();
            $this->assertEquals('10', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCreatedAt(): void
    {
        try {
            $asset_obj = new Asset();
            $createdAt = new \DateTime();
            $asset_obj->setCreatedAt($createdAt);
            $result = $asset_obj->getCreatedAt();
            $this->assertEquals($createdAt, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCreatedBy(): void
    {
        try {
            $asset_obj = new Asset();

            $asset_obj->setCreatedBy('user1');
            $result = $asset_obj->getCreatedBy();
            $this->assertEquals('user1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testVisibility(): void
    {
        try {
            $asset_obj = new Asset();

            $this->assertEquals(0, $asset_obj->getVisibility());

            $asset_obj->setVisibility(BaseEntity::VISIBILITY_ALL);
            $result = $asset_obj->getVisibility();
            $this->assertEquals(BaseEntity::VISIBILITY_ALL, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testGrossRentalReturnPA(): void
    {
        try {
            $asset_obj = new Asset();
            $asset_obj->setGrossRentalReturnPA('PA');
            $result = $asset_obj->getGrossRentalReturnPA();
            $this->assertEquals('PA', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testNetRentalReturnPA(): void
    {
        try {
            $asset_obj = new Asset();
            $asset_obj->setNetRentalReturnPA('PA');
            $result = $asset_obj->getNetRentalReturnPA();
            $this->assertEquals('PA', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testGrossCapitalAppreciation(): void
    {
        try {
            $asset_obj = new Asset();
            $asset_obj->setGrossCapitalAppreciation('application');
            $result = $asset_obj->getGrossCapitalAppreciation();
            $this->assertEquals('application', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testNetCapitalAppreciation(): void
    {
        try {
            $asset_obj = new Asset();
            $asset_obj->setNetCapitalAppreciation('application');
            $result = $asset_obj->getNetCapitalAppreciation();
            $this->assertEquals('application', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testPointsOfInterest(): void
    {
        try {
            $asset_obj = new Asset();
            $asset_obj->setPointsOfInterest('Interest');
            $result = $asset_obj->getPointsOfInterest();
            $this->assertEquals('Interest', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testAdditional_Wallet(): void
    {
        try {
            $asset_obj = new Asset();
            $asset_obj->setAdditionalWallet(123456789);
            $result = $asset_obj->getAdditionalWallet();
            $this->assertEquals(123456789, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testGetFeesGrouped(): void
    {
        $asset = new Asset();
        $asset->addFee($this->generateFeeBand(100, 10));
        $asset->addFee($this->generateFeeBand(800, 50));
        $asset->addFee($this->generateFeeBand(0, 5));
        $asset->addFee($this->generateFeeBand(500, 25));
        $actual = $asset->getFeesGrouped();
        $expected = [
            'relisting' => [
                0 => 5,
                100 => 10,
                500 => 25,
                800 => 50,
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testEmptyGetFeesGrouped(): void
    {
        $asset = new Asset();
        $actual = $asset->getFeesGrouped();
        $expected = [
            'relisting' => Asset::DEFAULT_RELISTING_FEES,
        ];
        $this->assertEquals($expected, $actual);
    }

    public function generateFeeBand(
        int $band,
        int $fee,
        string $type = 'relisting',
    ): AssetFee {
        $feeBand = new AssetFee();
        $feeBand->setBand($band);
        $feeBand->setFee($fee);
        $feeBand->setType($type);
        return $feeBand;
    }

    public function testGetAddedField(): void
    {
        $addedField = new \App\Entity\AssetAddFields();
        $addedField->setFieldKey('testKey');
        $addedField->setValue('testValue');
        $asset = new Asset();
        $asset->addAddField($addedField);
        $retrievedField = $asset->getAddedField('testKey');

        $this->assertEquals('testKey', $retrievedField->getFieldKey());
        $this->assertEquals('testValue', $retrievedField->getValue());
    }

    public function testGetTermEnd(): void
    {
        $asset = new Asset();
        $this->assertNull($asset->getTermEnd());
        // Pick a leap day to see what happens
        $asset->setTermStart(new \DateTime('2024-02-29'));
        $asset->setInvestmentTerm(36);
        // Should tick over onto the next day, which is in March
        $this->assertEquals(new \DateTime('2027-03-01'), $asset->getTermEnd());
    }

    public function testGetTermRemaining(): void
    {
        $asset = new Asset();
        $this->assertNull($asset->getTermRemaining());
        $startDate = new \DateTime('-5 months');
        $startDate = new \DateTime($startDate->format('Y-m-10 08:05'));
        $asset->setTermStart($startDate);
        $asset->setInvestmentTerm(36);

        // Term remaining will be between 30 and 31 depending on the length of the month
        $this->assertGreaterThanOrEqual(30, $asset->getTermRemaining());
        $this->assertLessThanOrEqual(31, $asset->getTermRemaining());

        $asset->setTermStart(new \DateTime('-40 months'));
        $this->assertEquals(0, $asset->getTermRemaining());

        // Debug print outs
        // $interval = $asset->getTermEnd()->diff(new \DateTime());
        // echo PHP_EOL;
        // echo $startDate->format(\DateTime::ATOM) . PHP_EOL;
        // echo "{$interval->y}\tYears" . PHP_EOL;
        // echo "{$interval->m}\tMonths" . PHP_EOL;
        // echo "{$interval->d}\tDays" . PHP_EOL;
        // echo "{$interval->days}\tTotal Days" . PHP_EOL;
    }
}
