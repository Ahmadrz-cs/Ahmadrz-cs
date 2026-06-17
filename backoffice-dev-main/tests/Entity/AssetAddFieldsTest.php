<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Asset;
use App\Entity\AssetAddFields;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class AssetAddFieldsTest
 * @package App\Tests\Entity
 */
class AssetAddFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function testFieldKey
     */
    public function testFieldKey(): void
    {
        try {
            $assetAddFields = new AssetAddFields();

            $assetAddFields->setFieldKey('field 1');
            $result = $assetAddFields->getFieldKey();
            $this->assertEquals('field 1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testFieldKey
     */
    public function testValue(): void
    {
        try {
            $assetAddFields = new AssetAddFields();

            $assetAddFields->setValue('Sayak Mukherjee');
            $result = $assetAddFields->getValue();
            $this->assertEquals('Sayak Mukherjee', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * testSetAsset
     */
    public function testSetAsset(): void
    {
        $assetAddFields = new AssetAddFields();
        $asset = new asset();
        $this->assertNull($assetAddFields->getAsset());

        $assetAddFields->setAsset($asset);
        $this->assertEquals($asset, $assetAddFields->getAsset());
    }
}
