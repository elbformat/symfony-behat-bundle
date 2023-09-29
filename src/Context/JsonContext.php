<?php

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Step\Then;
use Behat\Step\When;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use Symfony\Component\HttpKernel\KernelInterface;

class JsonContext implements Context
{
    use RequestTrait;

    public function __construct(
        protected KernelInterface $kernel,
        protected ArrayDeepCompare $arrayComp,
        protected State $state,
    ) {
    }

    #[When('I make a :method request with json data to :url')]
    public function iMakeARequestWithJsonDataTo(string $method, string $url, ?PyStringNode $data = null): void
    {
        $server = [];
        if ($data) {
            $rawData = $data->getRaw();
            $server['CONTENT_TYPE'] = 'application/json';
            if (str_contains($rawData, "\n\n")) {
                [$headers,$rawData] = explode("\n\n", $rawData);
                foreach (explode("\n", $headers) as $headerRow) {
                    [$headerKey,$headerValue] = explode(':', $headerRow, 2);
                    $server['HTTP_'.strtoupper($headerKey)] = trim($headerValue);
                }
            }
        }
        /** @psalm-suppress MixedArgument false positive? */
        $this->doRequest($this->buildRequest($url, $method, $server, $rawData ?? null));
    }

    #[Then('the response json matches')]
    public function theResponseJsonMatches(PyStringNode $string): void
    {
        $content = $this->state->getResponseContent();
        $expected = json_decode($string->getRaw(), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($expected)) {
            throw new \DomainException(sprintf('Only arrays can be matched. Got %s', gettype($expected)));
        }
        $got = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($got)) {
            throw new \DomainException(sprintf('Only arrays can be matched. Got %s', gettype($got)));
        }
        if ($this->arrayComp->arrayEquals($expected, $got)) {
            return;
        }
        $gotJson = json_encode($got, JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        throw new \DomainException(sprintf("Got\n%s\n%s", $gotJson, $this->arrayComp->getDifference()));
    }

    #[Then('the response json contains')]
    public function theResponseJsonContains(PyStringNode $string): void
    {
        $content = $this->state->getResponseContent();
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

    #[Then('the response json does not contain')]
    public function theResponseJsonDoesNotContain(PyStringNode $string): void
    {
        try {
            $this->theResponseJsonContains($string);
        } catch (\DomainException|\JsonException) {
            return;
        }
        throw new \DomainException('the response json contains exact this data');
    }
}
