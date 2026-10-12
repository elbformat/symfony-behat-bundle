<?php

declare(strict_types=1);

namespace Context;

use Elbformat\SymfonyBehatBundle\Context\DateContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateContext::class)]
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

    public function testWaitSeconds(): void
    {
        $ctx = new DateContext();
        $expectedDate = (new \DateTimeImmutable())->add(new \DateInterval('PT1S'));
        $ctx->iWaitNSeconds(1);
        $realDate = new \DateTimeImmutable();
        $this->assertSame($expectedDate->getTimestamp(), $realDate->getTimestamp());
    }

    public function testWaitMilliseconds(): void
    {
        $ctx = new DateContext();
        $expectedDate = (new \DateTimeImmutable())->add(new \DateInterval('PT1S'));
        $ctx->iWaitNMilliseconds(1000);
        $realDate = new \DateTimeImmutable();
        $this->assertSame($expectedDate->getTimestamp(), $realDate->getTimestamp());
    }
}
