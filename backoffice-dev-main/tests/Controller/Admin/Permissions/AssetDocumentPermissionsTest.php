<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\AssetDocuments;
use App\Entity\Document;
use App\Test\PermissionsWebTestCase;
use App\Test\Util\DocumentTestUtil;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class AssetDocumentPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testAssetDocumentRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/assetdocument',
            '/admin/assetdocument/list',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testAssetDocumentWrite(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/assetdocument/1/edit',
            '/admin/assetdocument/add',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testAssetDocumentDelete(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        if ($expected == Response::HTTP_OK) {
            DocumentTestUtil::addTempTestDoc(
                $this->client,
                '/admin/assetdocument/add',
                self::$kernel->getProjectDir()
                    . '/tests/Support/Data/uploads/public/fixtures/smallbedroomstock.jpg',
                'tempFileForDeletion',
                false,
            );
            // Fixture search ensures that it is the new file that we are deleting, else fails test
            $docId = $this->searchFixtures(
                AssetDocuments::class,
                ['tag' => 'tempFileForDeletion'],
                true,
            )[0];
            $this->client->request('GET', "/admin/assetdocument/$docId/delete");
        } else {
            $this->client->request('GET', '/admin/assetdocument/1/delete');
        }
        $this->assertResponseStatusCodeSame($expected);
    }
}
