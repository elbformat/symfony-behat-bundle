<?php

declare(strict_types=1);

namespace Helper;

use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(StringCompare::class)]
class StringCompareTest extends TestCase
{
    protected StringCompare $comp;

    protected function setUp(): void
    {
        $this->comp = new StringCompare();
    }

    #[DataProvider('stringContainsProvider')]
    public function testStringContains(string $haystack, string $needle): void
    {
        $this->assertTrue($this->comp->stringContains($haystack, $needle));
    }

    public static function stringContainsProvider(): array
    {
        return [
            ['Hello World', 'Hello'],
            ['Hello World', 'World'],
            ['Hello World', 'o W'],
            ['Hello World', '~.*'],
            ['Hello World', '~ello'],
            ['Hello World', '~[a-z]+'],
            ['Hello World', '~^Hello World$'],
        ];
    }

    #[DataProvider('stringContainsNotProvider')]
    public function testStringContainsNot(string $haystack, string $needle): void
    {
        $this->assertFalse($this->comp->stringContains($haystack, $needle));
    }

    public static function stringContainsNotProvider(): array
    {
        return [
            ['Hello', 'World'],
            ['Hello', 'hello'],
            ['Hello', '~[0-9]'],
            ['Hello World', '^World$'],
            ['Hello World', '^Hello$'],
        ];
    }

    #[DataProvider('stringEqualsProvider')]
    public function testStringEquals(string $haystack, string $needle): void
    {
        $this->assertTrue($this->comp->stringEquals($haystack, $needle));
    }

    public static function stringEqualsProvider(): array
    {
        return [
            ['Hello World', 'Hello World'],
            ['Hello World', '~.*'],
            ['Hello World', '~[a-zA-Z\s]+'],
            ['Hello World', '~^Hello World$'],
            ['Hello World', '~^Hello World'],
            ['Hello World', '~Hello World$'],
            ['Hello World', '~Hello World'],
        ];
    }

    #[DataProvider('stringEqualsNotProvider')]
    public function testStringEqualsNot(string $haystack, string $needle): void
    {
        $this->assertFalse($this->comp->stringEquals($haystack, $needle));
    }

    /** @return array<string[]> */
    public static function stringEqualsNotProvider(): array
    {
        return [
            ['Hello World', 'Hello'],
            ['Hello World', 'World'],
            ['Hello', 'World'],
            ['Hello', 'hello'],
            ['Hello', '~ello'],
            ['Hello', '~[0-9]'],
            ['Hello World', '~[a-z]+'],
            ['Hello World', '^World$'],
            ['Hello World', '^Hello$'],
            ['Hello World', '~World$'],
        ];
    }
}
