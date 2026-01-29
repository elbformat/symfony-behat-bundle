<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Helper;

use DomainException;

/**
 * Helper to find the difference in two complex array structures.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class ArrayDeepCompare
{
    protected ?string $difference = null;

    /**
     * @param mixed $a
     * @param mixed $b
     */
    public function arrayEquals($a, $b): bool
    {
        $this->difference = null;
        return !$this->hasDiff($a, $b);
    }

    public function arrayContains(array $container, array $containment): bool
    {
        $this->difference = null;
        return !$this->hasDiff($containment, $container, '', false);
    }

    public function getDifference(): string
    {
        if (null === $this->difference) {
            throw new DomainException('No difference');
        }

        return $this->difference;
    }

    protected function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, \count($arr) - 1);
    }

    /**
     * Check if two values/arrays are equal.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @throws DomainException when differs
     */
    protected function hasDiff($a, $b, string $path = '', bool $reverseCheck = true): bool
    {
        if (!\is_array($a) && !\is_array($b)) {
            // Scalar values -> compare
            if ($a !== $b) {
                $this->difference = sprintf(
                    '%s: (%s) %s != (%s) %s',
                    $path,
                    \gettype($a),
                    $a ?? '',
                    \gettype($b),
                    $b ?? ''
                );

                return true;
            }

            return false;
        }

        if (!\is_array($a) || !\is_array($b)) {
            // One array -> mismatch
            $this->difference = sprintf('%s: <%s> != <%s>', $path, \gettype($a), \gettype($b));

            return true;
        }

        // Only two arrays left
        $isAssoc = $this->isAssoc($a);
        /** @var mixed $v */
        foreach ($a as $k => $v) {
            $subpath = ($path ? $path.'.' : '').$k;
            if ($isAssoc) {
                // Key does not exist in b
                if (!\array_key_exists($k, $b)) {
                    $this->difference = sprintf('%s: Missing', $subpath);

                    return true;
                }

                // Recurse check of values
                if ($this->hasDiff($v, $b[$k], $subpath, $reverseCheck)) {
                    return true;
                }

                // This key is done
                unset($b[$k]);
            } else {
                /** @var mixed $bVal */
                foreach ($b as $index => $bVal) {
                    if (!$this->hasDiff($v, $bVal, $subpath, $reverseCheck)) {
                        // This key is done
                        unset($b[$index]);
                        continue 2;
                    }
                }
                $this->difference = sprintf('%s: %s Missing', $subpath, is_array($v) ? json_encode($v) : (string) $v);

                return true;
            }
        }

        // Still entries left in b? -> unequal
        if ($reverseCheck && \count($b)) {
            $item = is_array(array_reverse($b)[0]) ? json_encode(array_reverse($b)[0]) : (string) array_reverse($b)[0];
            $subpath = ($path ? $path.'.' : '').$item;
            $this->difference = sprintf('%s: Extra', $subpath);

            return true;
        }

        return false;
    }

}
