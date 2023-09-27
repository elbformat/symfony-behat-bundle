<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Browser;

use Elbformat\SymfonyBehatBundle\Browser\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StateTest extends TestCase
{
    public function testReset(): void
    {
        $state = new State();
        $state->update(Request::create('/'), new Response('', 200, ['set-cookie' => ['hello=world']]));
        $state->reset();
        $this->assertCount(0, $state->getCookies());
        $this->expectExceptionMessage('No request was made yet');
        $state->getRequest();
    }

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
}
