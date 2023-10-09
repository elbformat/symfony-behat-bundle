<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class TestLogger extends AbstractLogger
{
    /** @var array<string,LogEntry[]> */
    protected static array $logs = [];

    public static function reset(): void
    {
        self::$logs = [];
    }

    /**
     * @param mixed $level
     * @param \Stringable|string $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (!is_string($level)) {
            $level = LogLevel::ERROR;
        }
        if (LogLevel::DEBUG === $level || LogLevel::INFO === $level) {
            return;
        }
        self::$logs[$level][] = new LogEntry((string)$message, $context);
    }

    /** @return LogEntry[] */
    public static function getLogs(string $level): array
    {
        return self::$logs[$level] ?? [];
    }

    /** @return array<string,LogEntry[]> */
    public static function getAllLogs(): array
    {
        return self::$logs;
    }
}
