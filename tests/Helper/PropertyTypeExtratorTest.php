<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\Helper;

use Elbformat\SymfonyBehatBundle\Helper\PropertyTypeExtractor;
use Elbformat\SymfonyBehatBundle\Tests\fixtures\Entity\OneOfEverything;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PropertyTypeExtractor::class)]
class PropertyTypeExtratorTest extends TestCase
{
    public function testExtract(): void
    {
        $pte = new PropertyTypeExtractor();
        $type = $pte->getTypeOfProperty(OneOfEverything::class, 'int', PropertyTypeExtractor::READ);
        self::assertSame('int', $type);
        $type = $pte->getTypeOfProperty(OneOfEverything::class, 'dt', PropertyTypeExtractor::READ);
        self::assertSame('DateTime', $type);
    }

    public function testExtractConfusing(): void
    {
        $class = new class {
            private int $property;

            private $unknown;

            public function getProperty(): float
            {
                return (float) $this->property;
            }

            public function setProperty(string $property): void
            {
                $this->property = (int) $property;
            }
        };
        $pte = new PropertyTypeExtractor();
        $type = $pte->getTypeOfProperty($class::class, 'property', PropertyTypeExtractor::READ);
        self::assertSame('float', $type);
        $type = $pte->getTypeOfProperty($class::class, 'property', PropertyTypeExtractor::WRITE);
        self::assertSame('string', $type);
        $type = $pte->getTypeOfProperty($class::class, 'property', PropertyTypeExtractor::INTERNAL);
        self::assertSame('int', $type);
        $type = $pte->getTypeOfProperty($class::class, 'unknown', PropertyTypeExtractor::INTERNAL);
        self::assertNull($type);
    }
}
