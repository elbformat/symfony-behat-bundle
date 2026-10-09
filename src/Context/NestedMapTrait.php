<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Webmozart\Assert\Assert;

/**
 * @phpstan-type NestedMap array<string,null|scalar|array<array-key,mixed>>
 */
trait NestedMapTrait
{
    /**
     * @phpstan-assert NestedMap $value
     */
    protected static function assertNestedMap(mixed $value): void
    {
        // Make sure we always have string keys and scalar or array values
        Assert::isMap($value);
        foreach ($value as $key => $entry) {
            Assert::string($key);
            if (\is_scalar($entry) || null === $entry) {
                continue;
            }
            if (!\is_array($entry)) {
                throw new \InvalidArgumentException('data structure contains other than scalar or array values: '.\gettype($entry));
            }
        }
    }
}
