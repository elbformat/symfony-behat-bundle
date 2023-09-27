<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Mailer;

class Attachment
{
    private string $filename;
    private string $body;
    private ?string $textContent = null;

    public function __construct(string $filename, string $body)
    {
        $this->filename = $filename;
        $this->body = $body;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    public function setTextContent(string $textContent): void
    {
        $this->textContent = $textContent;
    }

    public function getFileExtension(): string
    {
        return strtolower(pathinfo($this->filename, \PATHINFO_EXTENSION));
    }
}
