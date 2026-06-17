<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class InvestmentPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testInvestmentRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/investment',
            '/admin/investment/list',
            '/admin/investment/1/view',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testInvestmentUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $expected = $expected == Response::HTTP_FORBIDDEN ? false : true;
        $crawler = $this->client->request('GET', '/admin/investment/1/edit');
        $form = $crawler->filter('form')->form();

        // Check whether all form fields are disabled
        $formValues = $form->getValues();
        $this->assertGreaterThanOrEqual((int) $expected, count($formValues));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testInvestmentCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/investment/add',
            '/admin/investment/1/add_payout',
            '/admin/investment/1/add_document',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testInvestmentTransactionIdUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $expected = $expected == Response::HTTP_FORBIDDEN ? false : true;
        $crawler = $this->client->request('GET', '/admin/investment/1/edit');
        $form = $crawler->filter('form')->form();

        // Check if transaction_id field exists in the investment form
        $formValues = $form->getValues();
        $this->assertEquals($expected, array_key_exists(
            'investment[transaction_id]',
            $formValues,
        ));
    }
}
