<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\fixtures\Context;

use Behat\Gherkin\Node\PyStringNode;
use Elbformat\SymfonyBehatBundle\Context\AbstractApiContext;

class MyApiContext extends AbstractApiContext
{
    public function reset(): void
    {
        $this->resetMock();
    }

    public function addMock(string $url, string $method = 'GET', ?PyStringNode $rawHttp = null, int $code = 200): void
    {
        $this->addResponse($url, $method, $rawHttp, $code);
    }

    public function assertCall(string $url, string $method = 'GET', ?PyStringNode $content = null): void
    {
        $this->assertApiCall($url, $method, $content);
    }

    public function assertNoCall(string $url, string $method = 'GET'): void
    {
        $this->assertNoApiCall($url, $method);
    }
}
