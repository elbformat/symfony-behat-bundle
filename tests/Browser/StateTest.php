<?php

namespace Browser;

use Elbformat\SymfonyBehatBundle\Browser\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StateTest extends TestCase
{
    public function testUpdateCookies(): void
    {
        $state = new State();
        $state->update(Request::create('/'), new Response('', 200, ['set-cookie' => ['hello=world']]));
        $cookies = $state->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('world', $cookies['hello']);
    }

    public function testGetResponseNotSet(): void
    {
        $state = new State();
        $this->expectExceptionMessage('No request was made yet');
        $state->getResponse();
    }

    public function testGetRequestNotSet(): void
    {
        $state = new State();
        $this->expectExceptionMessage('No request was made yet');
        $state->getRequest();
    }

    public function testGetLastFormNotSet(): void
    {
        $state = new State();
        $this->expectExceptionMessage('No form was queried yet');
        $state->getLastForm();
    }
    public function testGetLastFormCrawlerNotSet(): void
    {
        $state = new State();
        $this->expectExceptionMessage('No form was queried yet');
        $state->getLastFormCrawler();
    }
}
