<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use DOMElement;
use Elbformat\SymfonyBehatBundle\Browser\State;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Check existence of forms and fill and submit them. Also allows to simulate
 * javascript parts by modifying the form.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class FormContext implements Context
{
    use RequestTrait;
    use TableTrait;
    use DomTrait;

    protected ?Form $lastForm = null;
    protected ?Crawler $lastFormCrawler = null;

    public function __construct(
        protected KernelInterface $kernel,
        protected State $state,
        protected string $projectDir,
        protected StringCompare $strComp,
    ) {

    }

    #[When('I use form :name')]
    #[Then('the page contains a form named :name')]
    public function thePageContainsAFormNamed(string $name): void
    {
        $crawler = $this->getCrawler();
        $form = $crawler->filterXpath(sprintf('//form[@name="%s"]', $name));
        if (!$form->count()) {
            throw $this->createNotFoundException('Form', $crawler->filterXPath('//form'));
        }
        $this->setLastForm($form);
    }

    #[When('I fill :value into :name')]
    #[When('I select :name radio button with value :value')]
    public function iFillInto(string $value, string $name): void
    {
        $this->getFormField($name)->setValue($value);
    }

    #[When('I clear field :name')]
    public function iClearField(string $name): void
    {
        $this->getFormField($name)->setValue(null);
    }

    #[When('I check :name checkbox')]
    #[When('I check :name checkbox with value :value')]
    public function iCheckCheckbox(string $name, ?string $value = null): void
    {
        $this->getChoiceField($name, $value)->tick();
    }

    #[When('I uncheck :name checkbox')]
    #[When('I uncheck :name checkbox with value :value')]
    public function iUncheckCheckbox(string $name, ?string $value = null): void
    {
        $this->getChoiceField($name, $value)->untick();
    }

    #[When('I select :value from :name')]
    public function iSelectFrom(string $value, string $name): void
    {
        $select = $this->getLastForm()->get($name);
        if (!$select instanceof ChoiceFormField) {
            throw new \DomainException(sprintf('%s is not a choice form field', $name));
        }
        if (str_contains($value, ',')) {
            $value = explode(',', $value);
        }
        $select->select($value);
    }

    #[When('I select :fixture upload at :name')]
    public function iSelectUploadAt(string $fixture, string $name): void
    {
        $field = $this->getLastForm()->get($name);
        if (!$field instanceof FileFormField) {
            throw new \DomainException(sprintf('%s is not a file form field', $name));
        }
        if (!file_exists($this->projectDir.'/'.$fixture)) {
            throw new \DomainException(sprintf('Fixture file not found at %s', $this->projectDir.'/'.$fixture));
        }
        $field->upload($this->projectDir.'/'.$fixture);
    }

    #[When('I add an input field :name')]
    public function iAddAnInputField(string $name, ?TableNode $table = null): void
    {
        $form = $this->getLastForm();
        $newInput = $form->getNode()->ownerDocument?->createElement('input');
        if (! $newInput instanceof DOMElement) {
            throw new \DomainException('Error creating element');
        }
        $newInput->setAttribute('name', $name);
        if (null !== $table) {
            foreach ($this->getTableData($table) as $key => $val) {
                $newInput->setAttribute($key, $val);
            }
        }
        $form->getNode()->appendChild($newInput);
        $this->mutateForm();
    }

    #[When('I remove an input field :name')]
    public function iRemoveAnInputField(string $name): void
    {
        $inputNode = $this->getLastFormCrawler()->filterXPath("//input[@name='".$name."']")->getNode(0);
        if (!$inputNode instanceof DOMElement) {
            throw new \DomainException('Field not found');
        }
        $inputNode->remove();
        $this->mutateForm();
    }

    #[When('I remove a select field :name')]
    public function iRemoveASelectField(string $name): void
    {
        $inputNode = $this->getLastFormCrawler()->filterXPath("//select[@name='".$name."']")->getNode(0);
        if (!$inputNode instanceof DOMElement) {
            throw new \DomainException('Field not found');
        }
        $inputNode->remove();
        $this->mutateForm();
    }

    #[When('I submit the form')]
    #[When('I submit the form with button :buttonName')]
    public function iSubmitTheForm(?string $buttonName = null): void
    {
        $form = $this->getLastForm();
        $values = $form->getPhpValues();
        if (null !== $buttonName) {
            $buttonTag = $this->getCrawler()->filterXpath(sprintf('//button[@name="%s"]', $buttonName));
            $buttonValue = $buttonTag->attr('value');

            $qs = http_build_query([$buttonName => $buttonValue ?? ''], '', '&');
            if (!empty($qs)) {
                parse_str($qs, $expandedValue);
                $values = array_merge_recursive($values, $expandedValue);
            }
        }

        $this->doRequest($this->buildRequest($form->getUri(), $form->getMethod(), [], null, $values, $form->getPhpFiles()));
    }

    #[Then('the form contains an input field')]
    public function theFormContainsAnInputField(TableNode $attribs): void
    {
        $inputs = $this->getLastFormCrawler()->filterXPath('//input');

        /** @var DOMElement $input */
        foreach ($inputs as $input) {
            foreach ($this->getTableData($attribs) as $attrName => $attrVal) {
                if (!$this->strComp->stringEquals($input->getAttribute($attrName), $attrVal)) {
                    continue 2;
                }
            }

            return;
        }

        throw $this->createNotFoundException('input', $inputs);
    }

    #[Then('the form contains a select')]
    public function theFormContainsASelect(TableNode $attribs): void
    {
        $selects = $this->getLastFormCrawler()->filterXPath('//select');

        /** @var DOMElement $select */
        foreach ($selects as $select) {
            foreach ($this->getTableData($attribs) as $attrName => $attrVal) {
                if (!$this->strComp->stringEquals($select->getAttribute($attrName), $attrVal)) {
                    continue 2;
                }
            }

            return;
        }

        throw $this->createNotFoundException('input', $selects);
    }

    #[Then('select :select contains option')]
    #[Then('select :select contains option :label')]
    public function selectContainsOption(string $select, string $label = null, ?TableNode $tableNode = null): void
    {
        $crawler = $this->getLastFormCrawler();
        if (null !== $label) {
            $options = $crawler->filterXpath(sprintf('//select[@name="%s"]/option[text()="%s"]', $select, $label));
        } else {
            $options = $crawler->filterXpath(sprintf('//select[@name="%s"]/option', $select));
        }
        /** @var DOMElement $option */
        foreach ($options as $option) {
            foreach ($this->getTableData($tableNode) as $attrName => $attrVal) {
                // Attributes didn't match -> try the next one
                if (!$this->strComp->stringEquals($option->getAttribute($attrName), $attrVal)) {
                    continue 2;
                }
            }

            // All attributes match
            return;
        }
        throw $this->createNotFoundException('option', $options);
    }

    #[Then('select :name does not contain option')]
    #[Then('select :name does not contain option :value')]
    public function selectDoesNotContainOption(string $name, string $value): void
    {
        try {
            $this->selectContainsOption($name, $value);
        } catch (\DomainException $t) {
            return;
        }
        throw new \DomainException('Option found');
    }

    /************/
    /* INTERNAL */
    /************/

    protected function getFormField(string $name): FormField
    {
        $formField = $this->getLastForm()->get($name);
        if (is_array($formField)) {
            throw new \DomainException(sprintf('%s is not a single form field.', $name));
        }
        if (!$formField instanceof FormField) {
            throw new \DomainException(sprintf('%s is not a form field.', $name));
        }

        return $formField;
    }

    protected function getChoiceField(string $name, ?string $value = null): ChoiceFormField
    {
        $formField = $this->getLastForm()->get($name);
        // Found a single field
        if ($formField instanceof ChoiceFormField) {
            return $formField;
        }
        // Not even a collection
        if (!is_array($formField)) {
            throw new \DomainException(sprintf('%s is not a choice form field', $name));
        }
        foreach ($formField as $formFiel) {
            if (!$formFiel instanceof ChoiceFormField) {
                continue;
            }
            /** @psalm-suppress InternalMethod */
            if ($value === $formFiel->availableOptionValues()[0]) {
                return $formFiel;
            }
        }

        throw new \DomainException('No form field found with this value');
    }

    /* Apply changes to the form */
    protected function mutateForm(): void
    {
        $form = $this->getLastFormCrawler();
        $this->lastForm = $form->form();
    }

    protected function setLastForm(Crawler $form): void
    {
        $this->lastForm = $form->form();
        $this->lastFormCrawler = $form->first();
    }

    protected function getLastForm(): Form
    {
        if (null === $this->lastForm) {
            throw new \DomainException('No form was queried yet');
        }

        return $this->lastForm;
    }

    protected function getLastFormCrawler(): Crawler
    {
        if (null === $this->lastFormCrawler) {
            throw new \DomainException('No form was queried yet');
        }

        return $this->lastFormCrawler;
    }

}
