<?php

declare(strict_types=1);

namespace Context\AbstractDatabaseContext;

use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PostgresDriver;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\Context\AbstractDatabaseContext\AbstractAbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractDatabaseContext::class)]
class ResetTest extends AbstractAbstractDatabaseContext
{
    public function testResetDatabase(): void
    {
        $metadata = new ClassMetadata(OneOfEverything::class);
        $metadata->table = ['name' => 'my_table'];
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(OneOfEverything::class)
            ->willReturn($metadata);
        $this->connection->expects($this->once())->method('executeQuery')->with('DELETE FROM my_table');
        $this->context->resetDb();
    }

    public function testResetSecondDatabase(): void
    {
        $this->connection->expects($this->once())->method('executeQuery')->with('DELETE FROM another_table');
        $this->context->resetDb2();
    }

    public function testResetSequenceMysql(): void
    {
        $metadata = new ClassMetadata(OneOfEverything::class);
        $metadata->table = ['name' => 'my_table'];
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(OneOfEverything::class)
            ->willReturn($metadata);
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER TABLE my_table AUTO_INCREMENT=1');
        $this->context->resetSeq();
    }

    public function testResetSequencePostgres(): void
    {
        $metadata = new ClassMetadata(OneOfEverything::class);
        $metadata->table = ['name' => 'my_table'];
        $metadata->idGenerator = new IdentityGenerator('my_sequence');
        $metadata->sequenceGeneratorDefinition = ['sequenceName' => 'my_sequence'];
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(OneOfEverything::class)
            ->willReturn($metadata);
        $this->connection->method('getDriver')->willReturn(new PostgresDriver());
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER SEQUENCE my_sequence RESTART WITH 1');
        $this->context->resetSeq();
    }
}
