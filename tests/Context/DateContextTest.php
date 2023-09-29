<?php

declare(strict_types=1);

namespace Context;

use Elbformat\SymfonyBehatBundle\Context\DateContext;
use PHPUnit\Framework\TestCase;

class DateContextTest extends TestCase
{
    public function testMockDate(): void
    {
        $ctx = new DateContext();
        $dateBefore = date('Y-m-d');
        $ctx->theCurrentDateIs('2022-02-02');
        $this->assertSame('2022-02-02', date('Y-m-d'));
        $ctx->reset();
        $this->assertSame($dateBefore, date('Y-m-d'));
    }
}
