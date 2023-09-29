<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Logger;

class LogEntry
{
    protected string $message;

    /** @var array  */
    protected array $context;

    /** @param array $context */
    public function __construct(string $message, array $context)
    {
        $this->message = $message;
        $this->context = $context;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return array */
    public function getContext(): array
    {
        return $this->context;
    }
}
