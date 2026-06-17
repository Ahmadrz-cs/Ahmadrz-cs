<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Asset;
use App\Entity\AssetAddress;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class AssetAddressTest
 * @package App\Tests\Entity
 */
class AssetAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function testAddress1
     */
    public function testAddress1(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setAddress1('Address1');
            $result = $assetAddress->getAddress1();
            $this->assertEquals('Address1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testAddress2
     */
    public function testAddress2(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setAddress2('Address2');
            $result = $assetAddress->getAddress2();
            $this->assertEquals('Address2', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testAddress3
     */
    public function testAddress3(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setAddress3('Address3');
            $result = $assetAddress->getAddress3();
            $this->assertEquals('Address3', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testCity
     */
    public function testCity(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setCity('London');
            $result = $assetAddress->getCity();
            $this->assertEquals('London', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    private $region;

    /**
     * function testRegion
     */
    public function testRegion(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setRegion('Europe');
            $result = $assetAddress->getRegion();
            $this->assertEquals('Europe', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testPostCode
     */
    public function testPostCode(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setPostCode('700001');
            $result = $assetAddress->getPostCode();
            $this->assertEquals('700001', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testCountry
     */
    public function testCountry(): void
    {
        try {
            $assetAddress = new AssetAddress();

            $assetAddress->setCountry('England');
            $result = $assetAddress->getCountry();
            $this->assertEquals('England', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testLongitude
     */
    public function testLongitude(): void
    {
        try {
            $longitude = '51.538239';

            $assetAddress = new AssetAddress();

            $assetAddress->setLongitude($longitude);
            $result = $assetAddress->getLongitude();
            $this->assertEquals($longitude, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testLatitude
     */
    public function testLatitude(): void
    {
        try {
            $latitude = '51.538239';

            $assetAddress = new AssetAddress();

            $assetAddress->setLatitude($latitude);
            $result = $assetAddress->getLatitude();
            $this->assertEquals($latitude, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /*
     * testSetAsset
     */
    public function testSetAsset(): void
    {
        $assetAddress = new AssetAddress();
        $asset = new asset();
        $this->assertNull($assetAddress->getAsset());

        $assetAddress->setAsset($asset);
        $this->assertEquals($asset, $assetAddress->getAsset());
    }
}
