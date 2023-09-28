<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Helper;

/**
 * Do a plain or regex compare, when starts with ~
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class StringCompare
{
    public function stringContains(string $haystack, string $needle): bool
    {
        // Regex match
        if (str_starts_with($needle, '~')) {
            return $this->regexCompare($haystack, substr($needle, 1));
        }

        return str_contains($haystack, $needle);
    }

    public function stringEquals(string $actual, string $expected): bool
    {
        // Regex match
        if (str_starts_with($expected, '~')) {
            $regex = substr($expected, 1);
            // Make it match the whole string only
            if (!str_starts_with($regex, '^')) {
                $regex = '^'.$regex;
            }
            if (!str_ends_with($regex, '$')) {
                $regex .= '$';
            }
            return $this->regexCompare($actual, $regex);
        }

        return $actual === $expected;
    }

    protected function regexCompare(string $string, string $regex): bool
    {
        return (bool)preg_match('/'.$regex.'/', $string);
    }
}
