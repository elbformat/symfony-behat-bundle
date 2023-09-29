<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Context\HttpContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class HttpContextTest extends TestCase
{
    use ExpectNotToPerformAssertionTrait;

    protected ?KernelInterface $kernel = null;
    protected ?HttpContext $httpContext = null;
    protected ?State $state = null;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->state = new State();
        $this->httpContext = new HttpContext($this->kernel, $this->state, new StringCompare());
    }

    public function testIVisit(): void
    {
        $this->kernel->expects($this->once())->method('shutdown');
        $this->kernel->expects($this->once())->method('handle')->with($this->callback(function (Request $request) {
            return '/test' === $request->getPathInfo();
        }))->willReturn(new Response(''));
        $this->httpContext->iVisit('/test');
    }

    public function testINavigateToWithHeaders(): void
    {
        $this->kernel->expects($this->once())->method('shutdown');
        $this->kernel->expects($this->once())->method('handle')->with($this->callback(function (Request $request) {
            if ('/test' !== $request->getPathInfo()) {
                return false;
            }
            if ('de' !== $request->getPreferredLanguage(['en', 'de'])) {
                return false;
            }

            return true;
        }))->willReturn(new Response(''));
        $this->httpContext->iNavigateToWithHeaders('/test', new TableNode([
            0 => [
                'Accept-Language',
                'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        ]));
    }

    public function testISendARequestTo(): void
    {
        $postData = [
            '{',
            '  lorem":"ipsum"',
            '}',
        ];

        $this->kernel->expects($this->once())->method('shutdown');
        $this->kernel->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (Request $request) use ($postData) {
                if ('/test' !== $request->getPathInfo()) {
                    return false;
                }
                if ('POST' !== $request->getMethod()) {
                    return false;
                }
                if (implode("\n", $postData) !== $request->getContent()) {
                    return false;
                }

                return true;
            }))
            ->willReturn(new Response(''));

        $this->httpContext->iSendARequestTo('POST', '/test', new PyStringNode($postData, 1));
    }

    public function testIFollowTheRedirect(): void
    {
        $this->state->update(new Request(server:['HTTP_HOST' => 'localhost']), new Response('', 302, ['Location' => '/target']));
        $this->kernel->method('handle')->with($this->callback(function (Request $request) {
            return '/target' === $request->getPathInfo();
        }))->willReturn(new Response('Redirect Target'));
        $this->httpContext->iFollowTheRedirect();
        $this->assertEquals('Redirect Target', $this->state->getResponse()->getContent());
    }

    public function testIFollowTheRedirectQuery(): void
    {
        $this->state->update(Request::create('http://localhost'), new Response('', 302, ['Location' => '?success=true']));
        $this->kernel->method('handle')->with($this->callback(function (Request $request) {
            return '/?success=true' === $request->getRequestUri();
        }))->willReturn(new Response('Redirect Target'));
        $this->httpContext->iFollowTheRedirect();
        $this->assertEquals('Redirect Target', $this->state->getResponse()->getContent());
    }

    public function testIFollowTheRedirectFails(): void
    {
        $this->state->update(Request::create('/'), new Response('', 500));
        $this->expectException(\DomainException::class);
        $this->httpContext->iFollowTheRedirect();
    }

    public function testTheResponseStatusCodeIs(): void
    {
        $this->state->update(Request::create('/'), new Response('', 200));
        $this->httpContext->theResponseStatusCodeIs('200');
        $this->expectNotToPerformAssertions();
    }

    public function testTheResponseStatusCodeIsFails(): void
    {
        $this->state->update(Request::create('/'), new Response('', 404));
        $this->expectException(\RuntimeException::class);
        $this->httpContext->theResponseStatusCodeIs('200');
    }

    public function testTheResponseHasHttpHeaders(): void
    {
        $this->state->update(Request::create('/'), new Response('', 200, ['Content-Type' => 'application/json']));
        $table = new TableNode([0 => ['content-type', 'application/json']]);
        $this->httpContext->theResponseHasHttpHeaders($table);
        $this->expectNotToPerformAssertions();
    }

    public function testTheResponseHasHttpHeadersFails(): void
    {
        $this->state->update(Request::create('/'), new Response('', 200, ['Content-Type' => 'text/plain']));
        $table = new TableNode([0 => ['content-type', 'application/json']]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Header not found or not matching');
        $this->expectExceptionMessage('content-type: text/plain');
        $this->httpContext->theResponseHasHttpHeaders($table);
    }

    public function testIAmBeingRedirectedTo(): void
    {
        $this->state->update(Request::create('/'), new Response('', 302, ['Location' => '/redirecttarget']));
        $this->httpContext->iAmBeingRedirectedTo('/redirecttarget');
        $this->expectNotToPerformAssertions();
    }

    public function testIAmBeingRedirectedToWrongCode(): void
    {
        $this->expectExceptionMessage('Wrong HTTP Code, got 200');
        $this->state->update(Request::create('/'), new Response('', 200));
        $this->httpContext->iAmBeingRedirectedTo('/redirecttarget');
        $this->expectNotToPerformAssertions();
    }

    public function testIAmBeingRedirectedToWrongLocation(): void
    {
        $this->expectExceptionMessage('Wrong redirect target: /anotherone');
        $this->state->update(Request::create('/'), new Response('', 302, ['Location' => '/anotherone']));
        $this->httpContext->iAmBeingRedirectedTo('/redirecttarget');
        $this->expectNotToPerformAssertions();
    }

    public function testIAmBeingRedirectedToNoLocation(): void
    {
        $this->state->update(Request::create('/'), new Response('', 302, ));
        $this->expectExceptionMessage('No location header found');
        $this->httpContext->iAmBeingRedirectedTo('/redirecttarget');
    }
}
