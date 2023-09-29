<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use Elbformat\SymfonyBehatBundle\HttpClient\MockClientCallback;
use Symfony\Component\HttpClient\Response\MockResponse;

abstract class AbstractApiContext implements Context
{
    protected function resetMock(): void
    {
        MockClientCallback::reset();
    }

    protected function addResponse(string $url, string $method = 'GET', ?PyStringNode $rawHttp = null, int $code = 200): void
    {
        MockClientCallback::addResponse($method, $url, $this->buildMockResponse($code, $rawHttp?->getRaw()));
    }

    protected function assertApiCall(string $url, string $method = 'GET', ?PyStringNode $content = null): void
    {
        try {
            $data = MockClientCallback::getRequest($method, $url);
        } catch (\DomainException $e) {
            $text = $e->getMessage();
            $text .= " Found: \n";
            $requests = MockClientCallback::getRequests();
            foreach (array_keys($requests) as $key) {
                [$method, $url] = explode('/', $key, 2);
                $text .= sprintf('%s %s', $method, $url);
            }

            throw new \DomainException($text);
        }
        if (null !== $content) {
            $expected = json_decode($content->getRaw(), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($expected)) {
                throw new \DomainException(sprintf('Only arrays can be matched. Got %s', gettype($expected)));
            }
            $got = json_decode((string)$data['body'], true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($got)) {
                throw new \DomainException(sprintf('Only arrays can be matched. Got %s', gettype($got)));
            }
            $dc = new ArrayDeepCompare();
            if (!$dc->arrayEquals($got, $expected)) {
                $gotJson = json_encode($got, JSON_PRETTY_PRINT);
                $error = sprintf("Got: \n%s\n%s", $gotJson, $dc->getDifference());

                throw new \DomainException($error);
            }
        }
    }

    protected function assertNoApiCall(string $url, string $method = 'GET'): void
    {
        try {
            MockClientCallback::getRequest($method, $url);
        } catch (\DomainException) {
            return;
        }
        throw new \DomainException('Api has been called');
    }

    protected function buildMockResponse(?int $httpCode = null, ?string $rawHttp = null): MockResponse
    {
        $info = [];
        // Avoid empty string, as it will simulate a timeout im HttpMockClient
        $body = 'xxx';
        if (null !== $httpCode) {
            $info['http_code'] = $httpCode;
        }
        if (null !== $rawHttp) {
            $parts = preg_split("/\r?\n\r?\n/", $rawHttp, 2);
            // No divider -> no headers
            if (\count($parts) < 2) {
                $body = $rawHttp;
            } else {
                $headerRows = preg_split("/\r?\n/", $parts[0]);
                // Looks like "HTTP/1.0 200 OK"
                $statusRow = array_shift($headerRows);
                $info['http_code'] = explode(' ', $statusRow, 3)[1];

                // Split header by colon
                $info['response_headers'] = [];
                foreach ($headerRows as $headerRow) {
                    [$key, $val] = explode(':', $headerRow, 2);
                    $info['response_headers'][trim($key)][] = trim($val);
                }

                // Body is easy
                $body = $parts[1];
            }
        }

        return new MockResponse($body, $info);
    }
}
