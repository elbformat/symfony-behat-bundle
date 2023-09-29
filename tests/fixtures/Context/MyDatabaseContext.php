<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\fixtures\Context;

use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;

class MyDatabaseContext extends AbstractDatabaseContext
{
    public function resetDb(): void
    {
        $this->resetDatabase();
    }

    public function resetDb2(): void
    {
        $this->resetDatabase('another_table');
    }

    public function resetSeq(): void
    {
        $this->resetSequence();
    }

    public function createMyObject(TableNode $table): void
    {
        $this->createObject($table);
    }

    public function createMyRelation(int $id1, string $class2, int $id2, string $relationName): void
    {
        $this->createRelation($id1, $class2, $id2, $relationName);
    }

    public function assertMyObject(TableNode $table): void
    {
        $this->assertObject($table);
    }

    public function assertNotMyObject(TableNode $table): void
    {
        $this->assertNoObject($table);
    }

    public function assertMyCollectionContains(int $id1, string $class2, int $id2, string $relationName): void
    {
        $this->assertCollectionContains($id1, $class2, $id2, $relationName);
    }

    public function assertMyCollectionContainsNot(int $id1, string $class2, int $id2, string $relationName): void
    {
        $this->assertCollectionDoesNotContain($id1, $class2, $id2, $relationName);
    }

    protected function getClassName(): string
    {
        return OneOfEverything::class;
    }
}
