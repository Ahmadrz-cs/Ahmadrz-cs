<?php

namespace App\Tests\Entity\Lifecycle;

use App\Entity\Asset;
use App\Entity\Lifecycle\AssetLifecycle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssetLifecycleTest extends KernelTestCase
{
    /** @var \Symfony\Component\Workflow\WorkflowInterface $workflow */
    private $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->workflow = static::getContainer()->get('state_machine.asset');
    }

    public function testAssetInitialState(): void
    {
        $asset = new Asset();
        $this->assertEquals(AssetLifecycle::STATE_DRAFT, $asset->getLifecycleStatus());
    }

    public function testAssetStateChangeToArchived(): void
    {
        $asset = new Asset();
        $this->assertEquals(AssetLifecycle::STATE_DRAFT, $asset->getLifecycleStatus());
        $this->workflow->apply($asset, AssetLifecycle::DRAFT_TO_ARCHIVAL);
        $this->assertEquals(
            AssetLifecycle::STATE_ARCHIVED,
            $asset->getLifecycleStatus(),
        );
    }

    public function testAssetStateChangeToCancellation(): void
    {
        $asset = new Asset();
        $this->assertEquals(AssetLifecycle::STATE_DRAFT, $asset->getLifecycleStatus());
        $this->workflow->apply($asset, AssetLifecycle::DRAFT_TO_CANCELLED);
        $this->assertEquals(
            AssetLifecycle::STATE_CANCELLED,
            $asset->getLifecycleStatus(),
        );
    }

    public function testAssetStateChangeToSubmission(): void
    {
        $asset = new Asset();
        $this->assertEquals(AssetLifecycle::STATE_DRAFT, $asset->getLifecycleStatus());
        $this->workflow->apply($asset, AssetLifecycle::DRAFT_TO_SUBMIT);
        $this->assertEquals(
            AssetLifecycle::STATE_SUBMITTED,
            $asset->getLifecycleStatus(),
        );
    }

    public function testAssetStateChangeToApproval(): void
    {
        $asset = new Asset();
        $this->assertEquals(AssetLifecycle::STATE_DRAFT, $asset->getLifecycleStatus());
        $this->workflow->apply($asset, AssetLifecycle::DRAFT_TO_SUBMIT);
        $this->assertEquals(
            AssetLifecycle::STATE_SUBMITTED,
            $asset->getLifecycleStatus(),
        );
        $this->workflow->apply($asset, AssetLifecycle::SUBMIT_TO_APPROVE);
        $this->assertEquals(
            AssetLifecycle::STATE_APPROVED,
            $asset->getLifecycleStatus(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lifecycleStatusProvider')]
    public function testintAsState(int $input, string $expected): void
    {
        $actual = AssetLifecycle::intAsState($input);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('lifecycleStatusProvider')]
    public function testStateAsInt(int $expected, string $input): void
    {
        $actual = AssetLifecycle::StateAsInt($input);
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
        yield 'archived status' => [6, 'archived'];
        yield 'cancelled status' => [7, 'cancelled'];
    }
}
