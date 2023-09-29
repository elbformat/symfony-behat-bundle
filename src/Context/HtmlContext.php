<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use DOMElement;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;

/**
 * The scope of this context is checking the HTML result for occurrence of tags or text.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class HtmlContext implements Context
{
    use DomTrait;
    use TableTrait;

    public function __construct(
        protected State $state,
        protected StringCompare $strComp,
    ) {
    }

    /********/
    /* WHEN */
    /********/
    #[When('I remove attribute :attr from :xpath')]
    public function iRemoveAttributeFrom(string $attr, string $xpath): void
    {
        $crawler = $this->getCrawler();
        $element = $crawler->filterXpath($xpath);
        if (!$element->count()) {
            throw $this->createNotFoundException('DOM Element', $crawler->filterXPath($xpath));
        }
        foreach ($element as $el) {
            if ($el instanceof \DOMElement) {
                $el->removeAttribute($attr);
            }
        }
    }

    /********/
    /* THEN */
    /********/
    #[Then('I see :text')]
    public function iSee(string $text): void
    {
        if (!$this->strComp->stringContains($this->state->getResponseContent(), $text)) {
            throw new \DomainException('Text not found');
        }
    }

    #[Then('/^(?:|I )don\'t see "(?P<text>(?:[^"]|\\")*)"$/')]
    public function iDontSee(string $text): void
    {
        if ($this->strComp->stringContains($this->state->getResponseContent(), $text)) {
            throw new \DomainException('Text found');
        }
    }

    #[Then('I see :text1 before :text2')]
    public function iSeeBefore(string $text1, string $text2): void
    {
        $content = $this->state->getResponseContent();
        $pos1 = strpos($content, $text1);
        if (false === $pos1) {
            throw new \DomainException(sprintf('"%s" not found', $text1));
        }
        $pos2 = strpos($content, $text2);
        if (false === $pos2) {
            throw new \DomainException(sprintf('"%s" not found', $text2));
        }
        if ($pos1 > $pos2) {
            throw new \DomainException(sprintf('"%s" found at Position %d, "%s" at %d', $text1, $pos1, $text2, $pos2));
        }
    }

    #[Then('I see a(n) :tag tag')]
    #[Then('I see a(n) :tag tag :content')]
    public function iSeeATag(string $tag, ?TableNode $table = null, ?string $content = null, ?PyStringNode $multiLineContent = null): void
    {
        $ex = $this->mustContainTag($tag, $this->getTableData($table), $multiLineContent ? $multiLineContent->getRaw() : $content);
        if ($ex) {
            throw $ex;
        }
    }

    #[Then('I don\'t see a(n) :tag tag')]
    #[Then('I don\'t see a(n) :tag tag :content')]
    public function iDontSeeATag(string $tag, ?TableNode $table = null, ?string $content = null, ?PyStringNode $multiLineContent = null): void
    {
        $ex = $this->mustContainTag($tag, $this->getTableData($table), $multiLineContent ? $multiLineContent->getRaw() : $content);
        if (null === $ex) {
            throw new \DomainException('Tag found');
        }
    }

    /*************/
    /* Internals */
    /*************/

    /** @param array<string,string> $attr */
    protected function mustContainTag(string $tagName, array $attr = [], ?string $content = null): ?\DomainException
    {
        $crawler = $this->getCrawler();
        $xPath = '//'.$tagName;
        foreach ($attr as $attrName => $attrVal) {
            $xPath .= sprintf('[@%s="%s"]', $attrName, $attrVal);
        }
        $elements = $crawler->filterXPath($xPath);

        if (!$elements->count()) {
            return $this->createNotFoundException('Tag', $crawler->filterXPath('//'.$tagName));
        }

        // Check content
        if (null !== $content) {
            $content = trim($content);
            /** @var DOMElement $elem */
            foreach ($elements as $elem) {
                if ($this->strComp->stringEquals(trim($elem->textContent), $content)) {
                    return null;
                }
            }

            return $this->createNotFoundException('Tag with content', $crawler->filterXPath('//'.$tagName));
        }

        return null;
    }
}
