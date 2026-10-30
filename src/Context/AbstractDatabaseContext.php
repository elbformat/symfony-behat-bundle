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
use Elbformat\SymfonyBehatBundle\Helper\PropertyTypeExtractor;
use Elbformat\SymfonyBehatBundle\PostCheck\PostCheckArrayContains;
use Elbformat\SymfonyBehatBundle\PostCheck\PostCheckInterface;
use Elbformat\SymfonyBehatBundle\PostCheck\PostCheckStringMatches;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

/**
 * @template T of object
 *
 * @phpstan-import-type NestedMap from NestedMapTrait
 * @phpstan-import-type NestedMapValue from NestedMapTrait
 */
abstract class AbstractDatabaseContext implements Context
{
    use TableOrStringTrait;

    protected const string ENTITY_REFERENCE_PATTERN = '/^(.+)::(.+)$/';

    public function __construct(
        protected EntityManagerInterface $em,
        protected PropertyTypeExtractor $propertyTypeExtractor,
    ) {
    }

    protected function resetDatabase(?string $classOrTableName = null): void
    {
        if (null === $classOrTableName) {
            $classOrTableName = $this->getClassName();
        }
        if (class_exists($classOrTableName)) {
            try {
                $metadata = $this->em->getClassMetadata($classOrTableName);
                $classOrTableName = $metadata->getTableName();
            } catch (MappingException) {
            }
        }

        $this->exec('DELETE FROM '.$classOrTableName);
    }

    /**
     * @param ?class-string<T> $className
     */
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
            $val = $this->convertCreationValue($key, $val);
            // This is to make phpstan typesystem happy in sf < 7.4
            $paObj = $obj;
            $pa->setValue($paObj, $key, $val);
        }
        $this->em->persist($obj);
        $this->em->flush();
        $this->em->clear();

        return $obj;
    }

    /**
     * @param class-string $class2
     */
    protected function createRelation(int|string $id1, string $class2, int|string $id2, string $relationName, ?string $reverseRelationName = null): void
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
        $postChecks = [];
        foreach ($tableData as $key => $val) {
            $convertedValue = $this->convertAssertionValue($key, $val);
            if ($convertedValue instanceof PostCheckInterface) {
                $postChecks[$key] = $convertedValue;
            } else {
                $data[$key] = $convertedValue;
            }
        }

        if (0 === \count($postChecks)) {
            // There is only one candidate
            $obj = $repo->findOneBy($data);
        } else {
            // Test all candidates
            $candidates = $repo->findBy($data);
            $obj = $this->postCheckFilter($candidates, $postChecks);
        }
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
    protected function assertCollectionContains(int|string $containerId, string $containingClass, int|string $containingId, string $relation): void
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
        if (!$collection->contains($containingEntry)) {
            throw new \DomainException(\sprintf('%s(%d) not in collection.', $containingClass, $containerId));
        }
    }

    /** @param class-string $containingClass */
    protected function assertCollectionDoesNotContain(int|string $containerId, string $containingClass, int|string $containingId, string $relation): void
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

    /**
     * @param NestedMapValue $value
     */
    protected function convertCreationValue(string $key, mixed $value): mixed
    {
        self::assertNestedMapValue($value);

        // Reference to another entity with <Entity>::<ID>
        $isString = \is_string($value);
        if ($isString && preg_match(self::ENTITY_REFERENCE_PATTERN, $value, $match)) {
            return $this->resolveEntityReference($match[1], $match[2]);
        }

        $type = $this->propertyTypeExtractor->getTypeOfProperty($this->getClassName(), $key);

        if ($isString) {
            // Enum
            if (null !== $type && enum_exists($type)) {
                return \constant($type.'::'.$value);
            }
            if (\DateTimeInterface::class === $type || \DateTimeImmutable::class === $type) {
                return new \DateTimeImmutable($value);
            }
            if (\DateTime::class === $type) {
                return new \DateTime($value);
            }
            // Can be changed to TypeIdentifier::ARRAY->value when sf < 7.4 support is dropped
            if ('array' === $type) {
                // @deprecated: use PropertyAccess instead
                return json_decode($value, true, flags: \JSON_THROW_ON_ERROR);
            }
        }

        $isScalar = \is_scalar($value);
        if ($isScalar) {
            if ('int' === $type) {
                return (int) $value;
            }
            if ('float' === $type) {
                return (float) $value;
            }
            if ('bool' === $type) {
                // @deprecated use 'TRUE' and it will be converted from table syntax automatically
                if ('true' === $value) {
                    return true;
                }
                // @deprecated use 'FALSE' and it will be converted from table syntax automatically
                if ('false' === $value) {
                    return false;
                }

                return (bool) $value;
            }
        }

        return $value;
    }

    /** @param NestedMapValue $value */
    protected function convertAssertionValue(string $key, mixed $value): mixed
    {
        self::assertNestedMapValue($value);

        $isString = \is_string($value);
        $type = $this->propertyTypeExtractor->getTypeOfProperty($this->getClassName(), $key, PropertyTypeExtractor::INTERNAL);
        if ($isString) {
            if (preg_match(self::ENTITY_REFERENCE_PATTERN, $value, $match)) {
                return $this->resolveEntityReference($match[1], $match[2]);
            }
            if (str_starts_with($value, '~')) {
                return new PostCheckStringMatches($value);
            }
            if (\DateTimeInterface::class === $type || \DateTimeImmutable::class === $type) {
                return new \DateTimeImmutable($value);
            }
            if (\DateTime::class === $type) {
                return new \DateTime($value);
            }
        }

        if ('array' === $type) {
            // @deprecated use PropertyAccess Syntax instead
            if ($isString) {
                $value = json_decode($value, false, 512, \JSON_THROW_ON_ERROR);
            }
            Assert::isArray($value);

            return new PostCheckArrayContains($value);
        }

        if (\is_scalar($value) && 'bool' === $type) {
            if (\is_bool($value)) {
                return $value;
            }

            // @deprecated use TRUE and FALSE instead
            return 'false' !== $value && '0' !== $value;
        }

        return $value;
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
                $type = $this->propertyTypeExtractor->getTypeOfProperty($this->getClassName(), $key, PropertyTypeExtractor::READ);
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

    /**
     * @param array<array-key, mixed> $constructorArgs
     *
     * @return T
     */
    protected function newObject(array $constructorArgs = []): object
    {
        $className = $this->getClassName();

        return new $className(...$constructorArgs);
    }

    /** @return EntityRepository<T> */
    protected function getRepo(): ObjectRepository
    {
        return $this->em->getRepository($this->getClassName());
    }

    /**
     * @template E of object
     *
     * @param class-string<E> $entityName
     *
     * @return ?E
     */
    protected function findEntity(string $entityName, int|string $id): ?object
    {
        return $this->em->getRepository($entityName)->find($id);
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
            $constructorParams[$argName] = $this->convertCreationValue($argName, $data[$argName] ?? $this->getDefaultValue($argName));
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

    protected function resolveEntityReference(string $class, int|string $id): ?object
    {
        // Use FQN
        if (class_exists($class)) {
            return $this->em->getRepository($class)->find($id);
        }

        // Use same-domain class name
        $className = preg_replace('/[^\\\]+$/', $class, $this->getClassName());
        if (!\is_string($className) || !class_exists($className)) {
            throw new \DomainException('Invalid entity name: '.$className);
        }

        return $this->em->getRepository($className)->find($id);
    }

    /**
     * @param list<T>                          $candidates
     * @param array<string,PostCheckInterface> $postChecks
     *
     * @return T|null
     */
    protected function postCheckFilter(array $candidates, array $postChecks): ?object
    {
        $pa = new PropertyAccessor();
        foreach ($candidates as $candidate) {
            foreach ($postChecks as $key => $postCheck) {
                $foundValue = $pa->getValue($candidate, $key);
                // Check failed -> next candidate
                if (!$postCheck($foundValue, $candidate)) {
                    continue 2;
                }
            }

            // All checks passed
            return $candidate;
        }

        return null;
    }

    /** @return class-string<T> */
    abstract protected function getClassName(): string;
}
