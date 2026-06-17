<?php

namespace App\Tests\Entity\Lifecycle;

use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvestmentLifecycleTest extends KernelTestCase
{
    /** @var \Symfony\Component\Workflow\WorkflowInterface $workflow */
    private $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->workflow = static::getContainer()->get('state_machine.investment');
    }

    public function testInvestmentCheckInitialState(): void
    {
        $investment = new Investment();
        $this->assertEquals(
            InvestmentLifecycle::STATE_OPEN,
            $investment->getLifecycleStatus(),
        );
    }

    public function testInvestmentCheckChangeStateRejection(): void
    {
        $investment = new Investment();
        $this->assertEquals(
            InvestmentLifecycle::STATE_OPEN,
            $investment->getLifecycleStatus(),
        );
        $this->workflow->apply(
            $investment,
            InvestmentLifecycle::TRANSITION_OPEN_REJECTION,
        );
        $this->assertEquals(
            InvestmentLifecycle::STATE_REJECTED,
            $investment->getLifecycleStatus(),
        );
    }

    public function testInvestmentCheckSettledState(): void
    {
        $investment = new Investment();
        $this->assertEquals(
            InvestmentLifecycle::STATE_OPEN,
            $investment->getLifecycleStatus(),
        );
        $this->workflow->apply(
            $investment,
            InvestmentLifecycle::TRANSITION_OPEN_APPROVAL,
        );
        $this->assertEquals(
            InvestmentLifecycle::STATE_APPROVED,
            $investment->getLifecycleStatus(),
        );
        $this->workflow->apply(
            $investment,
            InvestmentLifecycle::TRANSACTION_APPROVE_SETTLED,
        );
        $this->assertEquals(
            InvestmentLifecycle::STATE_SETTLED,
            $investment->getLifecycleStatus(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lifecycleStatusProvider')]
    public function testintAsState(int $input, string $expected): void
    {
        $actual = InvestmentLifecycle::intAsState($input);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lifecycleStatusProvider')]
    public function testStateAsInt(int $expected, string $input): void
    {
        $actual = InvestmentLifecycle::StateAsInt($input);
        $this->assertEquals($expected, $actual);
    }

    // provider as generator
    public static function lifecycleStatusProvider(): \Generator
    {
        yield 'open status' => [0, 'open'];
        yield 'rejected status' => [1, 'rejected'];
        yield 'approved status' => [2, 'approved'];
        yield 'withdrawn status' => [3, 'withdrawn'];
        yield 'settled status' => [4, 'settled'];
    }
}
