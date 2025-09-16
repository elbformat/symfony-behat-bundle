<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use DomainException;
use Elbformat\SymfonyBehatBundle\Application\ApplicationFactory;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Interaction with a symfony console command.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class CommandContext implements Context
{
    private ?string $output = null;
    private ?int $returnCode = null;
    /** @var ?resource */
    private $stream = null;

    public function __construct(
        protected ApplicationFactory $appFactory,
        protected StringCompare $strComp,
    ) {
    }

    #[BeforeScenario]
    public function resetDocumentIdStack(): void
    {
        $this->output = null;
        $this->returnCode = null;
        $this->stream = null;
    }

    #[Given('the next command input is :string')]
    public function theNextCommandInputIs(string $string): void
    {
        if (null === $this->stream) {
            $this->stream = fopen('php://memory', 'r+', false);
        }
        fwrite($this->stream, $string.\PHP_EOL);
    }

    #[When('I run command :command')]
    public function iRunCommand(string $command): void
    {
        $params = explode(' ', $command);
        if (null === $this->stream) {
            array_unshift($params, '-n');
        }
        array_unshift($params, 'console');
        $input = new ArgvInput($params);
        if (null !== $this->stream) {
            rewind($this->stream);
            $input->setStream($this->stream);
            $input->setInteractive(true);
        }
        $output = new BufferedOutput();

        try {
            $application = $this->appFactory->create();
            $this->returnCode = $application->run($input, $output);
            $this->output = $output->fetch();
        } catch (\Throwable $t) {
            $prev = $t->getPrevious();
            while (null !== $prev) {
                echo $prev->getMessage()."\n";
                $prev = $prev->getPrevious();
            }
            throw $t;
        }
    }

    #[Then('the command has a return value of :code')]
    #[Then('the command is successful')]
    public function theCommandSHasAReturnValueOf(int $code = 0): void
    {
        if (($this->getReturnCode()) !== ($code)) {
            $msg = sprintf('Expected the command to return code %d but got %d', $code, $this->getReturnCode());
            $msg .= "\n".$this->getOutput();
            throw new DomainException($msg);
        }
    }

    #[Then('the command outputs :text')]
    public function theCommandOutputs(string $text): void
    {
        $found = $this->getOutput();
        if (!$this->strComp->stringContains($found, $text)) {
            throw new DomainException(sprintf("Text not found in\n%s", $found));
        }
    }

    #[Then('the command does not output :text')]
    public function theCommandDoesNotOutput(string $text): void
    {
        $found = $this->getOutput();
        if ($this->strComp->stringContains($found, $text)) {
            throw new \DomainException('Text found');
        }
    }

    protected function getOutput(): string
    {
        if (null === $this->output) {
            throw new DomainException('No command has run yet.');
        }

        return $this->output;
    }

    protected function getReturnCode(): int
    {
        if (null === $this->returnCode) {
            throw new DomainException('No command has run yet.');
        }

        return $this->returnCode;
    }
}
