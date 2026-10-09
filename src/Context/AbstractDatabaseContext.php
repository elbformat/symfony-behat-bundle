<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PgSqlDriver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @template T of object
 *
 * @phpstan-import-type NestedMap from NestedMapTrait
 */
abstract class AbstractDatabaseContext implements Context
{
    use TableOrStringTrait;

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    protected function resetDatabase(?string $classOrTableName = null): void
    {
        if (null === $classOrTableName) {
            $classOrTableName = $this->getClassName();
        }
        try {
            $metadata = $this->em->getClassMetadata($classOrTableName);
            $classOrTableName = $metadata->getTableName();
        } catch (MappingException) {
        }

        $this->exec('DELETE FROM '.$classOrTableName);
    }

    protected function resetSequence(?string $className = null): void
    {
        if (null === $className) {
            $className = $this->getClassName();
        }
        $metadata = $this->em->getClassMetadata($className);
        $tableName = $metadata->getTableName();
        if ($this->em->getConnection()->getDriver() instanceof PgSqlDriver) {
            if (null !== $metadata->sequenceGeneratorDefinition) {
                $seqName = $metadata->sequenceGeneratorDefinition['sequenceName'];
                $this->exec("ALTER SEQUENCE $seqName RESTART WITH 1");
            }
        } else {
            // @todo check if auto_increment exists
            $this->exec('ALTER TABLE '.$tableName.' AUTO_INCREMENT=1');
        }
    }

    /**
     * @return T
     */
    protected function createObject(?TableNode $tableNode = null, ?PyStringNode $pyStringNode = null): object
    {
        $data = $this->getDataFromTableOrString($tableNode, $pyStringNode);
        $data = array_merge($this->getDefaults(), $data);

        return $this->createObjectFromData($data);
    }

    /**
     * @param NestedMap $data
     *
     * @return T
     */
    protected function createObjectFromData(array $data): object
    {
        $constructorArgs = $this->getConstructorArgsFromData($data);
        $obj = $this->newObject($constructorArgs);
        $pa = new PropertyAccessor();
        foreach ($data as $key => $val) {
            // Skip, when entry was already consumed in constructor
            if (\array_key_exists($key, $constructorArgs)) {
                continue;
            }
            /** @psalm-suppress MixedAssignment */
            $val = $this->mapTableValue($key, $val);
            $pa->setValue($obj, $key, $val);
        }
        /* @psalm-suppress PossiblyInvalidArgument */
        $this->em->persist($obj);
        $this->em->flush();
        $this->em->clear();

        return $obj;
    }

    /** @param class-string $class2 */
    protected function createRelation(int $id1, string $class2, int $id2, string $relationName, ?string $reverseRelationName = null): void
    {
        $entity1 = $this->getRepo()->find($id1);
        if (null === $entity1) {
            throw new \DomainException(\sprintf('%s with ID %d not found', $this->getClassName(), $id1));
        }
        $entity2 = $this->findEntity($class2, $id2);
        if (null === $entity2) {
            throw new \DomainException(\sprintf('%s with ID %d not found', $class2, $id2));
        }
        $pa = new PropertyAccessor();
        // We need to do this manually as the PA does not support adder/remover by now.
        $collection = $pa->getValue($entity1, $relationName);
        if (!$collection instanceof Collection) {
            throw new \DomainException(\sprintf('Property "%s" is not a collection', $relationName));
        }
        $collection->add($entity2);
        if (null !== $reverseRelationName) {
            $collection2 = $pa->getValue($entity2, $reverseRelationName);
            if (!$collection2 instanceof Collection) {
                throw new \DomainException(\sprintf('Property "%s" is not a collection', $reverseRelationName));
            }
            $collection2->add($entity1);
        }
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @return T
     */
    protected function assertObject(?TableNode $table = null, ?PyStringNode $pyStringNode = null, bool $printAlternatives = true): object
    {
        $repo = $this->getRepo();
        $tableData = $this->getDataFromTableOrString($table, $pyStringNode);

        // Convert types
        $data = [];
        foreach ($tableData as $key => $val) {
            $data[$key] = $this->convertAssertionValue($val, $this->getTypeOfProperty($key));
        }

        // Found
        $obj = $repo->findOneBy($data);
        if (null !== $obj) {
            return $obj;
        }

        $this->em->clear();

        // Print available entities
        $exceptionMessage = 'Not found.';
        if ($printAlternatives) {
            $exceptionMessage .= " Found:\n".$this->printAlternatives($tableData);
        }
        throw new \DomainException($exceptionMessage);
    }

    protected function assertNoObject(?TableNode $table = null, ?PyStringNode $pyStringNode = null): void
    {
        try {
            $this->assertObject($table, $pyStringNode, false);
        } catch (\DomainException) {
            return;
        }
        throw new \DomainException('Found');
    }

    /**
     * @param class-string $containingClass
     */
    protected function assertCollectionContains(int $containerId, string $containingClass, int $containingId, string $relation): void
    {
        $mainEntry = $this->getRepo()->find($containerId);
        if (null === $mainEntry) {
            throw new \DomainException(\sprintf('%s with ID %d not found', $this->getClassName(), $containerId));
        }
        $containingEntry = $this->findEntity($containingClass, $containingId);
        if (null === $containingEntry) {
            throw new \DomainException(\sprintf('%s with ID %d not found', $containingClass, $containingId));
        }
        $pa = new PropertyAccessor();
        $collection = $pa->getValue($mainEntry, $relation);
        if (!$collection instanceof Collection) {
            throw new \DomainException(\sprintf('Property "%s" is not a collection.', $relation));
        }
        /* @psalm-suppress RedundantConditionGivenDocblockType */
        if (!$collection->contains($containingEntry)) {
            throw new \DomainException(\sprintf('%s(%d) not in collection.', $containingClass, $containerId));
        }
    }

    /** @param class-string $containingClass */
    protected function assertCollectionDoesNotContain(int $containerId, string $containingClass, int $containingId, string $relation): void
    {
        try {
            $this->assertCollectionContains($containerId, $containingClass, $containingId, $relation);
        } catch (\DomainException) {
            return;
        }
        throw new \DomainException('Found');
    }

    protected function exec(string $query): void
    {
        $this->em->getConnection()->executeQuery($query);
    }

    protected function mapTableValue(string $key, mixed $value): mixed
    {
        $type = $this->getTypeOfProperty($key);

        /* @psalm-suppress ArgumentTypeCoercion */
        switch (true) {
            case null !== $type && enum_exists($type) && \is_string($value):
                return \constant($type.'::'.$value);
                // Reference to another entity with <Entity>::<ID>
            case \is_string($value) && preg_match('/^(.+)::(.+)$/', $value, $match):
                $className = preg_replace('/[^\\\]+$/', $match[1], $this->getClassName());
                if (!class_exists($className)) {
                    throw new \DomainException('Invalid entity name: '.$className);
                }

                return $this->em->getRepository($className)->find($match[2]);
            case 'DateTimeInterface' === $type:
            case 'DateTimeImmutable' === $type:
                return \is_string($value) ? new \DateTimeImmutable($value) : $value;
            case 'DateTime' === $type:
                return \is_string($value) ? new \DateTime($value) : $value;
            case 'int' === $type:
                return \is_scalar($value) ? (int) $value : $value;
            case 'float' === $type:
                return \is_scalar($value) ? (float) $value : $value;
            case 'bool' === $type:
                if ('true' === $value) {
                    return true;
                }
                if ('false' === $value) {
                    return false;
                }

                return (bool) $value;
            case 'array' === $type:
                if (\is_string($value)) {
                    // @deprecated: use PropertyAccess instead
                    return json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
                }

                return $type;
            default:
                return $value;
        }
    }

    /** @return string|bool|\DateTimeInterface|null */
    protected function convertAssertionValue(mixed $value, ?string $type): mixed
    {
        return match ($type) {
            // @deprecated use PropertyAccess Syntax instead
            'array' => \is_string($value) ? json_decode($value, false, 512, \JSON_THROW_ON_ERROR) : $value,
            // @deprecated use TRUE and FALSE instead
            'bool' => \is_bool($value) ? $value : ('false' !== $value && '0' !== $value),
            'DateTimeInterface', 'DateTime' => \is_string($value) ? new \DateTime($value) : $value,
            'DateTimeImmutable' => \is_string($value) ? new \DateTimeImmutable($value) : $value,
            default => $value,
        };
    }

    /** @param NestedMap $data */
    protected function printAlternatives(array $data): string
    {
        $pa = new PropertyAccessor();
        $return = \sprintf("| %-20s | %-20s | %-20s |\n", 'Field', 'Expected', 'Found');
        foreach ($this->getRepo()->findAll() as $item) {
            $return .= \sprintf("| %-20s | %-20s | %-20s |\n", str_repeat('-', 20), str_repeat('-', 20), str_repeat('-', 20));
            foreach ($data as $key => $val) {
                $realVal = $pa->getValue($item, $key);
                $type = $this->getTypeOfProperty($key);
                switch (true) {
                    case $realVal instanceof \BackedEnum:
                        $realVal = $realVal->value;
                        break;
                    case $realVal instanceof \UnitEnum:
                        $realVal = $realVal->name;
                        break;
                    case 'bool' === $type:
                        $realVal = $realVal ? 'true' : 'false';
                        break;
                    case 'array' === $type:
                        $realVal = json_encode($realVal);
                        break;
                    case $realVal instanceof \DateTimeInterface:
                        $realVal = $realVal->format('c');
                        break;
                    case $realVal instanceof Collection:
                        $collectionEntryStrings = [];
                        foreach ($realVal->toArray() as $collEntry) {
                            switch (true) {
                                case \is_scalar($collEntry):
                                case \is_object($collEntry) && method_exists($collEntry, '__toString'):
                                    $collectionEntryStrings[] = (string) $collEntry;
                                    break;
                                default:
                                    $collectionEntryStrings[] = '<'.\gettype($collEntry).'>';
                                    break;
                            }
                        }
                        $realVal = implode(' / ', $collectionEntryStrings);

                        break;
                    case \is_object($realVal) && method_exists($realVal, '__toString'):
                        $realVal = (string) $realVal;
                }
                if (null === $realVal) {
                    $realVal = '<NULL>';
                }
                if (!\is_scalar($realVal)) {
                    $realVal = '<'.\gettype($realVal).'>';
                }
                $return .= \sprintf("| %-20s | %20s | %20s |\n", $key, \is_scalar($val) ? $val : json_encode($val), (string) $realVal);
            }
        }

        return $return;
    }

    /** @return T */
    protected function newObject(array $constructorArgs = []): object
    {
        $className = $this->getClassName();

        /* @psalm-suppress MixedMethodCall */
        return new $className(...$constructorArgs);
    }

    /** @return EntityRepository<T> */
    protected function getRepo(): ObjectRepository
    {
        return $this->em->getRepository($this->getClassName());
    }

    /**
     * @param class-string $entityName
     *
     * @psalm-suppress InvalidReturnType
     *
     * @return ?T
     */
    protected function findEntity(string $entityName, int $id): ?object
    {
        /* @psalm-suppress InvalidReturnStatement */
        return $this->em->getRepository($entityName)->find($id);
    }

    protected function getTypeOfProperty(string $propertyName): ?string
    {
        $refl = new \ReflectionClass($this->getClassName());
        $reflProp = $refl->getProperty($propertyName);
        if (!$reflProp->hasType()) {
            return null;
        }
        $type = $reflProp->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : null;
    }

    /**
     * @param NestedMap $data
     *
     * @return array<array-key,mixed>
     */
    protected function getConstructorArgsFromData(array $data): array
    {
        $refl = new \ReflectionClass($this->getClassName());
        $constructor = $refl->getConstructor();
        if (null === $constructor) {
            return [];
        }
        $constructorArgs = $constructor->getParameters();
        $constructorParams = [];
        foreach ($constructorArgs as $arg) {
            $argName = $arg->getName();
            /* @psalm-suppress MixedAssignment */
            $constructorParams[$argName] = $this->mapTableValue($argName, $data[$argName] ?? $this->getDefaultValue($argName));
        }

        return $constructorParams;
    }

    protected function getDefaultValue(string $var): string
    {
        return '';
    }

    /** @return NestedMap */
    protected function getDefaults(): array
    {
        return [];
    }

    /** @return class-string<T> */
    abstract protected function getClassName(): string;
}
