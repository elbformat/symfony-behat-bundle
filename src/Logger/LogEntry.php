<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Logger;

class LogEntry
{
    protected string $message;

    /** @var mixed[] */
    protected array $context;

    /** @param mixed[] $context */
    public function __construct(string $message, array $context)
    {
        $this->message = $message;
        $this->context = $context;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return mixed[] */
    public function getContext(): array
    {
        return $this->context;
    }
}
