<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-import-type NestedMap from NestedMapTrait
 */
trait PyStringTrait
{
    use NestedMapTrait;

    /**
     * Convert json or yaml to a (nested) array structure with scalars (no objects).
     *
     * @return NestedMap
     */
    protected function getPyStringData(PyStringNode $jsonOrYaml): array
    {
        // Try json first and fall back to yaml
        try {
            $data = json_decode($jsonOrYaml->getRaw(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $cleanYaml = $this->removePyStringIndentation($jsonOrYaml->getRaw());
            $data = Yaml::parse($cleanYaml);
        }
        self::assertNestedMap($data);

        return $data;
    }

    /**
     * This allows us to use yaml in a PyString even if the whole string is indented.
     */
    protected function removePyStringIndentation(string $input): string
    {
        $lines = explode("\n", $input);

        // Determine the number of leading spaces in the first line
        $firstLine = $lines[0];
        $leadingSpaces = \strlen($firstLine) - \strlen(ltrim($firstLine));

        // Remove the same number of leading spaces from each line
        $outputLines = array_map(static fn ($line) => substr($line, $leadingSpaces), $lines);

        // Join the modified lines back into a single string
        return implode("\n", $outputLines);
    }
}
