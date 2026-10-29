<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\Context\AbstractDatabaseContext;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyBackedEnum;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractDatabaseContext::class)]
class CreateObjectTest extends AbstractAbstractDatabaseContext
{
    public function testCreateObjectTable(): void
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
        $this->repo->expects($this->once())->method('find')->with(66)->willReturn(new OneOfEverything());
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

    public function testCreateObjectTableTypeCast(): void
    {
        $table = new TableNode([
            ['int', '(int) 7'],
            ['string', '(string) ab\nc'],
            ['float', '(float) 1.2'],
            ['dt', 'NULL'],
            ['bool', 'TRUE'],
        ]);
        $this->em->expects($this->once())->method('persist')->with($this->callback(function ($obj) {
            $this->assertInstanceOf(OneOfEverything::class, $obj);
            $this->assertSame(7, $obj->getInt());
            $this->assertSame('ab\nc', $obj->getString());
            $this->assertSame(1.2, $obj->getFloat());
            $this->assertNull($obj->getDt());
            $this->assertTrue($obj->isBool());

            return true;
        }));
        $this->context->createMyObject($table);
    }

    public function testCreateObjectJson(): void
    {
        $json = <<<EOJSON
            {
              "int": 7,
              "string": "abc",
              "float": 1.2,
              "dt": "2021-01-02",
              "dti": "2020-02-03",
              "dtif": "2019-03-04",
              "bool": true,
              "enum": "case2",
              "backedEnum": "case2",
              "self": "OneOfEverything::66",
            }
        EOJSON;
        $pyString = new PyStringNode(explode("\n", $json), 1);
        $this->repo->expects($this->once())->method('find')->with(66)->willReturn(new OneOfEverything());
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
        $this->context->createMyObject(null, $pyString);
    }

    public function testCreateObjectYaml(): void
    {
        $json = <<<EOYAML
            int: 7 
            string: "abc"
            float: 1.2
            dt: "2021-01-02"
            dti: "2020-02-03"
            dtif: "2019-03-04"
            bool: true
            enum: "case2"
            backedEnum: "case2"
            self: "OneOfEverything::66"
        EOYAML;

        $pyString = new PyStringNode(explode("\n", $json), 1);
        $this->repo->expects($this->once())->method('find')->with(66)->willReturn(new OneOfEverything());
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
        $this->context->createMyObject(null, $pyString);
    }

    #[DataProvider('createObjectBoolProvider')]
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

    public static function createObjectBoolProvider(): \Generator
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
}
