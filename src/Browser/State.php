<?php

namespace Elbformat\SymfonyBehatBundle\Browser;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Representation of the browser state with last Request and Response for BrowserContext
 */
class State
{
    protected ?Response $response = null;
    protected ?Request $request = null;
    protected ?Crawler $crawler = null;
    /** @var array<string,string|null> */
    protected array $cookies = [];

    public function reset(): void
    {
        $this->response = null;
        $this->request = null;
        $this->crawler = null;
        $this->cookies = [];
    }

    public function update(Request $request, Response $response): void
    {
        $this->request = $request;
        $this->response = $response;
        $this->crawler = null;
        foreach ($response->headers->getCookies() as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie->getValue();
        }
    }

    public function getResponse(): Response
    {
        if (null === $this->response) {
            throw new \DomainException('No request was made yet');
        }

        return $this->response;
    }

    public function getResponseContent(): string
    {
        return (string)$this->getResponse()->getContent();
    }

    public function getRequest(): Request
    {
        if (null === $this->request) {
            throw new \DomainException('No request was made yet');
        }

        return $this->request;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getCrawler(): Crawler
    {
        if (null === $this->crawler) {
            $this->crawler = new Crawler($this->getResponseContent(), $this->getRequest()->getUri());
        }

        return $this->crawler;
    }
}
