<?php

namespace Elbformat\SymfonyBehatBundle\Context;

use DOMAttr;
use DOMNamedNodeMap;
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
                $fullTag = $doc ? $doc->saveXML($fallback) : '<unknown>';
                // We have a maximum of 2 nested tags here (e.g. <a><p>...</p></a>)
                if (substr_count($fullTag, '<') <= 4) {
                    $names[] = $fullTag;
                    continue;
                }
                // Rebuild otherwise
                $attribs = '';
                /** @var DOMNamedNodeMap $fallbackAttributes */
                $fallbackAttributes = $fallback->attributes;
                /** @var DOMAttr $attribute */
                foreach ($fallbackAttributes as $attribute) {
                    $attribs .= sprintf(' %s="%s"', $attribute->nodeName, $attribute->value);
                }
                $foundTag = sprintf('<%s%s>...%s...</%1$s>', $fallback->nodeName, $attribs, $fallback->textContent);
                $names[] = $foundTag;
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
