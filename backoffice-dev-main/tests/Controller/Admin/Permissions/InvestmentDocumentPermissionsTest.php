<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\Document;
use App\Entity\InvestmentDocuments;
use App\Test\PermissionsWebTestCase;
use App\Test\Util\DocumentTestUtil;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class InvestmentDocumentPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testInvestmentDocumentRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/investmentdocument',
            '/admin/investmentdocument/list',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testInvestmentDocumentWrite(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/investmentdocument/1/edit',
            '/admin/investmentdocument/add',
            '/admin/investmentdocument/certificate-uploader',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testInvestmentDocumentDelete(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        if ($expected == Response::HTTP_OK) {
            DocumentTestUtil::addTempTestDoc(
                $this->client,
                '/admin/investment/1/add_document',
                self::$kernel->getProjectDir()
                    . '/tests/Support/Data/uploads/public/fixtures/smallbedroomstock.jpg',
                'tempFileForDeletion',
            );
            // Fixture search ensures that it is the new file that we are deleting, else fails test
            $docId = $this->searchFixtures(
                InvestmentDocuments::class,
                ['tag' => 'tempFileForDeletion'],
                true,
            )[0];
            $this->client->request('GET', "/admin/investmentdocument/$docId/delete");
        } else {
            $this->client->request('GET', '/admin/investmentdocument/1/delete');
        }
        $this->assertResponseStatusCodeSame($expected);
    }
}
