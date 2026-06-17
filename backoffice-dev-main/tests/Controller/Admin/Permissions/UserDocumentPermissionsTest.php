<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\Document;
use App\Entity\UserDocument;
use App\Test\PermissionsWebTestCase;
use App\Test\Util\DocumentTestUtil;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class UserDocumentPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testUserDocumentRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/userdocument',
            '/admin/userdocument/list',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testUserDocumentWrite(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/userdocument/1/edit',
            '/admin/userdocument/add',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testUserDocumentDelete(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        if ($expected == Response::HTTP_OK) {
            DocumentTestUtil::addTempTestDoc(
                $this->client,
                '/admin/userdocument/add',
                self::$kernel->getProjectDir()
                    . '/tests/Support/Data/uploads/public/fixtures/smallbedroomstock.jpg',
                'tempFileForDeletion',
            );
            // Fixture search ensures that it is the new file that we are deleting, else fails test
            $docId = $this->searchFixtures(
                UserDocument::class,
                ['tag' => 'tempFileForDeletion'],
                true,
            )[0];
            $this->client->request('GET', "/admin/userdocument/$docId/delete");
        } else {
            $this->client->request('GET', '/admin/userdocument/1/delete');
        }
        $this->assertResponseStatusCodeSame($expected);
    }
}
