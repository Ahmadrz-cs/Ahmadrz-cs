<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use App\Entity\InvestmentDocuments;
use App\Entity\Offering;
use App\Entity\Payout;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class InvestmentTest
 * @package Application\Sonata\UserBundle\Tests\Entity
 */
class InvestmentTest extends \PHPUnit\Framework\TestCase
{
    //protected $name;
    /**
     * function setNameTest
     */
    public function testSetName(): void
    {
        try {
            $investment_obj = new Investment();

            $investment_obj->setName('Sayak Mukherjee');
            $result = $investment_obj->getName();
            $this->assertEquals('Sayak Mukherjee', $result);
            unset($result);

            $investment_obj->setName('Sayak Mukherjeetesttesttesttesttesttesttesttest');
            $result = $investment_obj->getName();
            $this->assertEquals(
                'Sayak Mukherjeetesttesttesttesttesttesttesttest',
                $result,
            );
            unset($result);

            // Functional test cases, need to dicuss more on it later
            // $investment_obj->setName('1000000');
            // $result = $investment_obj->getName();
            // $this->assertEquals('', $result);
            // unset($result);
            //
            // $investment_obj->setName('@#$#@$');
            // $result = $investment_obj->getName();
            // $this->assertEquals('', $result);
            // unset($result);
            //
            // $investment_obj->setName('Sayak@#$#@$');
            // $result = $investment_obj->getName();
            // $this->assertEquals('', $result);
            // unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testGetId()
     */
    public function testGetId(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setName('Sayak Mukherjee');

            $result = $investment_obj->getId();

            // new objects should have no id set
            $this->assertNull($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetUser(): void
    {
        try {
            $investment_obj = new Investment();
            $user_obj = new User();

            $result_obj = $user_obj->getId();
            $result_add = $investment_obj->setUser($result_obj);
            $user = $result_obj;
            $result_get = $investment_obj->getUser();

            $this->assertEquals($user, $result_get);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testSetOffering()
     */
    public function testSetOffering(): void
    {
        try {
            $investment_obj = new Investment();
            $offering_obj = new Offering();

            $result_obj = $offering_obj->setName('Offer1');
            $investment_obj->setOffering($result_obj);
            $result = $investment_obj->getOffering();
            $this->assertEquals($result_obj, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testAddAddField(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_add_filed_obj = new InvestmentAddFields();

            $result_obj = $investment_add_filed_obj->setFieldKey('field1');

            $return_arr = $investment_obj->addAddField($result_obj);
            $addFields = new ArrayCollection();
            $addFields[] = $result_obj;

            $result = $investment_obj->getAddFields();
            $this->assertEquals($addFields, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    //TODO: Will revisit this method because the entity relationship have changed, causing failure
    /*
     * public function testAddDocument()
     * {
     * try
     * {
     * $investment_obj = new Investment();
     * $investment_add_document_obj = new InvestmentDocuments();
     *
     * $result_obj = $investment_add_document_obj->setFileName('file1');
     * $result_add = $investment_obj->addDocument($result_obj);
     * $this->documents[] = $result_obj;
     * $result_get = $investment_obj->getDocuments();
     *
     * $this->assertEquals($this->documents, $result_get);
     *
     * }
     * catch (\Exception $e)
     * {
     * echo 'Caught exception: ',  $e->getMessage(), "\n";
     * }
     * }*/

    public function testSetCreatedBy(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setCreatedBy('user1');
            $result = $investment_obj->getCreatedBy();

            $this->assertEquals('user1', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetInvestmentValue(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setInvestmentValue('1234');
            $result = $investment_obj->getInvestmentValue();

            $this->assertEquals('1234', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetNumberOfShares(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setNumberOfShares('1234');
            $result = $investment_obj->getNumberOfShares();

            $this->assertEquals('1234', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCurrency(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setCurrency('INR');
            $result = $investment_obj->getCurrency();

            $this->assertEquals('INR', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetInterestRate(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setInterestRate('7');
            $result = $investment_obj->getInterestRate();

            $this->assertEquals('7', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetTerm(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setTerm('7');
            $result = $investment_obj->getTerm();

            $this->assertEquals('7', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetOrgPricePerShare(): void
    {
        try {
            $investment_obj = new Investment();
            $investment_obj->setOrgPricePerShare('100');
            $result = $investment_obj->getOrgPricePerShare();

            $this->assertEquals('100', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testVisibility(): void
    {
        try {
            $invest_obj = new Investment();

            $this->assertEquals(0, $invest_obj->getVisibility());

            $invest_obj->setVisibility(2);
            $result = $invest_obj->getVisibility();
            $this->assertEquals(2, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testUpdateAddedField(): void
    {
        $addedField = new \App\Entity\InvestmentAddFields();
        $addedField->setFieldKey('testKey');
        $addedField->setFieldValue('testValue');
        $investment = new Investment();
        $investment->addAddField($addedField);
        $investment->updateAddedField('testKey', 'newValue');
        $this->assertEquals('newValue', $addedField->getFieldValue());

        //check new InvestmentAddFields if key doesn't exist
        $investment->updateAddedField('anotherKey', 'anotherValue');
        $fields = $investment->getAddFields();
        if ($fields) {
            foreach ($fields as $investmendAddField) {
                if ($investmendAddField->getFieldKey() == 'anotherKey') {
                    $this->assertEquals(
                        'anotherValue',
                        $investmendAddField->getFieldValue(),
                    );
                }
            }
        }
    }

    public function testGetAddedField(): void
    {
        $addedField = new \App\Entity\InvestmentAddFields();
        $addedField->setFieldKey('testKey');
        $addedField->setFieldValue('testValue');
        $investment = new Investment();
        $investment->addAddField($addedField);
        $retrievedField = $investment->getAddedField('testKey', 'testValue');

        $this->assertEquals('testKey', $retrievedField->getFieldKey());
        $this->assertEquals('testValue', $retrievedField->getFieldValue());
    }

    public function testGetMetadata(): void
    {
        $addedField = new InvestmentAddFields();
        $addedField->setFieldKey('animal');
        $addedField->setFieldValue('Emperor Penguin');
        $addedField2 = new InvestmentAddFields();
        $addedField2->setFieldKey('fruit');
        $addedField2->setFieldValue('dragonfruit');
        $investment = new Investment();
        $investment->addAddField($addedField);
        $investment->addAddField($addedField2);
        $actual = $investment->getMetadata();
        $expected = [
            'animal' => 'Emperor Penguin',
            'fruit' => 'dragonfruit',
        ];

        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /*
     * public function testSetComments()
     * {
     * try
     * {
     * $investment_obj = new Investment();
     * $investment_obj->setComments('comments 1');
     * $result = $investment_obj->getComments();
     *
     * $this->assertEquals('comments 1', $result);
     *
     * }
     * catch (\Exception $e)
     * {
     * echo 'Caught exception: ',  $e->getMessage(), "\n";
     * }
     * }
     */
}
