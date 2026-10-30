<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\PostCheck;

use Elbformat\SymfonyBehatBundle\PostCheck\PostCheckStringMatches;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostCheckStringMatches::class)]
class PostCheckStringMatchesTest extends TestCase
{
    public function testArrayContains(): void
    {
        $obj = new \stdClass();
        $check = new PostCheckStringMatches('~Hello');
        $this->assertTrue($check('I see Hello World', $obj));
        $this->assertTrue($check('Hello World', $obj));
        $this->assertTrue($check('Hello', $obj));
        $this->assertFalse($check('World', $obj));
        $this->assertFalse($check([1, 3], $obj));
        $this->assertFalse($check(7, $obj));
    }
}
