<?php

namespace App\Tests\Entity;

use App\Entity\Address;
use App\Entity\Communication;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Entity\UserDocument;
use Exception;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testOBStep(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->getOBStep());

        // set to page 1
        $test_value = 1;
        $this->assertNotNull($test_value);
        $user->setOBStep($test_value);
        $this->assertEquals($test_value, $user->getOBStep());

        // set to page 3
        $test_value = 3;
        $this->assertNotNull($test_value);
        $user->setOBStep($test_value);
        $this->assertEquals($test_value, $user->getOBStep());
    }

    public function testMIFID(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->getMIFIDStatus());

        // set to 0
        $test_value = 0;
        $this->assertNotNull($test_value);
        $user->setMIFIDStatus($test_value);
        $this->assertEquals($test_value, $user->getMIFIDStatus());

        // set to 1
        $test_value = 2;
        $this->assertNotNull($test_value);
        $user->setMIFIDStatus($test_value);
        $this->assertEquals($test_value, $user->getMIFIDStatus());
    }

    public function testGDPRAccepted(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->isGDPRAccepted());

        // set to accepted
        $test_value = 1;
        $this->assertNotNull($test_value);
        $user->setGDPRAccepted($test_value);
        $this->assertEquals($test_value, $user->isGDPRAccepted());

        // set to rejected
        $test_value = 2;
        $this->assertNotNull($test_value);
        $user->setGDPRAccepted($test_value);
        $this->assertEquals($test_value, $user->isGDPRAccepted());
    }

    public function testTermServiceAccepted(): void
    {
        $user = new User();

        $this->assertEquals(false, $user->isTermServiceAccepted());

        $test_value = true;
        $this->assertNotNull($test_value);

        $user->setTermServiceAccepted($test_value);
        $this->assertEquals($test_value, $user->isTermServiceAccepted());
    }

    public function testIsVIP(): void
    {
        $user = new User();

        $this->assertEquals(0, $user->getisVIP());

        $test_value = '1';
        $this->assertNotNull($test_value);

        $user->setisVIP($test_value);
        $this->assertEquals($test_value, $user->getisVIP());
    }

    public function testGender(): void
    {
        $user = new User();
        $test_value = 'M';
        $this->assertNotNull($test_value);

        $user->setGender($test_value);
        $this->assertEquals($test_value, $user->getGender());
    }

    public function testSetType(): void
    {
        $user = new User();
        $test_value = 'customer';
        $this->assertNotNull($test_value);

        $user->setType($test_value);
        $this->assertEquals($test_value, $user->getType());
    }

    public function testSetlastLoginAt(): void
    {
        $user = new User();
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $user->setLastLoginAt($test_value);
        $this->assertEquals($test_value, $user->getLastLoginAt());
    }

    public function testSetSetPasswdExpiry(): void
    {
        $user = new User();
        $test_value = '1';
        $this->assertNotNull($test_value);

        $user->setSetPasswdExpiry($test_value);
        $this->assertTrue($user->getSetPasswdExpiry());
    }

    public function testSetMiddlename(): void
    {
        $user = new User();
        $test_value = 'Sayak M';
        $this->assertNull($user->getMiddlename());

        $user->setMiddlename($test_value);
        $this->assertEquals($test_value, $user->getMiddlename());
    }

    public function testSetHonoricPrefix(): void
    {
        $user = new User();
        $test_value = 'Mr';
        $this->assertNotNull($test_value);

        $user->setHonoricPrefix($test_value);
        $this->assertEquals($test_value, $user->getHonoricPrefix());
    }

    public function testSetHonoricSuffix(): void
    {
        $user = new User();
        $test_value = 'M';
        $this->assertNotNull($test_value);

        $user->setHonoricSuffix($test_value);
        $this->assertEquals($test_value, $user->getHonoricSuffix());
    }

    public function testSetJobTitle(): void
    {
        $user = new User();
        $test_value = 'Developer';
        $this->assertNotNull($test_value);

        $user->setJobTitle($test_value);
        $this->assertEquals($test_value, $user->getJobTitle());
    }

    public function testSetLocation(): void
    {
        $user = new User();
        $test_value = 'Manchester';
        $this->assertNotNull($test_value);

        $user->setLocation($test_value);
        $this->assertEquals($test_value, $user->getLocation());
    }

    public function testSetNationality(): void
    {
        $user = new User();
        $test_value = 'British';
        $this->assertNotNull($test_value);

        $user->setNationality($test_value);
        $this->assertEquals($test_value, $user->getNationality());
    }

    public function testSetMobile(): void
    {
        $user = new User();
        $test_value = '12536456516';
        $this->assertNotNull($test_value);

        $user->setMobile($test_value);
        $this->assertEquals($test_value, $user->getMobile());
    }

    public function testSetPhone1(): void
    {
        $user = new User();
        $test_value = '+9112536456516';
        $this->assertNotNull($test_value);

        $user->setPhone1($test_value);
        $this->assertEquals($test_value, $user->getPhone1());
    }

    public function testSetPhone2(): void
    {
        $user = new User();
        $test_value = '+91126789-08';
        $this->assertNotNull($test_value);

        $user->setPhone2($test_value);
        $this->assertEquals($test_value, $user->getPhone2());
    }

    public function testSetBirthCountry(): void
    {
        $user = new User();
        $test_value = 'England';
        $this->assertNotNull($test_value);

        $user->setBirthCountry($test_value);
        $this->assertEquals($test_value, $user->getBirthCountry());
    }

    public function testSetBirthDate(): void
    {
        $user = new User();
        //  $test_value = "2016/10/28";
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $user->setBirthDate($test_value);
        $this->assertEquals($test_value, $user->getBirthDate());
    }

    public function testSetBirthPlace(): void
    {
        $user = new User();
        $test_value = 'Sydney';
        $this->assertNotNull($test_value);

        $user->setBirthPlace($test_value);
        $this->assertEquals($test_value, $user->getBirthPlace());
    }

    public function testSetDrivingLicenseNo(): void
    {
        $user = new User();
        $test_value = 'DL-3486294';
        $this->assertNotNull($test_value);

        $user->setDrivingLicenseNo($test_value);
        $this->assertEquals($test_value, $user->getDrivingLicenseNo());
    }

    public function testSetPassportNumber(): void
    {
        $user = new User();
        $test_value = 'IHH5479';
        $this->assertNotNull($test_value);

        $user->setPassportNumber($test_value);
        $this->assertEquals($test_value, $user->getPassportNumber());
    }

    public function testSetPassportCountry(): void
    {
        $user = new User();
        $test_value = 'England';
        $this->assertNotNull($test_value);

        $user->setPassportCountry($test_value);
        $this->assertEquals($test_value, $user->getPassportCountry());
    }

    public function testSetPassportExpiry(): void
    {
        $user = new User();
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $user->setPassportExpiry($test_value);
        $this->assertEquals($test_value, $user->getPassportExpiry());
    }

    public function testSetIncomeRange(): void
    {
        $user = new User();
        $test_value = '10000';
        $this->assertNotNull($test_value);

        $user->setIncomeRange($test_value);
        $this->assertEquals($test_value, $user->getIncomeRange());
    }

    /**
     * testAddField unit test to ensure the array collection are returning expected values
     */
    public function testAddField(): void
    {
        $User = new User();
        $User->setFirstname('Sayak');

        $CustomField = new UserCustomFields();
        $CustomField->setFieldKey('Test_add_field');
        $CustomField->setFieldValue('Test_add_field_value');
        $CustomField->setUser($User);

        $User->addCustomField($CustomField);
        $this->assertEquals('Test_add_field', $CustomField->getFieldKey());
        $this->assertEquals('Test_add_field_value', $CustomField->getFieldValue());
    }

    public function testFindReplaceCustomField(): void
    {
        $user = new User();
        $user->setFirstname('bob');

        $CustomField = new UserCustomFields();
        $CustomField->setFieldKey('field1');
        $CustomField->setFieldValue('field_one');
        $CustomField->setUser($user);
        $user->addCustomField($CustomField);

        unset($CustomField);

        $CustomField = new UserCustomFields();
        $CustomField->setFieldKey('field2');
        $CustomField->setFieldValue('field_two');
        $CustomField->setUser($user);
        $user->addCustomField($CustomField);

        unset($CustomField);
        $this->assertEquals(2, count($user->getCustomFields()));

        // lets replace the value
        $cf_field2 = new UserCustomFields();
        $cf_field2->setFieldKey('field2');
        $cf_field2->setFieldValue('field_two_updated');
        $cf_field2->setUser($user);

        $ret = $user->findReplaceCustomField($cf_field2);
        $this->assertTrue($ret);

        $this->assertEquals(2, count($user->getCustomFields()));

        /** @var UserCustomFields $update_cf */
        $update_cf = $user->getCustomFields()[1];
        $this->assertEquals('field_two_updated', $update_cf->getFieldValue());

        $CustomField = new UserCustomFields();
        $CustomField->setFieldKey('field3');
        $CustomField->setFieldValue('field_three');
        $CustomField->setUser($user);
        $user->addCustomField($CustomField);

        $this->assertEquals(3, count($user->getCustomFields()));

        $update_cf = $user->getCustomFields()[1];
        $this->assertEquals('field_two_updated', $update_cf->getFieldValue());

        $update_cf = $user->getCustomFields()[2];
        $this->assertEquals('field_three', $update_cf->getFieldValue());

        // lets replace the value again
        $cf_again = new UserCustomFields();
        $cf_again->setFieldKey('field2');
        $cf_again->setFieldValue('field_two_updated_again');
        $cf_again->setUser($user);

        $ret = $user->findReplaceCustomField($cf_again);

        $this->assertTrue($ret);
        $this->assertEquals(3, count($user->getCustomFields()));

        /** @var UserCustomFields $update_cf */
        $update_cf = $user->getCustomFields()[1];
        $this->assertEquals('field_two_updated_again', $update_cf->getFieldValue());
    }

    public function testVisibility(): void
    {
        try {
            $user_obj = new User();

            $this->assertEquals(0, $user_obj->getVisibility());

            $user_obj->setVisibility(2);
            $result = $user_obj->getVisibility();
            $this->assertEquals(2, $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testAdditionalType(): void
    {
        try {
            $user_obj = new User();

            $this->assertEquals(0, $user_obj->getAdditionalType());

            $user_obj->setAdditionalType('type1');
            $result = $user_obj->getAdditionalType();
            $this->assertEquals('type1', $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * testAddDocument unit test to ensure the array collection are returning expected values
     * This test case is tricky. Need to analyze
     */
    public function testAffiliateCode(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setAffiliateCode(123);
            $result = $user_obj->getAffiliateCode();
            $this->assertEquals(123, $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testBiography(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setBiography('biography1');
            $result = $user_obj->getBiography();
            $this->assertEquals('biography1', $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testExternalReferenceId(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setExternalReferenceId(123);
            $result = $user_obj->getExternalReferenceId();
            $this->assertEquals(123, $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testReferralCode(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setReferralCode(123);
            $result = $user_obj->getReferralCode();
            $this->assertEquals(123, $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSector(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setSector('Sector1');
            $result = $user_obj->getSector();
            $this->assertEquals('Sector1', $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testTagline(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setTagline('UserTag');
            $result = $user_obj->getTagline();
            $this->assertEquals('UserTag', $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testTaxId(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setTaxId(123);
            $result = $user_obj->getTaxId();
            $this->assertEquals(123, $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testTimezone(): void
    {
        try {
            $user_obj = new User();
            $createdAt = new \DateTime();
            $user_obj->setTimezone($createdAt);
            $result = $user_obj->getTimezone();
            $this->assertEquals($createdAt, $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testWebsite(): void
    {
        try {
            $user_obj = new User();
            $user_obj->setWebsite('www.crowdtek.com');
            $result = $user_obj->getWebsite();
            $this->assertEquals('www.crowdtek.com', $result);
            unset($result);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testAddCommunication(): void
    {
        $User = new User();
        $User->setFirstname('sajal');

        $CustomField = new Communication();
        $CustomField->setSubject('Top Yielder');
        $CustomField->setRecipient($User);

        $User->addCommunication($CustomField);
        $this->assertEquals('Top Yielder', $CustomField->getSubject());
    }

    public function testManagedBy(): void
    {
        $user = new User();
        $testValue = new User();
        $testValue->setFirstname('Emil');

        $user->setManagedBy($testValue);
        $this->assertEquals(
            $testValue->getFirstname(),
            $user->getManagedBy()->getFirstname(),
        );

        // no magic links to managedUsers
        $actual = $user->getManagedUsers();
        $this->assertTrue($actual->isEmpty());

        // nullable field
        $user->setManagedBy(null);
        $this->assertNull($user->getManagedBy());
    }

    public function testManagedUsers(): void
    {
        $user = new User();
        $user->setFirstname('Millie');
        $testValue = new User();
        $testValue->setFirstname('Emil');

        $actual = $user->getManagedUsers();
        $this->assertTrue($actual->isEmpty());

        $user->addManagedUser($testValue);
        $actual = $user->getManagedUsers();
        $this->assertFalse($actual->isEmpty());
        $this->assertEquals(
            $testValue->getFirstname(),
            $actual->first()->getFirstname(),
        );
        $this->assertEquals(
            $testValue->getManagedBy()->getFirstname(),
            $user->getFirstname(),
        );

        $user->removeManagedUser($testValue);
        $actual = $user->getManagedUsers();
        $this->assertTrue($actual->isEmpty());
    }
}
