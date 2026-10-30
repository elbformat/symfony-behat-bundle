<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\Context\AbstractDatabaseContext;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Elbformat\SymfonyBehatBundle\Context\AbstractDatabaseContext;
use Elbformat\SymfonyBehatBundle\Helper\PropertyTypeExtractor;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Context\MyDatabaseContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractAbstractDatabaseContext extends TestCase
{
    protected AbstractDatabaseContext $context;
    protected ?EntityManagerInterface $em = null;
    protected ?PropertyTypeExtractor $pte = null;
    protected ?Connection $connection = null;
    protected EntityRepository|MockObject $repo;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repo = $this->createMock(EntityRepository::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->em->method('getConnection')->willReturn($this->connection);
        $this->em->method('getRepository')->willReturn($this->repo);
        $this->pte = new PropertyTypeExtractor();
        $this->context = new MyDatabaseContext($this->em, $this->pte);
    }
}
