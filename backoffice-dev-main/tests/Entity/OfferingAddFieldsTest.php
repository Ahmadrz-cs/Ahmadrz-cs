<?php

namespace App\Tests\Entity;

use App\Entity\Offering;
use App\Entity\OfferingAddFields;
use Symfony\Component\Validator\Constraints\DateTime;

class OfferingAddFieldsTest extends \PHPUnit\Framework\TestCase
{
    /*
     * testSetFieldKey
     */
    public function testSetFieldKey(): void
    {
        $OfferingAddFields = new OfferingAddFields();
        $test_value = 'key1';
        $this->assertNotNull($test_value);

        $OfferingAddFields->setFieldKey($test_value);
        $this->assertEquals($test_value, $OfferingAddFields->getFieldKey());
    }

    /*
     * testSetFieldValue
     */
    public function testSetFieldValue(): void
    {
        $OfferingAddFields = new OfferingAddFields();
        $test_value = 'field1';
        $this->assertNotNull($test_value);

        $OfferingAddFields->setFieldValue($test_value);
        $this->assertEquals($test_value, $OfferingAddFields->getFieldValue());
    }

    /*
     * testSetOffering
     */
    public function testSetOffering(): void
    {
        $OfferingAddFields = new OfferingAddFields();
        $Offering = new Offering();
        $this->assertNull($OfferingAddFields->getOffering());

        $OfferingAddFields->setOffering($Offering);
        $this->assertEquals($Offering, $OfferingAddFields->getOffering());
    }
}
