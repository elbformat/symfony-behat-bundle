<?php

namespace Context;

use Behat\Gherkin\Node\PyStringNode;
use Elbformat\SymfonyBehatBundle\Context\AbstractApiContext;
use Elbformat\SymfonyBehatBundle\HttpClient\MockClientCallback;
use Elbformat\SymfonyBehatBundle\Tests\Context\ExpectNotToPerformAssertionTrait;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Context\MyApiContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

class AbstractApiContextTest extends TestCase
{
    use ExpectNotToPerformAssertionTrait;

    protected AbstractApiContext $context;

    protected function setUp(): void
    {
        $this->context = new MyApiContext();
        MockClientCallback::reset();
    }

    public function testReset(): void
    {
        MockClientCallback::addResponse('GET', '/', new MockResponse());
        $mcc = new MockClientCallback();
        $mcc->__invoke('GET', '/');
        $this->context->reset();
        $this->assertSame([], MockClientCallback::getRequests());
    }

    public function testAddMock(): void
    {
        $this->context->addMock('/');
        $mcc = new MockClientCallback();
        $mcc->__invoke('GET', '/');
        $rq = MockClientCallback::getRequest('GET', '/');
        $this->assertSame([], $rq);
    }

    public function testAddMockWithContent(): void
    {
        $this->context->addMock('/', rawHttp: new PyStringNode(['Lorem Ipsum'], 0));
        $mcc = new MockClientCallback();
        $mcc->__invoke('GET', '/');
        $rq = MockClientCallback::getRequest('GET', '/');
        $this->assertSame([], $rq);
    }

    public function testAddMockWithHeaders(): void
    {
        $rawHttp = <<<EOL
HTTP/1.1 200 OK
Content-Type: application/json

{"text":"hello world"}
EOL;

        $this->context->addMock('/', rawHttp: new PyStringNode([$rawHttp], 0));
        $mcc = new MockClientCallback();
        $mcc->__invoke('GET', '/');
        $rq = MockClientCallback::getRequest('GET', '/');
        $this->assertSame([], $rq);
    }

    public function testAssertApiCall(): void
    {
        $this->performRequest('/test');
        $this->context->assertCall('/test');
        $this->expectNotToPerformAssertions();
    }

    public function testAssertApiCallWithContent(): void
    {
        $this->performRequest('/test', ['text' => 'Lorem Ipsum']);
        $this->context->assertCall('/test', 'GET', new PyStringNode(['{"text":"Lorem Ipsum"}'], 0));
        $this->expectNotToPerformAssertions();
    }

    public function testAssertApiCallFailed(): void
    {
        $this->performRequest('/anotherurl');
        $this->expectExceptionMessageMatches('/^No response found for GET \/test/');
        $this->context->assertCall('/test');
    }

    public function testAssertApiCallWithContentFailed(): void
    {
        $this->performRequest('/test', ['text' => 'Dolor sit']);
        $this->expectExceptionMessage('(string) Dolor sit != (string) Lorem Ipsum');
        $this->context->assertCall('/test', 'GET', new PyStringNode(['{"text":"Lorem Ipsum"}'], 0));
    }

    public function testAssertNoApiCall(): void
    {
        $this->context->assertNoCall('/test');
        $this->expectNotToPerformAssertions();
    }
    public function testAssertNoApiCallFailed(): void
    {
        $this->performRequest('/test');
        $this->expectExceptionMessage('Api has been called');
        $this->context->assertNoCall('/test');
    }

    protected function performRequest(string $url, ?array $requestBody = null): void
    {
        MockClientCallback::addResponse('GET', $url, new MockResponse());
        $mcc = new MockClientCallback();
        $options = [];
        if (null !== $requestBody) {
            $options['body'] = json_encode($requestBody);
        }
        $mcc->__invoke('GET', $url, $options);
    }
}
