<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Behat\Step\When;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This Context is adapted from Mink Context but with less overhead by using the Kernel directly.
 * See https://github.com/Behat/MinkExtension/blob/master/src/Behat/MinkExtension/Context/MinkContext.php for more
 * methods.
 *
 * The scope of this context is HTTP request/response handling.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class HttpContext implements Context
{
    use RequestTrait;
    use TableTrait;

    public function __construct(
        protected KernelInterface $kernel,
        protected State $state,
        protected StringCompare $strComp,
    ) {

    }

    #[BeforeScenario]
    public function resetState(): void
    {
        $this->state->reset();
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
            $rawData = $data->getRaw();
            if (str_contains($rawData, "\n\n")) {
                [$headers,$rawData] = explode("\n\n", $rawData);
                foreach (explode("\n", $headers) as $headerRow) {
                    [$headerKey,$headerValue] = explode(':', $headerRow, 2);
                    $server['HTTP_'.strtoupper($headerKey)] = trim($headerValue);
                }
            }
        }
        /** @psalm-suppress MixedArgument false positive */
        $this->doRequest($this->buildRequest($url, $method, $server, $rawData ?? null));
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
        // Another non-rfc conform case - URLs without host
        if (str_starts_with($targetUrl, '/')) {
            $targetUrl = $this->state->getRequest()->getSchemeAndHttpHost() . $targetUrl;
        }
        $this->doRequest($this->buildRequest($targetUrl));
    }

    #[Then('/^the response status code is (?P<code>\d+)$/')]
    #[Then('the page shows up')]
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
                        if ($this->strComp->stringEquals($value, $expectedValue)) {
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
            if ('location' === strtolower($key)) {
                $val0 = ($val[0] ?? '');
                // Compare only the path
                if (str_starts_with($url, '/')) {
                    $parts = parse_url($val0);
                    $val0 = $parts['path'] ?? '';
                    $query = $parts['query'] ?? '';
                    if ('' !== $query) {
                        $val0 .= '?'.$query;
                    }
                }
                if (!$this->strComp->stringEquals($val0, $url)) {
                    throw new \DomainException('Wrong redirect target: '.$val0);
                }

                return;
            }
        }
        throw new \DomainException('No location header found');
    }
}
