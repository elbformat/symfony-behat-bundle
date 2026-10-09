<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Gherkin\Node\TableNode;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Webmozart\Assert\Assert;

/**
 * @phpstan-import-type NestedMap from NestedMapTrait
 */
trait TableNodeTrait
{
    use NestedMapTrait;

    /**
     * @return NestedMap
     */
    protected function getTableData(TableNode $tableNode): array
    {
        $data = $tableNode->getRowsHash();
        Assert::allString($data);
        Assert::allValidArrayKey($data);

        // Make deep structure from flat data with property access
        $array = [];
        $pa = PropertyAccess::createPropertyAccessor();
        foreach ($data as $key => $value) {
            // Special characters like NULL or \n
            $value = $this->replaceTableValues($value);
            $elements = (new PropertyPath($key))->getElements();
            $path = '['.implode('][', $elements).']';
            $pa->setValue($array, $path, $value);
        }
        self::assertNestedMap($array);

        return $array;
    }

    /**
     * Add features that tables don't support natively. Can be overriden/decorated in using classes.
     *
     * @return string|bool|int|float|null
     */
    protected function replaceTableValues(string $value): mixed
    {
        // Explicit typecasts
        if (preg_match('/^\(int\) (\d+)$/', $value, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/^\(float\) (\d+\.\d+)$/', $value, $matches)) {
            return (float) $matches[1];
        }
        // This can be used to bypass other replacements
        // e.g. (string) NULL or (string) a\nb
        if (preg_match('/^\(string\) (.*)$/', $value, $matches)) {
            return $matches[1];
        }

        // Replace line breaks
        $value = str_replace('\n', "\n", $value);

        // Emulate missing scalar types
        return match ($value) {
            'NULL' => null,
            'TRUE' => true,
            'FALSE' => false,
            default => $value,
        };
    }
}
