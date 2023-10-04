<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Context\HtmlContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class HtmlContextTest extends TestCase
{
    use DomTrait;
    use ExpectNotToPerformAssertionTrait;

    protected ?KernelInterface $kernel = null;
    protected ?HtmlContext $htmlContext = null;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->state = new State();
        $this->htmlContext = new HtmlContext(state: $this->state, strComp: new StringCompare());
    }

    public function testIRemoveAttributeFrom(): void
    {
        $this->setDom('<p hidden="hidden">Hello World</p>');
        $this->htmlContext->iRemoveAttributeFrom('hidden', '//p');
        $this->assertEquals('<body><p>Hello World</p></body>', $this->state->getCrawler()->html());
    }
    public function testIRemoveAttributeFromNotFound(): void
    {
        $this->setDom('<p hidden="hidden">Hello World</p>');
        $this->expectExceptionMessage('DOM Element not found');
        $this->htmlContext->iRemoveAttributeFrom('hidden', '//div');
    }

    public function testISee(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectNotToPerformAssertions();
        $this->htmlContext->iSee('Hello World');
    }

    public function testISeeFails(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectException(\DomainException::class);
        $this->htmlContext->iSee('Bye World');
    }

    public function testIDontSee(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectNotToPerformAssertions();
        $this->htmlContext->iDontSee('Bye World');
    }

    public function testIDontSeeFails(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectException(\DomainException::class);
        $this->htmlContext->iDontSee('Hello World');
    }

    public function testISeeBefore(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectNotToPerformAssertions();
        $this->htmlContext->iSeeBefore('Hello', 'World');
    }

    public function testISeeBeforeFails(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectExceptionMessage('"World" found at Position 9, "Hello" at 3');
        $this->htmlContext->iSeeBefore('World', 'Hello');
    }

    public function testISeeATag(): void
    {
        $this->setDom('<a href="/test"></a>');
        $table = new TableNode([0 => ['href', '/test']]);
        $this->expectNotToPerformAssertions();
        $this->htmlContext->iSeeATag('a', $table);
    }

    public function testISeeATagWithContent(): void
    {
        $this->setDom('<a href="/test">Hello World</a>');
        $table = new TableNode([0 => ['href', '/test']]);
        $this->expectNotToPerformAssertions();
        $this->htmlContext->iSeeATag('a', $table, 'Hello World');
    }

    public function testISeeATagWithContentComplex(): void
    {
        $this->setDom('<a href="/test"> <span> Hello World </span> <span> </span> </a>');
        $table = new TableNode([0 => ['href', '/test']]);
        $this->expectNotToPerformAssertions();
        $this->htmlContext->iSeeATag('a', $table, 'Hello World');
    }

    public function testISeeATagFails(): void
    {
        $this->setDom('<a href="/test">Hello World</a>');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tag not found. Did you mean "<a href="/test">Hello World</a>"?');
        $table = new TableNode([0 => ['href', '/notest']]);
        $this->htmlContext->iSeeATag('a', $table, 'Hello World');
    }

    public function testISeeATagFailsNoTag(): void
    {
        $this->setDom('<p>Hello World</p>');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tag not found.');
        $table = new TableNode([0 => ['href', '/notest']]);
        $this->htmlContext->iSeeATag('a', $table, 'Hello World');
    }

    public function testISeeATagFailsMultipleAlternatives(): void
    {
        $this->setDom('<p>Hello World</p><p>Bye World</p>');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Tag with content not found. Did you mean one of the following?\n<p>Hello World</p>\n<p>Bye World</p>");
        $this->htmlContext->iSeeATag('p', null, 'Mars');
    }

    public function testISeeATagWrongContent(): void
    {
        $this->setDom('<a href="/test">Hello World</a>');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tag with content not found. Did you mean "<a href="/test">Hello World</a>"?');
        $table = new TableNode([0 => ['href', '/test']]);
        $this->htmlContext->iSeeATag('a', $table, 'Bye World');
    }

    public function testIDontSeeATag(): void
    {
        $this->setDom('<a href="/test">Hello World</a>');
        $this->expectNotToPerformAssertions();
        $table = new TableNode([0 => ['href', '/test']]);
        $this->htmlContext->iDontSeeATag('a', $table, 'Bye World');
    }

    public function testIDontSeeATagFails(): void
    {
        $this->setDom('<a href="/test">Hello World</a>');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tag found');
        $table = new TableNode([0 => ['href', '/test']]);
        $this->htmlContext->iDontSeeATag('a', $table, 'Hello World');
    }
}
