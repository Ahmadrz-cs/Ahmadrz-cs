<?php

namespace App\Tests\Entity;

use App\Entity\Offering;
use App\Entity\OfferingStatus;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class OfferingStatusTest
 * @package App\Tests\Entity
 */
class OfferingStatusTest extends \PHPUnit\Framework\TestCase
{
    public function testArchivedOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setArchivedOn('14/07/2016');
            $result = $offering_obj->getArchivedOn();
            $this->assertEquals('14/07/2016', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testCancelledOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setCancelledOn('14/07/2016');
            $result = $offering_obj->getCancelledOn();
            $this->assertEquals('14/07/2016', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSubmittedOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setSubmittedOn('14/07/2016');
            $result = $offering_obj->getSubmittedOn();
            $this->assertEquals('14/07/2016', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testRejectedOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setRejectedOn('14/07/2016');
            $result = $offering_obj->getRejectedOn();
            $this->assertEquals('14/07/2016', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testApprovedOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setApprovedOn('14/07/2016');
            $result = $offering_obj->getApprovedOn();
            $this->assertEquals('14/07/2016', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testPublishedOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();
            $customdate = new \DateTime();
            $offering_obj->setPublishedOn($customdate);
            $result = $offering_obj->getPublishedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testClosedOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();
            $customdate = new \DateTime();
            $offering_obj->setClosedOn($customdate);
            $result = $offering_obj->getClosedOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSettledOn(): void
    {
        try {
            $offering_obj = new OfferingStatus();
            $customdate = new \DateTime();
            $offering_obj->setSettledOn($customdate);
            $result = $offering_obj->getSettledOn();
            $this->assertEquals($customdate, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsArchived(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsArchived(true);
            $result = $offering_obj->getIsArchived();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsCancelled(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsCancelled(true);
            $result = $offering_obj->getIsCancelled();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsSubmitted(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsSubmitted(true);
            $result = $offering_obj->getIsSubmitted();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsRejected(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsRejected(true);
            $result = $offering_obj->getIsRejected();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsApproved(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsApproved(true);
            $result = $offering_obj->getIsApproved();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsPublished(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsPublished(true);
            $result = $offering_obj->getIsPublished();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsRestricted(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsRestricted(true);
            $result = $offering_obj->getIsRestricted();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsClosed(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsClosed(true);
            $result = $offering_obj->getIsClosed();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testIsSettled(): void
    {
        try {
            $offering_obj = new OfferingStatus();

            $offering_obj->setIsSettled(true);
            $result = $offering_obj->getIsSettled();
            $this->assertEquals(true, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetInvestment(): void
    {
        try {
            $offeringstatus_obj = new OfferingStatus();
            $offering_obj = new Offering();
            $offering_obj->setName('my Offering');

            $offeringstatus_obj->setOffering($offering_obj);

            $result = $offeringstatus_obj->getOffering();

            $this->assertEquals($offering_obj->getName(), $result->getName());
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
