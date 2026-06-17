<?php

namespace App\Tests\Entity;

use App\Entity\AssetStatus;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class AssetStatusTest
 * @package App\Tests\Entity
 */
class AssetStatusTest extends \PHPUnit\Framework\TestCase
{
    public function testArchivedOn(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setArchivedOn($customdate);
            $result = $assetstatus_obj->getArchivedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testCancelledOn(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setCancelledOn($customdate);
            $result = $assetstatus_obj->getCancelledOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSubmittedOn(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setSubmittedOn($customdate);
            $result = $assetstatus_obj->getSubmittedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testRejectedOn(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setRejectedOn($customdate);
            $result = $assetstatus_obj->getRejectedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsArchived(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setIsArchived($customdate);
            $result = $assetstatus_obj->getIsArchived();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsCancelled(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $assetstatus_obj->setIsCancelled(true);
            $result = $assetstatus_obj->getIsCancelled();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsRejected(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $assetstatus_obj->setIsRejected(true);
            $result = $assetstatus_obj->getIsRejected();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsPublished(): void
    {
        try {
            $assetstatus_obj = new AssetStatus();
            $assetstatus_obj->setIsPublished(true);
            $result = $assetstatus_obj->getIsPublished();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
