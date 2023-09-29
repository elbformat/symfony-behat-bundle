<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\HttpClient;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MockClientCallback
{
    /** @var array<string,array> */
    protected static array $requests = [];
    /** @var array<string,ResponseInterface> */
    protected static array $responses = [];

    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        $key = $method.'/'.$url;
        if (isset(self::$responses[$key])) {
            self::$requests[$key] = $options;

            return self::$responses[$key];
        }

        return new MockResponse('Missing mock: '.$key, ['http_code' => 404]);
    }

    public static function reset(): void
    {
        self::$requests = [];
        self::$responses = [];
    }

    public static function addResponse(string $method, string $url, ResponseInterface $response): void
    {
        $key = $method.'/'.$url;
        self::$responses[$key] = $response;
    }

    public static function getRequest(string $method, string $url): array
    {
        $key = $method.'/'.$url;
        if (!isset(self::$requests[$key])) {
            throw new \DomainException(sprintf('No response found for %s %s', $method, $url));
        }

        return self::$requests[$key];
    }

    /** @return array<string,array> */
    public static function getRequests(): array
    {
        return self::$requests;
    }
}
