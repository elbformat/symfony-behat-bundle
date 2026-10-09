<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Behat\Testwork\Hook\Scope\AfterTestScope;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use Elbformat\SymfonyBehatBundle\Helper\StringCompare;
use Elbformat\SymfonyBehatBundle\Logger\LogEntry;
use Elbformat\SymfonyBehatBundle\Logger\TestLogger;

/**
 * Test interactions with the logger service.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class LoggingContext implements Context
{
    use TableOrStringTrait;

    protected const string DEFAULT_LOG = 'main';

    #[BeforeScenario]
    public function reset(): void
    {
        TestLogger::reset();
    }

    /**
     * As we use a testHandler, logs are not available to the output.
     * So we will provide them here, if a scenario fails.
     */
    #[AfterScenario]
    public function dumpLog(AfterTestScope $event): void
    {
        if ($event->getTestResult()->isPassed()) {
            return;
        }
        echo '  '.$this->getLogsOutput();
    }

    #[Then('the log contains a(n) :level entry :text')]
    #[Then('the :log log contains a(n) :level entry :text')]
    public function theLogContainsAnEntry(string $level, string $text, string $log = self::DEFAULT_LOG, ?TableNode $table = null, bool $dumpLogs = true, ?PyStringNode $pyStringNode = null): void
    {
        $allLogs = $this->getAllEntriesByLog($log);
        $tableRows = $this->getDataFromTableOrString($table, $pyStringNode);
        $strCmp = new StringCompare();
        foreach (($allLogs[$level] ?? []) as $logEntry) {
            if (!$strCmp->stringEquals($logEntry->getMessage(), $text)) {
                continue;
            }

            // Check context
            $context = $logEntry->getContext();
            foreach ($tableRows as $key => $val) {
                $foundVal = $context[$key] ?? null;

                // Context missing
                if (null === $foundVal) {
                    continue 2;
                }

                // Text compare
                if (\is_string($val)
                    && \is_scalar($foundVal)
                    && $strCmp->stringEquals((string) $foundVal, $val)) {
                    break;
                }

                // Array/Json compare
                if (\is_array($foundVal)) {
                    // Deprecated. Use PropertyAccess or JSON/YAML instead
                    if (\is_string($val)) {
                        /** @var array<array-key,mixed> $valArr */
                        $valArr = json_decode($val, true, 512, \JSON_THROW_ON_ERROR);
                    } elseif (\is_array($val)) {
                        $valArr = $val;
                    } else {
                        throw new \InvalidArgumentException('Expected json string or array to compare to another array');
                    }

                    $dc = new ArrayDeepCompare();
                    if ($dc->arrayEquals($foundVal, $valArr)) {
                        break;
                    }

                    continue 2;
                }

                continue 2;
            }

            return;
        }
        $message = 'Log entry not found.';
        if ($dumpLogs) {
            $message .= " Did you mean one of:\n  ";
            $message .= $this->getLogsOutput($log);
            if (isset($dc)) {
                $message .= "\n  ".$dc->getDifference();
            }
        }
        throw new \DomainException($message);
    }

    #[Then('the log does not contain any :level entries')]
    #[Then('the :log log does not contain any :level entries')]
    public function theLogDoesNotContainLevelEntries(string $level, string $log = self::DEFAULT_LOG): void
    {
        $allLogs = $this->getAllEntriesByLog($log);

        if (\count($allLogs[$level] ?? []) > 0) {
            throw new \DomainException("Log entries found: \n".$this->getLogsOutput($log));
        }
    }

    /** @return array<string, LogEntry[]> */
    protected function getAllEntriesByLog(string $log): array
    {
        return TestLogger::getAllLogs();
    }

    protected function getLogsOutput(string $log = self::DEFAULT_LOG): string
    {
        $output = [];
        foreach ($this->getAllEntriesByLog($log) as $level => $arr) {
            foreach ($arr as $logEntry) {
                $output[] = strtoupper($level).': '.$logEntry->getMessage().' '.json_encode($logEntry->getContext());
            }
        }

        return implode("\n  ", $output);
    }
}
