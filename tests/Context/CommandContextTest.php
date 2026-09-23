<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Tests\Context;

use Elbformat\SymfonyBehatBundle\Application\ApplicationFactory;
use Elbformat\SymfonyBehatBundle\Context\CommandContext;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(CommandContext::class)]
class CommandContextTest extends TestCase
{
    protected ?CommandContext $commandContext = null;
    protected ?ApplicationFactory $appFactory = null;
    protected ?Application $application = null;

    protected function setUp(): void
    {
        $this->application = $this->createMock(Application::class);
        $this->appFactory = $this->createMock(ApplicationFactory::class);
        $this->appFactory->method('create')->willReturn($this->application);
        $this->commandContext = new CommandContext($this->appFactory, new StringCompare());
    }

    public function testReset(): void
    {
        $this->commandContext->resetDocumentIdStack();
        $this->expectExceptionMessage('No command has run yet.');
        $this->commandContext->theCommandSHasAReturnValueOf(0);
    }

    public function testReset2(): void
    {
        $this->commandContext->resetDocumentIdStack();
        $this->expectExceptionMessage('No command has run yet.');
        $this->commandContext->theCommandOutputs('test');
    }

    public function testIRunCommand(): void
    {
        $this->application->expects($this->once())->method('run')->with($this->callback(static function ($input) {
            return 'elbformat:behat:test' === $input->getFirstArgument();
        }));
        $this->commandContext->iRunCommand('elbformat:behat:test');
    }

    public function testIRunCommandNotFound(): void
    {
        $this->application->method('run')->willThrowException(new \DomainException('Unknown command', 0, new \DomainException('because')));
        $this->expectExceptionMessageMatches('/^Unknown command/');
        $this->commandContext->iRunCommand('elbformat:behat:test');
    }

    public function testTheCommandSHasAReturnValueOf(): void
    {
        $this->application->expects($this->once())->method('run')->willReturn(1);
        $this->commandContext->iRunCommand('elbformat:behat:test');
        $this->commandContext->theCommandSHasAReturnValueOf(1);
    }

    public function testTheCommandSHasAReturnValueOfUnequal(): void
    {
        $this->application->method('run')->willReturn(1);
        $this->expectExceptionMessage('Expected the command to return code 0 but got 1');
        $this->commandContext->iRunCommand('elbformat:behat:test');
        $this->commandContext->theCommandSHasAReturnValueOf(0);
    }

    public function testTheCommandOutputs(): void
    {
        $this->application->expects($this->once())->method('run')->willReturnCallback(static function (InputInterface $input, OutputInterface $output) {
            $output->write('Lorem Ipsum');

            return 0;
        });
        $this->commandContext->iRunCommand('elbformat:behat:test');
        $this->commandContext->theCommandOutputs('Lorem Ipsum');
    }

    public function testTheCommandOutputsFails(): void
    {
        $this->application->method('run')->willReturnCallback(static function (InputInterface $input, OutputInterface $output) {
            $output->write('Lorem Ipsum');

            return 0;
        });
        $this->expectExceptionMessage("Text not found in\nLorem Ipsum");
        $this->commandContext->iRunCommand('elbformat:behat:test');
        $this->commandContext->theCommandOutputs('Hello World');
    }

    public function testtheCommandDoesNotOutput(): void
    {
        $this->application->expects($this->once())->method('run')->willReturnCallback(static function (InputInterface $input, OutputInterface $output) {
            $output->write('Lorem Ipsum');

            return 0;
        });
        $this->commandContext->iRunCommand('elbformat:behat:test');
        $this->commandContext->theCommandDoesNotOutput('Hello World');
    }

    public function testTheCommandDoesNotOutputFails(): void
    {
        $this->application->method('run')->willReturnCallback(static function (InputInterface $input, OutputInterface $output) {
            $output->write('Lorem Ipsum');

            return 0;
        });
        $this->expectExceptionMessage('Text found');
        $this->commandContext->iRunCommand('elbformat:behat:test');
        $this->commandContext->theCommandDoesNotOutput('Lorem Ipsum');
    }
}
