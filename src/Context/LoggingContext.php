<?php

declare(strict_types=1);

namespace Elbformat\SymfonyBehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Behat\Testwork\Hook\Scope\AfterTestScope;
use Elbformat\SymfonyBehatBundle\Helper\ArrayDeepCompare;
use Elbformat\SymfonyBehatBundle\Logger\TestLogger;

/**
 * Test interactions with the logger service.
 *
 * @author Hannes Giesenow <hannes.giesenow@elbformat.de>
 */
class LoggingContext implements Context
{
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
    public function theLogContainsAnEntry(string $level, string $text, ?TableNode $table = null, bool $dumpLogs = true): void
    {
        $logs = TestLogger::getLogs($level);
        $tableRows = null !== $table ? $table->getRowsHash() : [];
        foreach ($logs as $logEntry) {
            if ($logEntry->getMessage() !== $text) {
                continue;
            }
            // Check context
            $context = $logEntry->getContext();
            /** @var string $val */
            foreach ($tableRows as $key => $val) {
                /** @var mixed $foundVal */
                $foundVal = $context[$key] ?? null;

                // Context missing
                if (null === $foundVal) {
                    continue 2;
                }

                // Regex compare
                if (str_starts_with($val, '~') && preg_match('/'.preg_quote(substr($val, -1), '/').'/', (string) $foundVal)) {
                    break;
                }

                // Simple Compare
                if ($foundVal === $val) {
                    break;
                }

                // Array/Json compare
                if (\is_array($foundVal)) {
                    /** @var array $valArr */
                    $valArr = json_decode($val, true, 512, \JSON_THROW_ON_ERROR);

                    $dc = new ArrayDeepCompare();
                    if ($dc->arrayEquals($foundVal, $valArr)) {
                        break;
                    }
                    echo $dc->getDifference();

                    continue 2;
                }

                continue 2;
            }

            return;
        }
        $message = 'Log entry not found.';
        if ($dumpLogs) {
            $message .= " Did you mean one of:\n  ";
            $message .= $this->getLogsOutput();
        }
        throw new \DomainException($message);
    }

    protected function getLogsOutput(): string
    {
        $output = [];
        foreach (TestLogger::getAllLogs() as $level => $arr) {
            foreach ($arr as $logEntry) {
                $output[] = strtoupper($level).': '.$logEntry->getMessage();
            }
        }

        return implode("\n  ", $output);
    }
}
