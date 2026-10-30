<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\Context\AbstractDatabaseContext;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\ArrayCollection;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use SlopeIt\ClockMock\ClockMock;

#[CoversClass(AbstractDatabaseContext::class)]
class AssertObjectTest extends AbstractAbstractDatabaseContext
{
    protected array $expectedExceptionTableEntries = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->expectedExceptionTableEntries = [];
    }

    public function testAssertObject(): void
    {
        ClockMock::freeze(new \DateTime());
        $this->repo->expects($this->once())->method('findOneBy')->with([
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

    public function testAssertObjectJson(): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->with([
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
              "backedEnum": "case1",
              "self": 66,
            }
        EOJSON;
        $pyString = new PyStringNode(explode("\n", $json), 1);

        $this->context->assertMyObject(null, $pyString);
    }

    public function testAssertObjectStringable(): void
    {
        $obj = new OneOfEverything();
        $obj->setUntyped(new StringCompare());
        $obj->setCollection(new ArrayCollection([new StringCompare()]));
        $this->repo->method('findOneBy')->willReturn(null);
        $this->repo->method('findAll')->willReturn([$obj]);
        $this->expectExceptionMessage('Not found. Found:');
        $this->expectedExceptionTableEntry('Field', 'Expected', 'Found');
        $this->expectedExceptionTableEntry('untyped', '1', '<object>');

        $table = new TableNode([
            ['untyped', '1'],
            ['collection', '2'],
        ]);
        $this->context->assertMyObject($table);
    }

    public function testAssertObjectEntityRef(): void
    {
        $obj = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $obj->setSelf($obj2);
        $this->repo->expects($this->atLeastOnce())->method('findOneBy')->with([
            'self' => $obj2,
        ])->willReturn($obj);
        $this->repo->expects($this->atLeastOnce())->method('find')->with(66)->willReturn($obj2);
        $table = new TableNode([
            ['self', OneOfEverything::class.'::66'],
        ]);
        $this->context->assertMyObject($table);
    }

    public function testAssertObjectNotFound(): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->willReturn(null);
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
        $this->repo->expects($this->once())->method('findAll')->willReturn([$fallback]);
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
        $this->expectedExceptionTableEntry('string', 'null', 'def');
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
        $this->repo->expects($this->once())->method('findOneBy')->with(['int' => 7])->willReturn(null);
        $table = new TableNode([
            ['int', '7'],
        ]);
        $this->context->assertNotMyObject($table);
    }

    public function testAssertNoObjectFound(): void
    {
        $this->repo->expects($this->once())->method('findOneBy')->with(['int' => 7])->willReturn(new OneOfEverything());
        $this->expectExceptionMessage('Found');

        $table = new TableNode([
            ['int', '7'],
        ]);
        $this->context->assertNotMyObject($table);
    }

    public function testPostCheckArrayContains(): void
    {
        $obj = new OneOfEverything();
        $obj->setArray(['Hello', 'row2', 'row1', 'World']);
        $this->repo->expects($this->once())->method('findBy')->with([
            'int' => 7,
        ])->willReturn([$obj]);
        $table = new TableNode([
            ['int', '7'],
            ['array.0', 'row1'],
            ['array.1', 'row2'],
        ]);
        $this->context->assertMyObject($table);
    }

    public function testPostCheckRegexMatches(): void
    {
        $obj = new OneOfEverything();
        $obj->setString('Hello World');
        $this->repo->expects($this->once())->method('findBy')->with([
            'int' => 7,
        ])->willReturn([$obj]);
        $table = new TableNode([
            ['int', '7'],
            ['string', '~World'],
        ]);
        $this->context->assertMyObject($table);
    }

    public function testPostCheckFails(): void
    {
        $obj = new OneOfEverything();
        $obj->setArray(['Hello', 'World']);
        $this->repo->expects($this->once())->method('findBy')->with([
            'int' => 7,
        ])->willReturn([$obj]);
        $this->repo->expects($this->once())->method('findAll')->willReturn([$obj]);
        $table = new TableNode([
            ['int', '7'],
            ['array.0', 'row3'],
        ]);
        $this->expectExceptionMessage('Not found. Found:');
        $this->expectedExceptionTableEntry('Field', 'Expected', 'Found');
        $this->expectedExceptionTableEntry('array', '["row3"]', '["Hello","World"]');
        $this->context->assertMyObject($table);
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
