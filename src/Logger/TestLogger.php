<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class TestLogger extends AbstractLogger
{
    protected static array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (LogLevel::DEBUG === $level || LogLevel::INFO === $level) {
            return;
        }
        self::$logs[$level][] = [(string) $message, $context];
    }

    public static function getLogs(string $level): array
    {
        return self::$logs[$level] ?? [];
    }

    public static function getAllLogs(): array
    {
        return self::$logs;
    }
}
