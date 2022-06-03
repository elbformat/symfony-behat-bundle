<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use DOMElement;
use Psr\Container\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This Context is adapted from Mink Context but with less overhead by using the Kernel directly.
 * See https://github.com/Behat/MinkExtension/blob/master/src/Behat/MinkExtension/Context/MinkContext.php for more
 * methods
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class BrowserContext implements Context
{
//    use FormTrait;

    protected KernelInterface $kernel;
    protected ?Response $response = null;
    protected ?Request $request = null;

    /** @var array<string,string|null> */
    protected array $cookies = [];

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Opens specified page
     * Example: Given I am on "http://batman.com"
     * Example: And I am on "/articles/isBatmanBruceWayne"
     * Example: When I go to "/articles/isBatmanBruceWayne"
     *
     * @Given /^(?:|I )am on "(?P<page>[^"]+)"$/
     * @When /^(?:|I )go to "(?P<page>[^"]+)"$/
     * @When I navigate to :page
     */
    public function visit(string $page): void
    {
        $this->doRequest(Request::create($page, 'GET', [], $this->cookies));
    }

    /**
     * @When I send a :method request to :url
     * @When I make a :method request to :url
     */
    public function sendARequestTo(string $method, string $url, ?PyStringNode $data = null): void
    {
        $server = [];
        if ($data) {
            $server['CONTENT_TYPE'] = 'application/json';
        }
        $this->doRequest(Request::create($url, strtoupper($method), [], $this->cookies, [], $server, $data ? $data->getRaw() : null));
    }

    /**
     * @When I follow the redirect
     */
    public function iFollowtheRedirect(): void
    {
        if (null === $this->response) {
            throw new \DomainException('No request was made yet');
        }
        $code = $this->response->getStatusCode();
        if ($code >= 400 || $code < 300) {
            throw new \DomainException('No redirect code found: Code ' . $code);
        }
        $targetUrl = (string) $this->response->headers->get('Location');
        // This is not url, not even a path. Not RFC compliant but we need to handle it either way
        if (0 === strpos($targetUrl, '?')) {
            $targetUrl = $this->getRequest()->getUri().$targetUrl;
        }
        $this->doRequest(Request::create($targetUrl, 'GET', [], $this->cookies));
    }

    /**
     * Checks, that current page response status is equal to specified
     * Example: Then the response status code should be 200
     * Example: And the response status code should be 400
     *
     * @Then /^the response status code should be (?P<code>\d+)$/
     */
    public function assertResponseStatus(string $code): void
    {
        if ($this->response === null) {
            throw new \RuntimeException('No response received');
        }
        if ($this->response->getStatusCode() !== (int) $code) {
            throw new \RuntimeException('Received ' . $this->response->getStatusCode());
        }
    }

    /**
     * Checks, that page contains specified text
     * Example: Then I should see "Who is the Batman?"
     * Example: And I should see "Who is the Batman?"
     *
     * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)"$/
     */
    public function assertPageContainsText(string $text): void
    {
        $regex = '/' . preg_quote($text, '/') . '/ui';
        $actual = (string) $this->getResponse()->getContent();
        if (!preg_match($regex, $actual)) {
            throw new \DomainException('Text not found');
        }
    }

    /**
     * @Then /^(?:|I )should not see "(?P<text>(?:[^"]|\\")*)"$/
     */
    public function assertPageNotContainsText(string $text): void
    {
        try {
            $this->assertPageContainsText($text);
        } catch (\DomainException $e) {
            return;
        }
        throw new \DomainException('Text found');
    }

    /**
     * @Then I should see a(n) :tag tag
     * @Then I should see a(n) :tag tag :content
     */
    public function ishouldSeeATag(string $tag, ?TableNode $table = null, ?string $content = null, ?PyStringNode $multiLineContent = null): void
    {
        $this->mustContainTag($tag, $table ? $table->getRowsHash() : null, $multiLineContent ? $multiLineContent->getRaw() : $content);
    }

    /**
     * @Then I should not see a(n) :tag tag
     * @Then I should not see a(n) :tag tag :content
     */
    public function ishouldNotSeeATag(string $tag, ?TableNode $table = null, ?string $content = null, ?PyStringNode $multiLineContent = null): void
    {
        try {
            $this->mustContainTag($tag, $table ? $table->getRowsHash() : null, $multiLineContent ? $multiLineContent->getRaw() : $content);
        } catch (\DomainException $e) {
            return;
        }
        throw new \DomainException('Tag found');
    }

    public function getInternalContainer(): ContainerInterface
    {
        return $this->client->getContainer();
    }

    protected function getCrawler(): Crawler
    {
        return new Crawler((string) $this->getResponse()->getContent(), $this->getRequest()->getUri());
    }

    /** @param array<array-key,mixed>|null $attr */
    protected function mustContainTag(string $tagName, ?array $attr = null, ?string $content = null): void
    {
        $crawler = new Crawler((string)$this->getResponse()->getContent());
        $xPath = '//' . $tagName;
        if (null !== $attr) {
            foreach ($attr as $attrName => $attrVal) {
                $xPath .= sprintf('[@%s="%s"]', (string)$attrName, (string)$attrVal);
            }
        }
        $elements = $crawler->filterXPath($xPath);

        if (!$elements->count()) {
            $nearestTags = [];
            /** @var DOMElement $nearMatch */
            foreach ($crawler->filterXPath('//' . $tagName) as $nearMatch) {
                $attrs = [];
                foreach ($nearMatch->attributes as $domAttr) {
                    $attrs[] = sprintf('%s="%s"', $domAttr->name, $domAttr->value);
                }
                $nearestTags[] = sprintf('<%s %s>', $tagName, implode(' ', $attrs));
            }

            throw new \DomainException(sprintf("No matching %s tags found. Did you mean one of \n%s", $tagName, implode("\n", $nearestTags)));
        }

        // Check content
        if (null !== $content) {
            $content = trim($content);
            /** @var DOMElement $link */
            $foundContents = [];
            foreach ($elements as $link) {
                if ($content === trim($link->textContent)) {
                    return;
                }
                $foundContents[] = trim($link->textContent);
            }
            throw new \DomainException(sprintf("No matching content found for %s tag. Did you mean one of\n%s", $tagName, implode("\n", $foundContents)));
        }
    }

    protected function doRequest(Request $request): void
    {
        $this->request = $request;
        // Reboot kernel
        $this->kernel->shutdown();
        $this->response = $this->kernel->handle($request);
        foreach ($this->response->headers->getCookies() as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie->getValue();
        }
    }

    protected function getResponse(): Response
    {
        if (null === $this->response) {
            throw new \DomainException('No request was made yet.');
        }

        return $this->response;
    }

    protected function getRequest(): Request
    {
        if (null === $this->request) {
            throw new \DomainException('No request was made yet.');
        }

        return $this->request;
    }
}