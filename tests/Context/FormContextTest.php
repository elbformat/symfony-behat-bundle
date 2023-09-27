<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Context\FormContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class FormContextTest extends TestCase
{
    use DomTrait;
    use ExpectNotToPerformAssertionTrait;

    protected ?KernelInterface $kernel = null;
    protected ?FormContext $formContext = null;
    protected ?State $state = null;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->state = new State();
        $this->formContext = new FormContext(kernel: $this->kernel, state: $this->state, projectDir: __DIR__.'/../..', strComp: new StringCompare());
    }

    public function testThePageContainsAFormNamed(): void
    {
        $this->setDom('<form name="hello"></form>');
        $this->formContext->thePageContainsAFormNamed('hello');
        $this->assertInstanceOf(Form::class, $this->state->getLastForm());
        $this->assertInstanceOf(Crawler::class, $this->state->getLastFormCrawler());
    }

    public function testThePageContainsAFormNamedFail(): void
    {
        $this->setDom('<form name="otherform"></form>');
        $this->expectException(\DomainException::class);
        $this->formContext->thePageContainsAFormNamed('hello');
    }

    public function testIFillInto(): void
    {
        $crawler = new Crawler('<form><input type="text" name="form[text]"/></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->formContext->iFillInto('test', 'form[text]');
        $this->assertEquals('test', $this->state->getLastForm()->get('form[text]')->getValue());
    }

    public function testIFillIntoAmbiguous(): void
    {
        $crawler = new Crawler('<form><input name="form[text][]"/><input name="form[text][]"/></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectExceptionMessage('form[text] is not a single form field');
        $this->formContext->iFillInto('test', 'form[text]');
    }

    public function testIFillIntoNoInput(): void
    {
        $crawler = new Crawler('<form></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectException(\InvalidArgumentException::class);
        $this->formContext->iFillInto('test', 'form[text]');
    }

    public function testICheckCheckbox(): void
    {
        $crawler = new Crawler('<form><input type="checkbox" name="form[check]" value="an"/></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->formContext->iCheckCheckbox('form[check]');
        $this->assertTrue($this->state->getLastForm()->get('form[check]')->hasValue());
    }

    public function testICheckCheckboxWrongType(): void
    {
        $crawler = new Crawler('<form><input type="text" name="form[check]" value="an"/></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectExceptionMessage('form[check] is not a choice form field');
        $this->formContext->iCheckCheckbox('form[check]');
    }

    public function testISelectFrom(): void
    {
        $crawler = new Crawler('<form><select name="form[selection]"><option value="a">Option A</option></select></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->formContext->iSelectFrom('a', 'form[selection]');
        $this->assertEquals('a', $this->state->getLastForm()->get('form[selection]')->getValue());
    }

    public function testISelectFromMultiple(): void
    {
        $crawler = new Crawler('<form><select name="form[selection]" multiple><option value="a">Option A</option><option value="b">Option B</option></select></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->formContext->iSelectFrom('a,b', 'form[selection]');
        $this->assertEquals(['a', 'b'], $this->state->getLastForm()->get('form[selection]')->getValue());
    }

    public function testISelectFromNoChoice(): void
    {
        $crawler = new Crawler('<form><input type="text" name="form[selection]" /></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectExceptionMessage('form[selection] is not a choice form field');
        $this->formContext->iSelectFrom('a', 'form[selection]');
    }

    public function testISubmitTheForm(): void
    {
        $dom = '<form action="/submit" method="post"><input type="text" name="lorem" value="ipsum"></form>';
        $this->setDom($dom);
        $crawler = new Crawler($dom, 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->kernel->expects($this->once())->method('handle')->with($this->callback(function (Request $request) {
            if ('/submit' !== $request->getRequestUri()) {
                return false;
            }
            if ('POST' !== $request->getMethod()) {
                return false;
            }
            if ('ipsum' !== $request->request->get('lorem')) {
                return false;
            }

            return true;
        }))->willReturn(new Response('Redirect Target'));
        $this->formContext->iSubmitTheForm();
    }

    public function testISelectUploadAt(): void
    {
        $crawler = new Crawler('<form><input type="file" name="form[file]" /></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->formContext->iSelectUploadAt('tests/fixtures/1px.jpg', 'form[file]');
        $uplValue = $this->state->getLastForm()->get('form[file]')->getValue();
        $this->assertIsArray($uplValue);
        $this->assertEquals('1px.jpg', $uplValue['name']);
    }

    public function testISelectUploadAtNotAnUpload(): void
    {
        $crawler = new Crawler('<form><input type="text" name="form[file]" /></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectExceptionMessage('form[file] is not a file form field');
        $this->formContext->iSelectUploadAt('tests/fixtures/1px.png', 'form[file]');
    }

    public function testISelectUploadAtMissingFixture(): void
    {
        $crawler = new Crawler('<form><input type="file" name="form[file]" /></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectExceptionMessageMatches('/Fixture file not found/');
        $this->expectExceptionMessageMatches('#tests/fixtures/1px.png#');
        $this->formContext->iSelectUploadAt('tests/fixtures/1px.png', 'form[file]');
    }

    public function testTheFormContainsAnInputField(): void
    {
        $crawler = new Crawler('<form><input type="text" name="form[text]" /></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $tableData = [
            1 => ['type', 'text'],
            2 => ['name', 'form[text]'],
        ];
        $this->expectNotToPerformAssertions();
        $this->formContext->theFormContainsAnInputField(new TableNode($tableData));
    }

    public function testTheFormContainsAnInputFieldWrongType(): void
    {
        $crawler = new Crawler('<form><input type="password" name="form[text]"></form>', 'http://localhost/');
        $this->state->setLastForm($crawler->filterXPath('//form'));
        $this->expectExceptionMessage('input not found. Did you mean "<input type="password" name="form[text]"/>"?');
        $tableData = [
            1 => ['type', 'text'],
            2 => ['name', 'form[text]'],
        ];
        $this->formContext->theFormContainsAnInputField(new TableNode($tableData));
    }
}
