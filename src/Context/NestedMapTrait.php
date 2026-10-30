<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Webmozart\Assert\Assert;

/**
 * @phpstan-type NestedMapValue null|scalar|array<array-key,mixed>
 * @phpstan-type NestedMap array<string,NestedMapValue>
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
            try {
                self::assertNestedMapValue($entry);
            } catch (\InvalidArgumentException) {
                throw new \InvalidArgumentException('data structure contains other than scalar or array values: '.\gettype($entry));
            }
        }
    }

    /**
     * @phpstan-assert NestedMapValue $value
     */
    protected static function assertNestedMapValue(mixed $value): void
    {
        if (\is_scalar($value) || null === $value) {
            return;
        }
        if (!\is_array($value)) {
            throw new \InvalidArgumentException('value contains other than scalar or array values: '.\gettype($value));
        }
    }
}
