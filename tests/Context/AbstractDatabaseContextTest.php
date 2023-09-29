<?php

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PostgresDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Id\IdentityGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Context\MyDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyBackedEnum;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyEnum;
use PHPUnit\Framework\TestCase;

class AbstractDatabaseContextTest extends TestCase
{
    protected AbstractDatabaseContext $context;
    protected ?EntityManagerInterface $em = null;
    protected ?Connection $connection = null;
    protected array $expectedExceptionTableEntries = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->em->method('getConnection')->willReturn($this->connection);
        $this->context = new MyDatabaseContext($this->em);
        $this->expectedExceptionTableEntries = [];
    }

    public function testExec(): void
    {
        $this->connection->expects($this->once())->method('executeQuery')->with('SQL');
        $refl = new \ReflectionMethod(AbstractDatabaseContext::class, 'exec');
        $refl->invoke($this->context, 'SQL');
    }

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
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->willThrowException(MappingException::nonExistingClass('xxx'));
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

    public function testCreateObject(): void
    {
        $table = new TableNode([
            ['int', '7'],
            ['string', 'abc'],
            ['float', '1.2'],
            ['dt', '2021-01-02'],
            ['dti', '2020-02-03'],
            ['dtif', '2019-03-04'],
            ['bool', 'true'],
            ['enum', 'case2'],
            ['backedEnum', 'case2'],
            ['self', 'OneOfEverything::66'],
        ]);
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('find')->with(66)->willReturn(new OneOfEverything());
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(OneOfEverything::class)
            ->willReturn($repoMock);
        $this->em->expects($this->once())->method('persist')->with($this->callback(function ($obj) {
            $this->assertInstanceOf(OneOfEverything::class, $obj);
            $this->assertSame(7, $obj->getInt());
            $this->assertSame('abc', $obj->getString());
            $this->assertSame(1.2, $obj->getFloat());
            $this->assertSame('2021-01-02', $obj->getDt()->format('Y-m-d'));
            $this->assertSame('2020-02-03', $obj->getDti()->format('Y-m-d'));
            $this->assertSame('2019-03-04', $obj->getDtif()->format('Y-m-d'));
            $this->assertTrue($obj->isBool());
            $this->assertSame(MyEnum::case2, $obj->getEnum());
            $this->assertSame(MyBackedEnum::case2, $obj->getBackedEnum());
            $this->assertInstanceOf(OneOfEverything::class, $obj->getSelf());

            return true;
        }));
        $this->context->createMyObject($table);
    }

    /** @dataProvider createObjectBoolProvider */
    public function testCreateObjectBool(string $tableVal, bool $expected): void
    {
        $table = new TableNode([
            ['bool', $tableVal],
        ]);
        $this->em->expects($this->once())->method('persist')->with($this->callback(function ($obj) use ($expected) {
            $this->assertInstanceOf(OneOfEverything::class, $obj);
            $this->assertSame($expected, $obj->isBool());

            return true;
        }));
        $this->context->createMyObject($table);
    }

    public function createObjectBoolProvider(): \Generator
    {
        yield ['true', true];
        yield ['1', true];
        yield ['false', false];
        yield ['0', false];
    }

    public function testCreateObjectInvalidEntity(): void
    {
        $table = new TableNode([
            ['self', 'InvalidEntity::1'],
        ]);
        $this->expectExceptionMessage('Invalid entity name: Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\InvalidEntity');

        $this->context->createMyObject($table);
    }

    public function testCreateRelation(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $repoMock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($obj1, $obj2);
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repoMock);
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('clear');
        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'collection');
        $this->assertTrue($obj1->getCollection()->contains($obj2));
    }

    public function testCreateRelationId1NotFound(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('find')->willReturn(null);
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('OneOfEverything with ID 1 not found');

        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'collection');
    }

    public function testCreateRelationId2NotFound(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('find')->willReturnOnConsecutiveCalls(new OneOfEverything(), null);
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('OneOfEverything with ID 2 not found');

        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'collection');
    }

    public function testCreateRelationWrongField(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('find')->willReturn(new OneOfEverything());
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('Property "string" is not a collection');

        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'string');
    }

    public function testAssertObject(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('findOneBy')->with([
            'int' => 7,
            'string' => 'abc',
            'float' => 1.2,
            'dt' => new \DateTime('2021-01-02'),
            'dti' => new \DateTime('2020-02-03'),
            'dtif' => new \DateTime('2019-03-04'),
            'bool' => true,
            'enum' => 'case2',
            'backedEnum' => 'case1',
            'self' => 66,
        ])->willReturn(new OneOfEverything());
        $this->em->expects($this->once())->method('getRepository')->willReturn($repoMock);
        $table = new TableNode([
            ['int', '7'],
            ['string', 'abc'],
            ['float', '1.2'],
            ['dt', '2021-01-02'],
            ['dti', '2020-02-03'],
            ['dtif', '2019-03-04'],
            ['bool', 'true'],
            ['enum', 'case2'],
            ['backedEnum', 'case1'],
            ['self', '66'],
        ]);
        $this->context->assertMyObject($table);
    }

    public function testAssertObjectStringable(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj = new OneOfEverything();
        $obj->setUntyped(new StringCompare());
        $obj->setCollection(new ArrayCollection([new StringCompare()]));
        $repoMock->method('findOneBy')->willReturn(null);
        $repoMock->method('findAll')->willReturn([$obj]);
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('Not found. Found:');
        $this->expectedExceptionTableEntry('Field', 'Expected', 'Found');
        $this->expectedExceptionTableEntry('untyped', '1', '<object>');

        $table = new TableNode([
            ['untyped', '1'],
            ['collection', '2'],
        ]);
        $this->context->assertMyObject($table);
    }

    public function testAssertObjectNotFound(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('findOneBy')->willReturn(null);
        $fallback = new OneOfEverything();
        $fallback->setInt(8);
        $fallback->setString('def');
        $fallback->setFloat(2.3);
        $fallback->setDt(new \DateTime('2022-01-02'));
        $fallback->setBool(false);
        $fallback->setEnum(MyEnum::case1);
        $self = new OneOfEverything();
        $self->setInt(33);
        $fallback->setSelf($self);
        $fallback->setUntyped(7);
        $col2 = new OneOfEverything();
        $col2->setInt(44);
        $fallback->setCollection(new ArrayCollection([$self, $col2]));
        $repoMock->expects($this->once())->method('findAll')->willReturn([$fallback]);
        $this->em->method('getRepository')->willReturn($repoMock);
        $table = new TableNode([
            ['int', '7'],
            ['string', 'NULL'],
            ['float', '1.2'],
            ['dt', '2021-01-02'],
            ['dti', '2020-02-03'],
            ['dtif', '2019-03-04'],
            ['bool', 'true'],
            ['enum', 'case2'],
            ['backedEnum', 'case1'],
            ['self', '66'],
            ['untyped', '4'],
            ['collection', '3,4'],
        ]);
        $this->expectExceptionMessage('Not found. Found:');
        $this->expectedExceptionTableEntry('Field', 'Expected', 'Found');
        $this->expectedExceptionTableEntry('int', '7', '8');
        $this->expectedExceptionTableEntry('string', 'NULL', 'def');
        $this->expectedExceptionTableEntry('float', '1.2', '2.3');
        $this->expectedExceptionTableEntry('dt', '2021-01-02', '2022-01-02T00:00:00+00:00');
        $this->expectedExceptionTableEntry('dti', '2020-02-03', '<NULL>');
        $this->expectedExceptionTableEntry('dtif', '2019-03-04', '<NULL>');
        $this->expectedExceptionTableEntry('bool', 'true', 'false');
        $this->expectedExceptionTableEntry('enum', 'case2', 'case1');
        $this->expectedExceptionTableEntry('backedEnum', 'case1', 'case1');
        $this->expectedExceptionTableEntry('self', '66', '33');
        $this->expectedExceptionTableEntry('untyped', '4', '7');
        $this->expectedExceptionTableEntry('collection', '3,4', '33 / 44');
        $this->context->assertMyObject($table);
    }

    public function testAssertNoObject(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('findOneBy')->with(['int' => 7])->willReturn(null);
        $this->em->expects($this->once())->method('getRepository')->willReturn($repoMock);
        $table = new TableNode([
            ['int', '7'],
        ]);
        $this->context->assertNotMyObject($table);
    }

    public function testAssertNoObjectFound(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('findOneBy')->with(['int' => 7])->willReturn(new OneOfEverything());
        $this->em->expects($this->once())->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('Found');

        $table = new TableNode([
            ['int', '7'],
        ]);
        $this->context->assertNotMyObject($table);
    }

    public function testAssertCollectionContains(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $obj1->setCollection(new ArrayCollection([$obj2]));
        $repoMock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($obj1, $obj2);
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repoMock);
        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionContainsNotFoundId1(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('find')->willReturn(null);
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('OneOfEverything with ID 1 not found');

        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionContainsNotFoundId2(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('find')->willReturnOnConsecutiveCalls(new OneOfEverything(), null);
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('OneOfEverything with ID 2 not found');

        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }
    public function testAssertCollectionContainsWrongProperty(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('find')->willReturnOnConsecutiveCalls(new OneOfEverything(), new OneOfEverything());
        $this->em->method('getRepository')->willReturn($repoMock);
        $this->expectExceptionMessage('Property "string" is not a collection.');

        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'string');
    }

    public function testAssertCollectionContainsNot(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $repoMock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($obj1, $obj2);
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repoMock);
        $this->expectExceptionMessage('OneOfEverything(1) not in collection.');
        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }
    public function testAssertCollectionContainsNotFound(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj1 = new OneOfEverything();
        $repoMock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($obj1, new OneOfEverything());
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repoMock);
        $this->expectExceptionMessage('OneOfEverything(1) not in collection.');
        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionNotContains(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $repoMock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($obj1, $obj2);
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repoMock);
        $this->context->assertMyCollectionContainsNot(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionNotContainsContains(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $obj1->setCollection(new ArrayCollection([$obj2]));
        $repoMock->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($obj1, $obj2);
        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repoMock);
        $this->expectException(\DomainException::class);
        $this->context->assertMyCollectionContainsNot(1, OneOfEverything::class, 2, 'collection');
    }

    private function expectedExceptionTableEntry(string $field, string $expected, string $found): void
    {
        $field = preg_quote($field, '/');
        $expected = preg_quote($expected, '/');
        $found = preg_quote($found, '/');
        $this->expectedExceptionTableEntries[] = "(\|\s+{$field}\s+\|\s+{$expected}\s+\|\s+{$found}\s+\|)";
        $this->expectExceptionMessageMatches('/'.implode('.*', $this->expectedExceptionTableEntries).'/s');
    }
}
