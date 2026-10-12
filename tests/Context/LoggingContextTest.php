<?php

declare(strict_types=1);

namespace Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Hook\Scope\AfterTestScope;
use Behat\Testwork\Tester\Result\TestResult;
use Elbformat\SymfonyBehatBundle\Context\LoggingContext;
use Elbformat\SymfonyBehatBundle\Logger\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoggingContext::class)]
class LoggingContextTest extends TestCase
{
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

    public function testTheLogContainsAnEntryRegex(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $this->loggingContext->theLogContainsAnEntry('warn', '~This is a.*');
        $this->expectNotToPerformAssertions();
    }

    public function testTheLogContainsAnEntryContext(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning', ['lorem' => 'ipsum']);
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning', table: new TableNode([
            [
                'lorem',
                'ipsum',
            ],
        ]), dumpLogs: false);
        $this->expectNotToPerformAssertions();
    }

    public function testTheLogContainsAnEntryContextRegex(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning', ['lorem' => 'ipsum']);
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning', table: new TableNode([
            [
                'lorem',
                '~ips.*',
            ],
        ]), dumpLogs: false);
        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('provideTableArray')]
    public function testTheLogContainsAnEntryContextArray(string $key, string $val): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning', ['lorem' => ['dolor' => 'sit']]);
        $this->loggingContext->theLogContainsAnEntry('warn', 'This is a warning', table: new TableNode([[$key, $val]]), dumpLogs: false);
        $this->expectNotToPerformAssertions();
    }

    public static function provideTableArray(): iterable
    {
        yield ['lorem.dolor', 'sit'];
        yield ['[lorem][dolor]', 'sit'];
        // @deprecated
        yield ['lorem', '{"dolor":"sit"}'];
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
        $this->loggingContext->theLogContainsAnEntry('error', 'This is an error', table: new TableNode([['lorem', 'ipsum']]));
    }

    public function testTheLogContainsAnEntryFailWrongContextValue(): void
    {
        $logger = new TestLogger();
        $logger->log('error', 'This is an error', ['lorem' => 'dolor']);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->expectExceptionMessage('ERROR: This is an error');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is an error', table: new TableNode([['lorem', 'ipsum']]));
    }

    public function testTheLogContainsAnEntryFailWrongContextArray(): void
    {
        $logger = new TestLogger();
        $logger->log('error', 'This is an error', ['lorem' => ['dolor' => 'sit']]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entry not found.');
        $this->expectExceptionMessage('ERROR: This is an error');
        $this->loggingContext->theLogContainsAnEntry('error', 'This is an error', table: new TableNode([['lorem', '{"ipsum": "sit"}']]));
    }

    // theLogDoesNotContainLevelEntries()

    public function testTheLogDoesNotContainLevelEntries(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $this->loggingContext->theLogDoesNotContainLevelEntries('error');
        $this->expectNotToPerformAssertions();
    }

    public function testTheLogDoesNotContainLevelEntriesFail(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Log entries found');
        $this->expectExceptionMessage('This is a warning');
        $this->loggingContext->theLogDoesNotContainLevelEntries('warn');
    }

    // dumpLog
    public function testDumpLogPassed(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $result = $this->createMock(TestResult::class);
        $result->method('isPassed')->willReturn(true);
        $event = $this->createMock(AfterTestScope::class);
        $event->method('getTestResult')->willReturn($result);
        ob_start();
        $this->loggingContext->dumpLog($event);
        $text = ob_get_clean();
        $this->assertEmpty($text);
    }

    public function testDumpLogNotPassed(): void
    {
        $logger = new TestLogger();
        $logger->log('warn', 'This is a warning');
        $result = $this->createMock(TestResult::class);
        $result->method('isPassed')->willReturn(false);
        $event = $this->createMock(AfterTestScope::class);
        $event->method('getTestResult')->willReturn($result);
        ob_start();
        $this->loggingContext->dumpLog($event);
        $text = ob_get_clean();
        $this->assertStringContainsString('This is a warning', $text);
    }
}
