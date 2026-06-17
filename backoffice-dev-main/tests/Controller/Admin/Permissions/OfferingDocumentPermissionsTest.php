<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\Document;
use App\Entity\OfferingDocuments;
use App\Test\PermissionsWebTestCase;
use App\Test\Util\DocumentTestUtil;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class OfferingDocumentPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testOfferingDocumentRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/offeringdocument',
            '/admin/offeringdocument/list',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testOfferingDocumentWrite(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/offeringdocument/1/edit',
            '/admin/offeringdocument/add',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testOfferingDocumentDelete(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        if ($expected == Response::HTTP_OK) {
            DocumentTestUtil::addTempTestDoc(
                $this->client,
                '/admin/offeringdocument/add',
                self::$kernel->getProjectDir()
                    . '/tests/Support/Data/uploads/public/fixtures/smallbedroomstock.jpg',
                'tempFileForDeletion',
            );
            // Fixture search ensures that it is the new file that we are deleting, else fails test
            $docId = $this->searchFixtures(
                OfferingDocuments::class,
                ['tag' => 'tempFileForDeletion'],
                true,
            )[0];
            $this->client->request('GET', "/admin/offeringdocument/$docId/delete");
        } else {
            $this->client->request('GET', '/admin/offeringdocument/1/delete');
        }
        $this->assertResponseStatusCodeSame($expected);
    }
}
