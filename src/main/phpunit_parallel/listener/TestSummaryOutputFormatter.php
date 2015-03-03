<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerChildProcess;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Outputs a list of failing tests at the end. To be used together with xunit/lane output.
 */
class TestSummaryOutputFormatter implements TestEventListener
{
    private $executedTests = 0;
    private $startTime;
    private $errors = [];
    private $warnings = [];
    private $fatals = [];
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function begin($workerCount, $testCount)
    {
        $this->startTime = microtime(true);
    }

    public function testStarted(WorkerChildProcess $worker, TestRequest $request)
    {

    }

    public function testCompleted(WorkerChildProcess $worker, TestResult $result)
    {
        $this->executedTests++;

        foreach ($result->getErrors() as $error) {
            if ($error->severity == 'error') {
                $this->errors[] = [$result, $error];
            } elseif ($error->severity == 'warning') {
                $this->warnings[] = [$result, $error];
            } else {
                $this->fatals[] = [$result, $error];
            }
        }
    }

    public function end()
    {
        $elapsed = microtime(true) - $this->startTime;

        $memoryUsage = memory_get_peak_usage() / 1024 / 1024;

        $this->output->writeln(sprintf('Time: %0.2f seconds, Memory: %0.2fMb', $elapsed, $memoryUsage));

        $this->output->writeln('');

        $errorCount = count($this->errors);
        if ($errorCount > 0) {
            $this->output->writeln("There were $errorCount failures:");
            $this->output->writeln('');

            foreach ($this->errors as $errorNumber => $error) {
                $errorNumber++;
                $this->printError($errorNumber, $error);
            }
        }

        $warningCount = count($this->warnings);
        if ($warningCount > 0) {
            $this->output->writeln("There were $warningCount warnings:");
            $this->output->writeln('');

            foreach ($this->warnings as $warningNumber => $warning) {
                $warningNumber++;
                $this->printError($warningNumber, $warning);
            }
        }

        $fatalCount = count($this->fatals);
        if ($fatalCount > 0) {
            $this->output->writeln("There were $fatalCount fatal issues:");
            $this->output->writeln('');

            foreach ($this->fatals as $fatalNumber => $fatal) {
                $fatalNumber++;
                $this->printError($fatalNumber, $fatal);
            }
        }

        if (!($this->errors || $this->warnings || $this->fatals)) {
            $this->output->writeln("OK ({$this->executedTests} tests)");
        }
    }

    private function printError($errorNumber, array $errorData) {
        list ($test, $error) = $errorData;

        $this->output->writeln("{$errorNumber}) {$test->getClass()}::{$test->getName()}");

        $indentedError = '  ' . str_replace("\n", "\n  ", $error->getFormatted());
        $this->output->writeln($indentedError);
        $this->output->writeln('');
    }
}
