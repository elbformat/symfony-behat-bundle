<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\PostCheck;

use Elbformat\SymfonyBehatBundle\Helper\StringCompare;

class PostCheckStringMatches implements PostCheckInterface
{
    public function __construct(
        protected string $expected,
    ) {
    }

    public function __invoke(mixed $data, object $object): bool
    {
        if (!\is_scalar($data)) {
            return false;
        }
        $sc = new StringCompare();

        return $sc->stringContains((string) $data, $this->expected);
    }
}
