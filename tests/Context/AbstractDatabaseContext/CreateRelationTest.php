<?php

declare(strict_types=1);

namespace Context\AbstractDatabaseContext;

use Doctrine\Common\Collections\ArrayCollection;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\Context\AbstractDatabaseContext\AbstractAbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractDatabaseContext::class)]
class CreateRelationTest extends AbstractAbstractDatabaseContext
{
    public function testCreateRelation(): void
    {
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $this->repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($obj1, $obj2) {
                return match ($id) {
                    1 => $obj1,
                    2 => $obj2,
                };
            });
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('clear');
        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'collection');
        $this->assertTrue($obj1->getCollection()->contains($obj2));
    }

    public function testCreateRelationId1NotFound(): void
    {
        $this->repo->method('find')->willReturn(null);
        $this->expectExceptionMessage('OneOfEverything with ID 1 not found');

        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'collection');
    }

    public function testCreateRelationId2NotFound(): void
    {
        $this->repo->method('find')->willReturnOnConsecutiveCalls(new OneOfEverything(), null);
        $this->expectExceptionMessage('OneOfEverything with ID 2 not found');

        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'collection');
    }

    public function testCreateRelationWrongField(): void
    {
        $this->repo->method('find')->willReturn(new OneOfEverything());
        $this->expectExceptionMessage('Property "string" is not a collection');

        $this->context->createMyRelation(1, OneOfEverything::class, 2, 'string');
    }

    public function testAssertCollectionContains(): void
    {
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $obj1->setCollection(new ArrayCollection([$obj2]));
        $this->repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($obj1, $obj2) {
                return match ($id) {
                    1 => $obj1,
                    2 => $obj2,
                };
            });
        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionContainsNotFoundId1(): void
    {
        $this->repo->method('find')->willReturn(null);
        $this->expectExceptionMessage('OneOfEverything with ID 1 not found');

        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionContainsNotFoundId2(): void
    {
        $this->repo->method('find')->willReturnOnConsecutiveCalls(new OneOfEverything(), null);
        $this->expectExceptionMessage('OneOfEverything with ID 2 not found');

        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionContainsWrongProperty(): void
    {
        $this->repo->method('find')->willReturnOnConsecutiveCalls(new OneOfEverything(), new OneOfEverything());
        $this->expectExceptionMessage('Property "string" is not a collection.');

        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'string');
    }

    public function testAssertCollectionContainsNot(): void
    {
        $obj1 = new OneOfEverything();
        $this->repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($obj1) {
                return match ($id) {
                    1 => $obj1,
                    2 => new OneOfEverything(),
                };
            });
        $this->expectExceptionMessage('OneOfEverything(1) not in collection.');
        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionContainsNotFound(): void
    {
        $obj1 = new OneOfEverything();
        $this->repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($obj1) {
                return match ($id) {
                    1 => $obj1,
                    2 => new OneOfEverything(),
                };
            });
        $this->expectExceptionMessage('OneOfEverything(1) not in collection.');
        $this->context->assertMyCollectionContains(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionNotContains(): void
    {
        $obj1 = new OneOfEverything();
        $this->repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($obj1) {
                return match ($id) {
                    1 => $obj1,
                    2 => new OneOfEverything(),
                };
            });
        $this->context->assertMyCollectionContainsNot(1, OneOfEverything::class, 2, 'collection');
    }

    public function testAssertCollectionNotContainsContains(): void
    {
        $obj1 = new OneOfEverything();
        $obj2 = new OneOfEverything();
        $obj1->setCollection(new ArrayCollection([$obj2]));
        $this->repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(static function (int $id) use ($obj1, $obj2) {
                return match ($id) {
                    1 => $obj1,
                    2 => $obj2,
                };
            });
        $this->expectException(\DomainException::class);
        $this->context->assertMyCollectionContainsNot(1, OneOfEverything::class, 2, 'collection');
    }
}
