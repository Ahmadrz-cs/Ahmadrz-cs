<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserUpdateResponseTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'address fields'|'basic-core fields'|'gender field', array{0: array{gender?: 'FEMALE', email?: 'bene.spaghet@test.com', firstName?: 'Bene', lastName?: 'Spaghet', address?: array{address1: '123 Merry Apartments', address2: 'Concourse Way', address3: 'Camden', city: 'London', postCode: 'SQP10X', country: 'United Kingdom'}}}, mixed, void>
     */
    public static function fieldsetProvider(): \Generator
    {
        yield 'basic-core fields' => [
            [
                'email' => 'bene.spaghet@test.com',
                'firstName' => 'Bene',
                'lastName' => 'Spaghet',
            ],
        ];
        yield 'address fields' => [
            [
                'address' => [
                    'address1' => '123 Merry Apartments',
                    'address2' => 'Concourse Way',
                    'address3' => 'Camden',
                    'city' => 'London',
                    'postCode' => 'SQP10X',
                    'country' => 'United Kingdom',
                ],
            ],
        ];

        yield 'gender field' => [['gender' => 'FEMALE']];

        // yield "company fields" => [[
        //     'company' => [
        //         "name" => "Some kinda company",
        //         "regAddress1" => "117  St James Boulevard",
        //         "regAddress2" => "Pennay",
        //         "postCode" => "SQP10X",
        //         "regCountry" => "United Kingdom",
        //         "operatingAddress" => "117  St James Boulevard, Pennay, SQP10X",
        //         "operatingPostCode" => "SQP10X",
        //         "registrationNumber" => "1188445522",
        //         "businessNature" => "Dried Pasta",
        //         "telephone" => "12340002468",
        //     ],
        // ]];
    }

    public function testUpdateUser(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fieldsetProvider')]
    public function testUpdateUserFieldsSets($fieldset): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1';
        $content = json_encode($fieldset);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($fieldset as $key => $expected) {
            if (is_array($expected)) {
                foreach ($expected as $innerKey => $innerValue) {
                    $this->assertEquals($innerValue, $expected[$innerKey]);
                }
            } else {
                $this->assertEquals($expected, $apiResponse[$key]);
            }
        }
    }

    // public function testUpdateUserDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(\App\Entity\UserDocument::class);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getId();
    //     $fieldset = [
    //         "description" => "test this, test that",
    //         "tag" => "some other tag",
    //     ];
    //     $content = json_encode($fieldset);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     foreach ($fieldset as $key => $expected) {
    //         $this->assertEquals($expected, $apiResponse[$key]);
    //     }
    // }
}
