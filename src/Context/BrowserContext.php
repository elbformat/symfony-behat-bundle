<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Behat\Step\When;
use DOMElement;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Browser\StateFactory;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

use function json_decode;

/**
 * This Context is adapted from Mink Context but with less overhead by using the Kernel directly.
 * See https://github.com/Behat/MinkExtension/blob/master/src/Behat/MinkExtension/Context/MinkContext.php for more
 * methods
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class BrowserContext implements Context
{
    protected State $state;

    public function __construct(
        protected KernelInterface $kernel,
        protected StateFactory $stateFactory,
        protected string $projectDir,
        protected StringCompare $strComp,
        protected ArrayDeepCompare $arrayComp,
    ) {
        $this->resetState();
    }

    #[BeforeScenario]
    public function resetState(): void
    {
        $this->state = $this->stateFactory->newState();
    }

    #[When('I visit :page')]
    #[When('I navigate to :page')]
    #[When('I open :page')]
    #[When('I go to :page')]
    #[When('I am on :page')]
    public function iVisit(string $page): void
    {
        $this->doRequest($this->buildRequest($page));
    }

    #[When('I navigate to :page with http headers')]
    public function iNavigateToWithHeaders(string $page, TableNode $data): void
    {
        $server = [];
        /** @var array<string,string> $rowsHash */
        $rowsHash = $data->getRowsHash();
        foreach ($rowsHash as $key => $val) {
            $keyName = 'HTTP_'.strtoupper(str_replace('-', '_', $key));
            $server[$keyName] = $val;
        }
        $this->doRequest($this->buildRequest($page, 'GET', $server));
    }

    #[When('I send a :method request to :url')]
    #[When('I make a :method request to :url')]
    public function iSendARequestTo(string $method, string $url, ?PyStringNode $data = null): void
    {
        $server = [];
        if ($data) {
            $server['CONTENT_TYPE'] = 'application/json';
        }
        $this->doRequest($this->buildRequest($url, $method, $server, $data ? $data->getRaw() : null));
    }

    #[When('I follow the redirect')]
    public function iFollowTheRedirect(): void
    {
        $response = $this->state->getResponse();
        $code = $response->getStatusCode();
        if ($code >= 400 || $code < 300) {
            throw new \DomainException('No redirect code found: Code '.$code);
        }
        $targetUrl = (string)$response->headers->get('Location');
        // This is not url, not even a path. Not RFC compliant but we need to handle it either way
        if (str_starts_with($targetUrl, '?')) {
            $targetUrl = $this->state->getRequest()->getUri().$targetUrl;
        }
        $this->doRequest($this->buildRequest($targetUrl));
    }

    #[When('I use form :name')]
    #[Then('the page contains a form named :name')]
    public function thePageContainsAFormNamed(string $name): void
    {
        $crawler = $this->getCrawler();
        $form = $crawler->filterXpath(sprintf('//form[@name="%s"]', $name));
        if (!$form->count()) {
            throw $this->createNotFoundException('Form', $crawler->filterXPath('//form'));
        }
        $this->state->setLastForm($form);
    }

    #[When('I fill :value into :name')]
    #[When('I select :name radio button with value :value')]
    public function iFillInto(string $value, string $name): void
    {
        $formField = $this->state->getLastForm()
            ->get($name);
        if (!$formField instanceof FormField) {
            throw new \DomainException(sprintf('%s is not a single form field.', $name));
        }
        $formField->setValue($value);
    }

    #[When('I check :name checkbox')]
    public function iCheckCheckbox(string $name): void
    {
        /** @var ChoiceFormField $cb */
        $formField = $this->state->getLastForm()
            ->get($name);
        if (!$formField instanceof ChoiceFormField) {
            throw new \DomainException(sprintf('%s is not a choice form field', $name));
        }
        $formField->tick();
    }

    #[When('I select :value from :name')]
    public function iSelectFrom(string $value, string $name): void
    {
        $select = $this->state->getLastForm()
            ->get($name);
        if (!$select instanceof ChoiceFormField) {
            throw new \DomainException(sprintf('%s is not a choice form field', $name));
        }
        if (str_contains($value, ',')) {
            $value = explode(',', $value);
        }
        $select->select($value);
    }

    #[When('I submit the form')]
    public function iSubmitTheForm(): void
    {
        $form = $this->state->getLastForm();

        $this->doRequest($this->buildRequest($form->getUri(), $form->getMethod(), [], null, $form->getPhpValues(), $form->getPhpFiles()));
    }

    #[When('I select :fixture upload at :name')]
    public function iSelectUploadAt(string $fixture, string $name): void
    {
        $field = $this->state->getLastForm()
            ->get($name);
        if (!$field instanceof FileFormField) {
            throw new \DomainException(sprintf('%s is not a file form field', $name));
        }
        if (!file_exists($this->projectDir.'/'.$fixture)) {
            throw new \DomainException(sprintf('Fixture file not found at %s', $this->projectDir.'/'.$fixture));
        }
        $field->upload($this->projectDir.'/'.$fixture);
    }

    #[Then('/^the response status code is (?P<code>\d+)$/')]
    #[Then('The page shows up')]
    public function theResponseStatusCodeIs(string $code = '200'): void
    {
        $response = $this->state->getResponse();
        if ($response->getStatusCode() !== (int)$code) {
            throw new \RuntimeException('Received '.$response->getStatusCode());
        }
    }

    #[Then('the response has http headers')]
    public function theResponseHasHttpHeaders(TableNode $table): void
    {
        $response = $this->state->getResponse();
        /** @var array<mixed,array<int,string>> $headers */
        $headers = $response->headers->all();
        foreach ($this->getTableData($table) as $expectedHeader => $expectedValue) {
            foreach ($headers as $key => $values) {
                if (strtolower((string)$key) === strtolower($expectedHeader)) {
                    foreach ($values as $value) {
                        if ($this->strComp->stringContains($value, $expectedValue)) {
                            continue 3;
                        }
                    }
                }
            }

            $foundHeaders = [];
            foreach ($headers as $key => $values) {
                foreach ($values as $value) {
                    $foundHeaders[] = $key.': '.$value;
                }
            }

            throw new \DomainException("Header not found or not matching. Found \n  ".implode("\n  ", $foundHeaders));
        }
    }

    #[Then('I am being redirected to :url')]
    public function iAmBeingRedirectedTo(string $url): void
    {
        $response = $this->state->getResponse();
        $httpCode = $response->getStatusCode();
        if (!\in_array($httpCode, [301, 302, 303, 307, 308], true)) {
            throw new \DomainException(sprintf('Wrong HTTP Code, got %d', $httpCode));
        }

        foreach ($response->headers->all() as $key => $val) {
            if ('location' === strtolower((string)$key)) {
                $val0 = ($val[0] ?? '');
                if (!$this->strComp->stringEquals($val0, $url)) {
                    throw new \DomainException('Wrong redirect target: '.$val0);
                }

                return;
            }
        }
        throw new \DomainException('No location header found');
    }

    #[Then('I see :text')]
    public function iSee(string $text): void
    {
        $ex = $this->containsText($text);
        if ($ex) {
            throw $ex;
        }
    }

    #[Then('/^(?:|I )don\'t see "(?P<text>(?:[^"]|\\")*)"$/')]
    public function iDontSee(string $text): void
    {
        $ex = $this->containsText($text);
        if (null === $ex) {
            throw new \DomainException('Text found');
        }
    }

    #[Then('I see a(n) :tag tag')]
    #[Then('I see a(n) :tag tag :content')]
    public function iSeeATag(string $tag, ?TableNode $table = null, ?string $content = null, ?PyStringNode $multiLineContent = null): void
    {
        $ex = $this->mustContainTag($tag, $this->getTableData($table), $multiLineContent ? $multiLineContent->getRaw() : $content);
        if ($ex) {
            throw $ex;
        }
    }

    #[Then('I don\'t see a(n) :tag tag')]
    #[Then('I don\'t see a(n) :tag tag :content')]
    public function iDontSeeATag(string $tag, ?TableNode $table = null, ?string $content = null, ?PyStringNode $multiLineContent = null): void
    {
        $ex = $this->mustContainTag($tag, $this->getTableData($table), $multiLineContent ? $multiLineContent->getRaw() : $content);
        if (null === $ex) {
            throw new \DomainException('Tag found');
        }
    }

    #[Then('the form contains an input field')]
    public function theFormContainsAnInputField(TableNode $attribs): void
    {
        $inputs = $this->state->getLastFormCrawler()
            ->filterXPath('//input');

        /** @var DOMElement $input */
        foreach ($inputs as $input) {
            foreach ($this->getTableData($attribs) as $attrName => $attrVal) {
                if (!$this->strComp->stringEquals($input->getAttribute($attrName), $attrVal)) {
                    continue 2;
                }
            }

            return;
        }

        throw $this->createNotFoundException('input', $inputs);
    }

    #[Then('the response json matches')]
    public function theResponseJsonMatches(PyStringNode $string): void
    {
        $content = $this->state->getResponse()->getContent() ?: '';
        /** @var mixed $expected */
        $expected = json_decode($string->getRaw(), true, 512, JSON_THROW_ON_ERROR);
        /** @var mixed $got */
        $got = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if ($this->arrayComp->arrayEquals($expected, $got)) {
            return;
        }
        $gotJson = json_encode($got, JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        throw new \DomainException(sprintf("Got\n%s\n%s", $gotJson, $this->arrayComp->getDifference()));
    }

    #[Then('the response json contains')]
    public function theResponseJsonContains(PyStringNode $string): void
    {
        $content = $this->state->getResponse()->getContent() ?: '';
        $expected = json_decode($string->getRaw(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($expected)) {
            throw new \DomainException(sprintf('Only arrays can be contained. Got %s', gettype($expected)));
        }
        $got = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($got)) {
            throw new \DomainException(sprintf('Only arrays can contain something. Got %s', gettype($got)));
        }
        $dc = new ArrayDeepCompare();
        if ($dc->arrayContains($got, $expected)) {
            return;
        }
        $gotJson = json_encode($got, JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        throw new \DomainException(sprintf("Got\n%s\n%s", $gotJson, $dc->getDifference()));
    }


    /*************/
    /* Internals */
    /*************/

    protected function getCrawler(): Crawler
    {
        return new Crawler((string)$this->state->getResponse()
            ->getContent(), $this->state->getRequest()
            ->getUri());
    }

    protected function containsText(string $text): ?\DomainException
    {
        $regex = '/'.preg_quote($text, '/').'/ui';
        $actual = (string)$this->state->getResponse()
            ->getContent();
        if (!preg_match($regex, $actual)) {
            return new \DomainException('Text not found');
        }

        return null;
    }

    /** @param array<string,string> $attr */
    protected function mustContainTag(string $tagName, array $attr = [], ?string $content = null): ?\DomainException
    {
        $crawler = $this->getCrawler();
        $xPath = '//'.$tagName;
        foreach ($attr as $attrName => $attrVal) {
            $xPath .= sprintf('[@%s="%s"]', $attrName, $attrVal);
        }
        $elements = $crawler->filterXPath($xPath);

        if (!$elements->count()) {
            return $this->createNotFoundException('Tag', $crawler->filterXPath('//'.$tagName));
        }

        // Check content
        if (null !== $content) {
            $content = trim($content);
            /** @var DOMElement $elem */
            foreach ($elements as $elem) {
                if ($this->strComp->stringEquals(trim($elem->textContent), $content)) {
                    return null;
                }
            }

            return $this->createNotFoundException('Tag with content', $crawler->filterXPath('//'.$tagName));
        }

        return null;
    }

    protected function doRequest(Request $request): void
    {
        // Reboot kernel
        $this->kernel->shutdown();
        $response = $this->kernel->handle($request);
        $this->state->update($request, $response);
    }

    protected function createNotFoundException(string $what, ?Crawler $fallbacks = null): \DomainException
    {
        $errMsg = sprintf('%s not found.', $what);
        if (null !== $fallbacks) {
            $names = [];
            foreach ($fallbacks as $fallback) {
                $doc = $fallback->ownerDocument;
                $names[] = $doc ? $doc->saveXML($fallback) : '<unknown>';
            }
            switch (\count($names)) {
                case 0:
                    break;
                case 1:
                    $errMsg .= sprintf(' Did you mean "%s"?', $names[0]);
                    break;
                default:
                    $errMsg .= sprintf(" Did you mean one of the following?\n%s", implode("\n", $names));
                    break;
            }
        }

        return new \DomainException($errMsg);
    }

    /**
     * @return array<string,string>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    protected function getTableData(?TableNode $table): array
    {
        if (null === $table) {
            return [];
        }

        return $table->getRowsHash();
    }

    /** @param array<string,string> $server */
    protected function buildRequest(string $uri, string $method = 'GET', array $server = [], ?string $content = null, array $parameters = [], array $files = []): Request
    {
        $server['SCRIPT_FILENAME'] = $server['SCRIPT_FILENAME'] ?? 'index.php';

        return Request::create($uri, $method, $parameters, $this->state->getCookies(), $this->convertFileInformation($files), $server, $content);
    }

    // Copied from FileBag and modified to enable test mode
    protected function convertFileInformation(array $file)
    {
        $keys = array_keys($file);
        sort($keys);

        $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];
        if ($fileKeys === $keys) {
            if (\UPLOAD_ERR_NO_FILE === $file['error']) {
                $file = null;
            } else {
                $file = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error'], true);
            }
        } else {
            $file = array_map(fn ($v) => $v instanceof UploadedFile || \is_array($v) ? $this->convertFileInformation($v) : $v, $file);
            if (array_keys($keys) === $keys) {
                $file = array_filter($file);
            }
        }

        return $file;
    }
}
