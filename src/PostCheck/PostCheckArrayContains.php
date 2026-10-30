<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\PostCheck;

use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;

class PostCheckArrayContains implements PostCheckInterface
{
    /**
     * @param array<array-key,mixed> $expected
     */
    public function __construct(
        protected array $expected,
    ) {
    }

    public function __invoke(mixed $data): bool
    {
        if (!\is_array($data)) {
            return false;
        }
        $dc = new ArrayDeepCompare();

        return $dc->arrayContains($data, $this->expected);
    }
}
