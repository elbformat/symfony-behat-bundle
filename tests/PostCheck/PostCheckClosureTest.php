<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\PostCheck;

use Elbformat\SymfonyBehatBundle\PostCheck\PostCheckClosure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostCheckClosure::class)]
class PostCheckClosureTest extends TestCase
{
    public function testArrayContains(): void
    {
        $check = new PostCheckClosure(static function (mixed $value): bool {
            return 7 === $value;
        });
        $this->assertTrue($check(7));
        $this->assertFalse($check('Hello World'));
    }
}
