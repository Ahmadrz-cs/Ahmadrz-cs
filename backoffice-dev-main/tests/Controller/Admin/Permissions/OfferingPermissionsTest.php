<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\Offering;
use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class OfferingPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testOfferingRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/offering',
            '/admin/offering/list',
            '/admin/offering/1/view',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testOfferingUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/offering/1/edit',
        ];
        $expected = $expected == Response::HTTP_FORBIDDEN ? false : true;
        foreach ($paths as $path) {
            $crawler = $this->client->request('GET', $path);
            $form = $crawler->filter('form')->form();

            // Check whether all form fields are disabled
            $formValues = $form->getValues();
            $this->assertGreaterThanOrEqual((int) $expected, count($formValues));
        }
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    // public function testOfferingUpdateSimple(string $user, int $expected): void
    // {
    //     // Any simple routes that are GET requests that will perform an update
    //     // And thus require operations permissions
    //     $this->loginWebClient($user);
    //     $this->client->followRedirects();
    //     $paths = [
    //         // '/admin/offering/1/quick-publish',
    //     ];
    //     foreach ($paths as $path) {
    //         $this->client->request('GET', $path);
    //         $this->assertResponseStatusCodeSame($expected);
    //     }
    // }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testOfferingCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/offering/add',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testOfferingStateTransitions(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $transitions = [
            ['action' => 'submit', 'startState' => 'draft'],
            ['action' => 'approve', 'startState' => 'submitted'],
            ['action' => 'publish', 'startState' => 'approved'],
            ['action' => 'close', 'startState' => 'published'],
            ['action' => 'cancel', 'startState' => 'published'],
            ['action' => 'reject', 'startState' => 'submitted'],
        ];
        foreach ($transitions as $transition) {
            $fixtureId = $this->searchFixtures(
                Offering::class,
                ['status' => $transition['startState']],
                true,
            )[0];
            $this->client->request(
                'GET',
                "/admin/offering/$fixtureId/" . $transition['action'],
            );
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
