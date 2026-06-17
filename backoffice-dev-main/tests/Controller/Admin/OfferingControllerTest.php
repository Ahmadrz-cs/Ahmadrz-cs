<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class OfferingControllerTest extends FixtureWebTestCase
{
    public static function offeringStateTransitionProvider(): \Generator
    {
        yield 'submit' => ['submit', 'draft', 'submitted'];
        yield 'approve' => ['approve', 'submitted', 'approved'];
        yield 'publish' => ['publish', 'approved', 'published'];
        yield 'close' => ['close', 'published', 'closed'];
        yield 'cancel' => ['cancel', 'approved', 'cancelled'];
        yield 'reject' => ['reject', 'approved', 'rejected'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringStateTransitionProvider')]
    public function testEditStateActions(
        string $transition,
        string $startState,
        string $endState,
    ): void {
        $this->loginWebClient(self::USER_SUPER_ADMIN);
        $this->client->followRedirects();
        $fixtureId = $this->searchFixtures(
            Offering::class,
            ['status' => $startState],
            true,
        )[0];
        $crawler = $this->client->request(
            'GET',
            "/admin/offering/$fixtureId/$transition",
        );
        $endStateText = $crawler
            ->filter('#status-manage [data-field-name="current-status"]')
            ->text();
        $this->assertEquals(ucfirst($endState), $endStateText);
    }

    public static function offeringTransitionLinkProvider(): \Generator
    {
        yield 'draft transitions' => ['draft', ['submit', 'cancel']];
        yield 'submitted transitions' => ['submitted', ['approve', 'reject', 'cancel']];
        yield 'approved transitions' => ['approved', ['publish', 'reject', 'cancel']];
        yield 'published transitions' => ['published', ['close', 'cancel']];
        yield 'closed transitions' => ['closed', ['cancel']];
        yield 'rejected transitions' => ['rejected', []];
        yield 'cancelled transitions' => ['cancelled', []];
    }

    /**
     * @param string[] $expectedTransitions
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('offeringTransitionLinkProvider')]
    public function testEditStateActionLinks(
        string $startState,
        array $expectedTransitions,
    ): void {
        $this->loginWebClient(self::USER_SUPER_ADMIN);
        $fixtureId = $this->searchFixtures(
            Offering::class,
            ['status' => $startState],
            true,
        )[0];
        $crawler = $this->client->request('GET', "/admin/offering/$fixtureId/edit");
        if (empty($expectedTransitions)) {
            $selector = '#status-manage .status-actions span';
            $selectorText = '-';
            $this->assertSelectorTextContains($selector, $selectorText);
        } else {
            $expectedTransitions = array_map(
                fn($x) => ucfirst($x) . ' Offering',
                $expectedTransitions,
            );
            $matchingNodes = $crawler
                ->filter('#status-manage .status-actions a')
                ->each(fn(Crawler $node) => $node->text());
            foreach ($matchingNodes as $nodeText) {
                $this->assertContains($nodeText, $expectedTransitions);
            }
        }
    }
}
