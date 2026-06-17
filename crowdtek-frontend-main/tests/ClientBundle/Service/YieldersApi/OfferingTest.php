<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service\YieldersApi;

use GuzzleHttp\Psr7\Response;

final class OfferingTest extends AbstractApiResourceTest
{
    public function testAll()
    {
        $body = [
            ['id' => 1],
            ['id' => 3],
            ['id' => 8]
        ];
        $this->mockHandler->append(new Response(200, $this::HEADER_JSON, json_encode($body)));

        $response = $this->apiClient->offering()->all();
        $actual = $this->apiClient->getContent($response);

        $this->assertCount(3, $actual);
        $this->assertEquals('GET', $this->history[0]['request']->getMethod());
        $this->assertEquals('/v2/yielders/offerings', $this->history[0]['request']->getRequestTarget());
    }

    public function testRetrieve()
    {
        $this->mockHandler->append(new Response(200, $this::HEADER_JSON));

        $sample = mt_rand(1, 16);
        $this->apiClient->offering()->retrieve($sample);
        $this->assertEquals('GET', $this->history[0]['request']->getMethod());
        $this->assertEquals('/v2/yielders/offerings/' . $sample, $this->history[0]['request']->getRequestTarget());
    }

    public function testCreate()
    {
        $this->mockHandler->append(new Response(201, $this::HEADER_JSON));

        $this->apiClient->offering()->create([]);
        $this->assertEquals('POST', $this->history[0]['request']->getMethod());
        $this->assertEquals(201, $this->history[0]['response']->getStatusCode());
        $this->assertEquals('/v2/yielders/offerings', $this->history[0]['request']->getRequestTarget());
    }
}
