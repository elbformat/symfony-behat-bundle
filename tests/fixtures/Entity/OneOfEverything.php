<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyBackedEnum;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Enum\MyEnum;

class OneOfEverything
{
    protected int $int = 0;
    protected string $string = '';
    protected float $float = 0.1;
    protected bool $bool = true;
    protected ?\DateTime $dt;
    protected ?\DateTimeImmutable $dti = null;
    protected ?\DateTimeInterface $dtif = null;
    /** @var Collection<int,self> */
    protected Collection $collection;
    protected MyBackedEnum $backedEnum;
    protected MyEnum $enum;
    protected self $self;

    protected $untyped;

    public function __construct()
    {
        $this->collection = new ArrayCollection();
        $this->backedEnum = MyBackedEnum::case1;
        $this->enum = MyEnum::case1;
    }

    public function getInt(): int
    {
        return $this->int;
    }

    public function setInt(int $int): void
    {
        $this->int = $int;
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): void
    {
        $this->string = $string;
    }

    public function getFloat(): float
    {
        return $this->float;
    }

    public function setFloat(float $float): void
    {
        $this->float = $float;
    }

    public function isBool(): bool
    {
        return $this->bool;
    }

    public function setBool(bool $bool): void
    {
        $this->bool = $bool;
    }

    public function getDt(): ?\DateTime
    {
        return $this->dt;
    }

    public function setDt(?\DateTime $dt): void
    {
        $this->dt = $dt;
    }

    public function getDti(): ?\DateTimeImmutable
    {
        return $this->dti;
    }

    public function setDti(?\DateTimeImmutable $dti): void
    {
        $this->dti = $dti;
    }

    public function getDtif(): ?\DateTimeInterface
    {
        return $this->dtif;
    }

    public function setDtif(?\DateTimeInterface $dtif): void
    {
        $this->dtif = $dtif;
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function getBackedEnum(): MyBackedEnum
    {
        return $this->backedEnum;
    }

    public function setBackedEnum(MyBackedEnum $backedEnum): void
    {
        $this->backedEnum = $backedEnum;
    }

    public function getEnum(): MyEnum
    {
        return $this->enum;
    }

    public function setEnum(MyEnum $enum): void
    {
        $this->enum = $enum;
    }

    public function getSelf(): self
    {
        return $this->self;
    }

    public function setSelf(self $self): void
    {
        $this->self = $self;
    }

    public function getUntyped()
    {
        return $this->untyped;
    }

    public function setUntyped($untyped): void
    {
        $this->untyped = $untyped;
    }

    public function __toString(): string
    {
        return (string) $this->int;
    }
}
