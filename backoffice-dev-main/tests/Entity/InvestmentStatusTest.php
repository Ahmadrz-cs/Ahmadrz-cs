<?php

namespace App\Tests\Entity;

use App\Entity\Investment;
use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class InvestmentStatusTest
 * @package App\Tests\Entity
 */
class InvestmentStatusTest extends \PHPUnit\Framework\TestCase
{
    public function testOpenOn(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setOpenOn($customdate);
            $result = $assetstatus_obj->getOpenOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsopen(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $assetstatus_obj->setIsopen(true);
            $result = $assetstatus_obj->getIsopen();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testRejectedOn(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setRejectedOn($customdate);
            $result = $assetstatus_obj->getRejectedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsRejected(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $assetstatus_obj->setIsRejected(true);
            $result = $assetstatus_obj->getIsRejected();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testApprovedOn(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setApprovedOn($customdate);
            $result = $assetstatus_obj->getApprovedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testisApproved(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $assetstatus_obj->setisApproved(true);
            $result = $assetstatus_obj->getisApproved();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testWithdrawnOn(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $customdate = new \DateTime();
            $assetstatus_obj->setWithdrawnOn($customdate);
            $result = $assetstatus_obj->getWithdrawnOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsWithdrawn(): void
    {
        try {
            $assetstatus_obj = new InvestmentStatus();
            $assetstatus_obj->setIsWithdrawn(true);
            $result = $assetstatus_obj->getIsWithdrawn();

            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetLifecycleStatus(): void
    {
        $statusChoices = [
            InvestmentLifecycle::STATE_SETTLED,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_REJECTED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_OPEN,
        ];
        $investment = new Investment();
        $investmentStatus = $investment->getStatus();
        foreach ($statusChoices as $status) {
            $investmentStatus->setLifecycleStatus($status);
            $this->assertEquals($status, $investmentStatus->getLifecycleStatus());
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            // State bool and time set
            $this->assertTrue($propertyAccessor->getValue(
                $investmentStatus,
                'is' . ucfirst($status),
            ));
            $this->assertNotEmpty($propertyAccessor->getValue(
                $investmentStatus,
                ucfirst($status) . 'On',
            ));
            $otherStates = array_diff($statusChoices, [$status]);
            foreach ($otherStates as $otherState) {
                // Other state bools unset
                // Don't really care about whether the time is set or not, always useful as a record
                $this->assertFalse($propertyAccessor->getValue(
                    $investmentStatus,
                    'is' . ucfirst($otherState),
                ));
            }
        }
    }
}
