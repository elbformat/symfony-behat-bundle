<?php

namespace Elbformat\SymfonyBehatBundle\Context;

use Elbformat\SymfonyBehatBundle\Browser\State;
use Symfony\Component\DomCrawler\Crawler;

trait DomTrait
{
    protected State $state;

    protected function getCrawler(): Crawler
    {
        return $this->state->getCrawler();
    }

    protected function createNotFoundException(string $what, ?Crawler $fallbacks = null): \DomainException
    {
        $errMsg = sprintf('%s not found.', $what);
        if (null !== $fallbacks) {
            $names = [];
            foreach ($fallbacks as $fallback) {
                $doc = $fallback->ownerDocument;
                $names[] = $doc ? $doc->saveXML($fallback) : '<unknown>';
            }
            switch (\count($names)) {
                case 0:
                    break;
                case 1:
                    $errMsg .= sprintf(' Did you mean "%s"?', $names[0]);
                    break;
                default:
                    $errMsg .= sprintf(" Did you mean one of the following?\n%s", implode("\n", $names));
                    break;
            }
        }

        return new \DomainException($errMsg);
    }
}
