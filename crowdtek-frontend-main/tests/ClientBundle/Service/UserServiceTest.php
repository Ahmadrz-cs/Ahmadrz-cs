<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service;

use ClientBundle\Service\UserService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\ClientBundle\Service\AbstractApiServiceTest;

class UserServiceTest extends AbstractApiServiceTest
{
    /**
     * @var UserService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(UserService::class);
    }


    public function testCardPayin(): void
    {
        $service = $this->getMockBuilder(UserService::class)
            ->disableOriginalConstructor()
            // ->setConstructorArgs([static::getContainer(), new Session(new MockArraySessionStorage())])
            ->onlyMethods(['payinWithRegisteredCardMangoPay'])
            ->getMock();

        $payinReturn = [
            'data' => [
                'SecureModeRedirectURL' => 'test.com'
            ]
        ];

        $service->expects($this->once())
            ->method('payinWithRegisteredCardMangoPay')
            ->will($this->returnValue($payinReturn));

        /**
         * @var UserService $service
         */
        $result = $service->payinWithRegisteredCardMangoPay(100, []);
        $this->assertEquals('test.com', $result['data']['SecureModeRedirectURL']);
    }
}
