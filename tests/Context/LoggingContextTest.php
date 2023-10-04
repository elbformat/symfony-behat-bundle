<?php

namespace Context;

use Behat\Gherkin\Node\TableNode;
use Elbformat\SymfonyBehatBundle\Context\LoggingContext;
use Elbformat\SymfonyBehatBundle\Logger\TestLogger;
use Elbformat\SymfonyBehatBundle\Tests\Context\ExpectNotToPerformAssertionTrait;
use PHPUnit\Framework\TestCase;

class LoggingContextTest extends TestCase
{
    use ExpectNotToPerformAssertionTrait;

    protected ?LoggingContext $loggingContext = null;

    protected function setUp(): void
    {
        $this->loggingContext = new LoggingContext();
        $this->loggingContext->reset();
    }

    public function testTheLogContainsAnEntry(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning');
        $this->expectNotToPerformAssertions();
    }

    public function testTheLogContainsAnEntryContext(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning', ['lorem' => 'ipsum']);
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning', new TableNode([
            [
                'lorem',
                'ipsum',
            ],
        ]), false);
        $this->expectNotToPerformAssertions();
    }

    public function testTheLogContainsAnEntryContextArray(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning', ['lorem' => ['dolor' => 'sit']]);
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning', new TableNode([
            [
                'lorem',
                '{"dolor":"sit"}',
            ],
        ]), false);
        $this->expectNotToPerformAssertions();
    }

    public function testTheLogContainsAnEntryFailNoEntry(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning');
    }

    public function testTheLogContainsAnEntryFailWrongSeverity(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is a warning');
    }

    public function testTheLogContainsAnEntryFailWrongMessage(): void
    {
        $logger = new TestLogger();
        $logger->log('error', 'This is an error');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is a warning');
    }

    public function testTheLogContainsAnEntryFailMissingContext(): void
    {
        $logger = new TestLogger();
        $logger->log('error', 'This is an error');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->expectExceptionMessage('ERROR: This is an error');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is an error', new TableNode([['lorem', 'ipsum']]));
    }

    public function testTheLogContainsAnEntryFailWrongContextValue(): void
    {
        $logger = new TestLogger();
        $logger->log('error', 'This is an error', ['lorem' => 'dolor']);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->expectExceptionMessage('ERROR: This is an error');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is an error', new TableNode([['lorem', 'ipsum']]));
    }

    public function testTheLogContainsAnEntryFailWrongContextArray(): void
    {
        $logger = new TestLogger();
        $logger->log('error', 'This is an error', ['lorem' => ['dolor' => 'sit']]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->expectExceptionMessage('ERROR: This is an error');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is an error', new TableNode([['lorem', '{"ipsum": "sit"}']]));
    }

}
