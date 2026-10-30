<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\PostCheck;

use Elbformat\SymfonyBehatBundle\PostCheck\PostCheckArrayContains;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostCheckArrayContains::class)]
class PostCheckArrayContainsTest extends TestCase
{
    public function testArrayContains(): void
    {
        $obj = new \stdClass();
        $check = new PostCheckArrayContains([1, 2]);
        $this->assertTrue($check([1, 2, 3], $obj));
        $this->assertFalse($check([1, 3], $obj));
        $this->assertFalse($check('Hello World', $obj));
    }
}
