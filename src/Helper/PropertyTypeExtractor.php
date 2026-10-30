<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Helper;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * This is a thin wrapper around symfony/property-info with two purposes:
 * 1. make it compatible for sf <7.3 and > 7.3 (BC changes in symfony/property-info)
 * 2. Different types, depending on the use case (read, write, internal)
 */
class PropertyTypeExtractor
{
    public const int READ = 1;
    public const int WRITE = 2;
    public const int INTERNAL = 3;

    /** @var array<int,PropertyTypeExtractorInterface> */
    protected array $extractors = [];

    /**
     * @param class-string $class
     * @param int<1,3>     $mode
     */
    public function getTypeOfProperty(string $class, string $property, int $mode = self::WRITE): ?string
    {
        if (null === ($extractor = $this->extractors[$mode] ?? null)) {
            $extractor = $this->extractors[$mode] = $this->buildExtractor($mode);
        }

        // sf7.3+: TypeInfo
        if (method_exists($extractor, 'getType')) {
            $type = $extractor->getType($class, $property);
            if (null === $type) {
                return null;
            }
            if (method_exists($type, 'getWrappedType')) {
                $type = $type->getWrappedType();
            }

            return (string) $type;
        }

        if (method_exists($extractor, 'getTypes')) {
            $types = $extractor->getTypes($class, $property) ?? [];
            $type = $types[0] ?? null;
            if (null === $type) {
                return null;
            }

            return $type->getClassName() ?? $type->getBuiltinType();
        }
        throw new \RuntimeException('Neither getType() nor getTypes() exists.');
    }

    protected function buildReflectionExtractor(int $mode): PropertyTypeExtractorInterface
    {
        return match ($mode) {
            self::READ => new ReflectionExtractor(mutatorPrefixes: [], arrayMutatorPrefixes: []),
            self::WRITE => new ReflectionExtractor(),
            self::INTERNAL => new ReflectionExtractor(mutatorPrefixes: [], accessorPrefixes: [], arrayMutatorPrefixes: []),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    protected function buildExtractor(int $mode): PropertyTypeExtractorInterface
    {
        $reflectionExtractor = $this->buildReflectionExtractor($mode);

        return new PropertyInfoCacheExtractor(
            new PropertyInfoExtractor(
                listExtractors: [],
                // maybe the DoctrineExtractor (loose coupled!) could also make sense here?
                // In case of DateTimeInterface it could give us the missing hint if it's immutable or not.
                typeExtractors: [$reflectionExtractor],
            ),
            new ArrayAdapter(),
        );
    }
}
