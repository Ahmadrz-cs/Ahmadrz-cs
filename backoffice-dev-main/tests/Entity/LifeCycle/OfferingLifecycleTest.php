<?php

namespace App\Tests\Entity\Lifecycle;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OfferingLifecycleTest extends KernelTestCase
{
    /** @var \Symfony\Component\Workflow\WorkflowInterface $workflow */
    private $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->workflow = static::getContainer()->get('state_machine.offering');
    }

    public function testOfferingInitialState(): void
    {
        $offering = new Offering();
        $this->assertEquals(
            OfferingLifecycle::STATE_DRAFT,
            $offering->getLifecycleStatus(),
        );
    }

    // public function testOfferingApplyStateChange()
    // {
    //     // Transitions are not correctly named in the workflow or the OfferingLifecycle
    //     $offering = new Offering();
    //     $this->assertEquals(OfferingLifecycle::STATE_DRAFT, $offering->getLifecycleStatus());
    //     $this->workflow->apply($offering, OfferingLifecycle::DRAFT_TO_SUBMIT);
    //     $this->assertEquals(OfferingLifecycle::STATE_SUBMITTED, $offering->getLifecycleStatus());
    // }
    // public function testOfferingApplyMultipleStateChange()
    // {
    //     // Transitions are not correctly named in the workflow or the OfferingLifecycle
    //     $offering = new Offering();
    //     $this->assertEquals(OfferingLifecycle::STATE_DRAFT, $offering->getLifecycleStatus());
    //     $this->workflow->apply($offering, OfferingLifecycle::DRAFT_TO_SUBMIT);
    //     $this->assertEquals(OfferingLifecycle::STATE_SUBMITTED, $offering->getLifecycleStatus());
    //     $this->workflow->apply($offering, OfferingLifecycle::TRANSITION_APPROVAL);
    //     $this->assertEquals(OfferingLifecycle::STATE_APPROVED, $offering->getLifecycleStatus());
    // }
    #[\PHPUnit\Framework\Attributes\DataProvider('lifecycleStatusProvider')]
    public function testintAsState(int $input, string $expected): void
    {
        $actual = OfferingLifecycle::intAsState($input);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lifecycleStatusProvider')]
    public function testStateAsInt(int $expected, string $input): void
    {
        $actual = OfferingLifecycle::StateAsInt($input);
        $this->assertEquals($expected, $actual);
    }

    // provider as generator
    public static function lifecycleStatusProvider(): \Generator
    {
        yield 'draft status' => [0, 'draft'];
        yield 'submitted status' => [1, 'submitted'];
        yield 'rejected status' => [2, 'rejected'];
        yield 'approved status' => [3, 'approved'];
        yield 'restricted status' => [4, 'restricted'];
        yield 'published status' => [5, 'published'];
        yield 'live status' => [6, 'live'];
        yield 'closed status' => [7, 'closed'];
        yield 'settled status' => [8, 'settled'];
        yield 'cancelled status' => [9, 'cancelled'];
    }
}
