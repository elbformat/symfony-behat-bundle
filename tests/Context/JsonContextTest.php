<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Behat\Gherkin\Node\PyStringNode;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Context\JsonContext;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class JsonContextTest extends TestCase
{
    use DomTrait;
    use ExpectNotToPerformAssertionTrait;

    protected ?KernelInterface $kernel = null;
    protected ?JsonContext $jsonContext = null;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->state = new State();
        $this->jsonContext = new JsonContext(kernel: $this->kernel, arrayComp: new ArrayDeepCompare(), state: $this->state);
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
                if ('json' !== $request->getContentTypeFormat()) {
                    return false;
                }
                if (implode("\n", $postData) !== $request->getContent()) {
                    return false;
                }

                return true;
            }))
            ->willReturn(new Response(''));

        $this->jsonContext->iMakeARequestWithJsonDataTo('POST', '/test', new PyStringNode($postData, 1));
    }

    public function testISendARequestToWithHeaders(): void
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
                if ('json' !== $request->getContentTypeFormat()) {
                    return false;
                }
                if (implode("\n", $postData) !== $request->getContent()) {
                    return false;
                }

                return true;
            }))
            ->willReturn(new Response(''));

        $this->jsonContext->iMakeARequestWithJsonDataTo('POST', '/test', new PyStringNode($postData, 1));
    }

    public function testTheResponseJsonMatches(): void
    {
        $this->setDom('{"hello":"world"}');
        $this->expectNotToPerformAssertions();
        $this->jsonContext->theResponseJsonMatches(new PyStringNode(['{"hello":"world"}'], 0));
    }

    public function testTheResponseJsonMatchesWithWrongOrder(): void
    {
        $this->setDom('{"hello":"world", "goodbye":"world", "a":1}');
        $this->expectNotToPerformAssertions();
        $this->jsonContext->theResponseJsonMatches(new PyStringNode(['{"goodbye":"world", "a":1, "hello":"world"}'], 0));
    }

    public function testTheResponseJsonMatchesFail(): void
    {
        $this->setDom('{"hello":"world"}');
        $this->expectExceptionMessage("{\n    \"hello\": \"world\"\n}\ngoodbye: Missing");
        $this->jsonContext->theResponseJsonMatches(new PyStringNode(['{"goodbye":"world"}'], 0));
    }

    public function testTheResponseJsonContains(): void
    {
        $this->setDom('{"hello":"world","number":42}');
        $this->expectNotToPerformAssertions();
        $this->jsonContext->theResponseJsonContains(new PyStringNode(['{"hello":"world"}'], 0));
    }

    public function testTheResponseJsonContainsFail(): void
    {
        $this->setDom('{"hello":"world","number":42}');
        $this->expectExceptionMessage("{\n    \"hello\": \"world\",\n    \"number\": 42\n}\ngoodbye: Missing");
        $this->jsonContext->theResponseJsonContains(new PyStringNode(['{"goodbye":"world"}'], 0));
    }

    public function testTheResponseJsonContainsNoArray(): void
    {
        $this->setDom('42');
        $this->expectExceptionMessage("Only arrays can contain something. Got integer");
        $this->jsonContext->theResponseJsonContains(new PyStringNode(['{"goodbye":"world"}'], 0));
    }

    public function testTheResponseJsonContainsNoArrayExpected(): void
    {
        $this->setDom('{"hello":"world","number":42}');
        $this->expectExceptionMessage("Only arrays can be contained. Got integer");
        $this->jsonContext->theResponseJsonContains(new PyStringNode(['42'], 0));
    }
}
