<?php

declare(strict_types=1);

namespace Context\AbstractDatabaseContext;

use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\Context\AbstractDatabaseContext\AbstractAbstractDatabaseContext;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractDatabaseContext::class)]
class ExecTest extends AbstractAbstractDatabaseContext
{
    public function testExec(): void
    {
        $this->connection->expects($this->once())->method('executeQuery')->with('SQL');
        $refl = new \ReflectionMethod(AbstractDatabaseContext::class, 'exec');
        $refl->invoke($this->context, 'SQL');
    }
}
