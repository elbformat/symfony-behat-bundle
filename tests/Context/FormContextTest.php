<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Context\FormContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class FormContextTest extends TestCase
{
    use DomTrait;
    use ExpectNotToPerformAssertionTrait;

    protected ?KernelInterface $kernel = null;
    protected ?FormContext $formContext = null;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->state = new State();
        $this->formContext = new FormContext(kernel: $this->kernel, state: $this->state, projectDir: __DIR__.'/../..', strComp: new StringCompare());
    }

    public function testThePageContainsAFormNamedFail(): void
    {
        $this->setDom('<form name="otherform"></form>');
        $this->expectException(\DomainException::class);
        $this->formContext->thePageContainsAFormNamed('hello');
    }

    public function testIFillInto(): void
    {
        $this->setDom('<form name="test"><input name="form[text]"/></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iFillInto('test', 'form[text]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $this->assertEquals('test', $rq->query->all()['form']['text']);
    }

    public function testIFillIntoAmbiguous(): void
    {
        $this->setDom('<form name="test"><input name="form[text][]"/><input name="form[text][]"/></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectExceptionMessage('form[text] is not a single form field');
        $this->formContext->iFillInto('test', 'form[text]');
    }

    public function testIFillIntoNoInput(): void
    {
        $this->setDom('<form name="test"></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectException(\InvalidArgumentException::class);
        $this->formContext->iFillInto('test', 'form[text]');
    }

    public function testIClear(): void
    {
        $this->setDom('<form name="test"><input name="form[text]" value="test" /></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iClearField('form[text]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $this->assertEquals('', $rq->query->all()['form']['text']);
    }

    public function testICheckCheckbox(): void
    {
        $this->setDom('<form name="test"><input type="checkbox" name="form[check]" value="an"/></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iCheckCheckbox('form[check]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $this->assertSame('an', $rq->query->all()['form']['check']);
    }

    public function testICheckCheckboxWrongType(): void
    {
        $this->setDom('<form name="test"><input type="text" name="form[check]" value="an"/></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectExceptionMessage('form[check] is not a choice form field');
        $this->formContext->iCheckCheckbox('form[check]');
    }

    public function testIUncheckCheckbox(): void
    {
        $this->setDom('<form name="test"><input type="checkbox" name="form[check]" value="an" checked="checked"/></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iUncheckCheckbox('form[check]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $this->assertSame('', $rq->query->all()['form']['check'] ?? '');
    }

    public function testISelectFrom(): void
    {
        $this->setDom('<form name="test"><select name="form[selection]"><option value="a">Option A</option></select></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iSelectFrom('a', 'form[selection]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $this->assertEquals('a', $rq->query->all()['form']['selection']);
    }

    public function testISelectFromMultiple(): void
    {
        $this->setDom('<form name="test" method="post"><select name="form[selection]" multiple><option value="a">Option A</option><option value="b">Option B</option></select></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iSelectFrom('a,b', 'form[selection]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $this->assertEquals(['a', 'b'], $rq->request->all()['form']['selection']);
    }

    public function testISelectFromNoChoice(): void
    {
        $this->setDom('<form name="test"><input type="text" name="form[selection]" /></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectExceptionMessage('form[selection] is not a choice form field');
        $this->formContext->iSelectFrom('a', 'form[selection]');
    }

    public function testISubmitTheForm(): void
    {
        $dom = '<form name="test" action="/submit" method="post"><input type="text" name="lorem" value="ipsum"></form>';
        $this->setDom($dom);
        $this->formContext->thePageContainsAFormNamed('test');
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
        $this->setDom('<form name="test" method="post"><input type="file" name="form[file]" /></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->formContext->iSelectUploadAt('tests/fixtures/1px.jpg', 'form[file]');
        $this->kernel->method('handle')->willReturn(new Response());
        $this->formContext->iSubmitTheForm();
        $rq = $this->state->getRequest();
        $uplFile = $rq->files->get('form')['file'];
        $this->assertInstanceOf(UploadedFile::class, $uplFile);
        $this->assertEquals('1px.jpg', $uplFile->getClientOriginalName());
    }

    public function testISelectUploadAtNotAnUpload(): void
    {
        $this->setDom('<form name="test"><input type="text" name="form[file]" /></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectExceptionMessage('form[file] is not a file form field');
        $this->formContext->iSelectUploadAt('tests/fixtures/1px.png', 'form[file]');
    }

    public function testISelectUploadAtMissingFixture(): void
    {
        $this->setDom('<form name="test"><input type="file" name="form[file]" /></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectExceptionMessageMatches('/Fixture file not found/');
        $this->expectExceptionMessageMatches('#tests/fixtures/1px.png#');
        $this->formContext->iSelectUploadAt('tests/fixtures/1px.png', 'form[file]');
    }

    public function testTheFormContainsAnInputField(): void
    {
        $this->setDom('<form name="test"><input type="text" name="form[text]"></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $tableData = [
            1 => ['type', 'text'],
            2 => ['name', 'form[text]'],
        ];
        $this->expectNotToPerformAssertions();
        $this->formContext->theFormContainsAnInputField(new TableNode($tableData));
    }

    public function testTheFormContainsAnInputFieldWrongType(): void
    {
        $this->setDom('<form name="test"><input type="password" name="form[text]"></form>');
        $this->formContext->thePageContainsAFormNamed('test');
        $this->expectExceptionMessage('input not found. Did you mean "<input type="password" name="form[text]"/>"?');
        $tableData = [
            1 => ['type', 'text'],
            2 => ['name', 'form[text]'],
        ];
        $this->formContext->theFormContainsAnInputField(new TableNode($tableData));
    }
}
