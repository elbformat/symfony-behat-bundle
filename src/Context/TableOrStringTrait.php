<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * @phpstan-import-type NestedMap from NestedMapTrait
 */
trait TableOrStringTrait
{
    use PyStringTrait;
    use TableNodeTrait;

    /**
     * Convert tables/json/yaml to a (nested) array structure with scalars (no objects).
     *
     * @return NestedMap
     */
    protected function getDataFromTableOrString(?TableNode $tableNode = null, ?PyStringNode $jsonOrYaml = null): array
    {
        // Prefer table data
        if (null !== $tableNode) {
            return $this->getTableData($tableNode);
        }

        // Check json first, then yaml
        if (null !== $jsonOrYaml) {
            return $this->getPyStringData($jsonOrYaml);
        }

        // No data at all
        return [];
    }
}
