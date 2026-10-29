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
        $check = new PostCheckStringMatches('~Hello');
        $this->assertTrue($check('I see Hello World'));
        $this->assertTrue($check('Hello World'));
        $this->assertTrue($check('Hello'));
        $this->assertFalse($check('World'));
        $this->assertFalse($check([1, 3]));
        $this->assertFalse($check(7));
        $this->assertFalse($check(7));
    }
}
