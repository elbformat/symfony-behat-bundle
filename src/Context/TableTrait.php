<?php

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Gherkin\Node\TableNode;

trait TableTrait
{
    /**
     * @return array<string,string>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    protected function getTableData(?TableNode $table): array
    {
        if (null === $table) {
            return [];
        }

        return $table->getRowsHash();
    }
}
