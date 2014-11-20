<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\Version;
use phpunit_parallel\WorkerChildProcess;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class XUnitOutputFormatter implements TestEventListener
{
    private $workerCount;
    private $expectedTests;
    private $executedTests = 0;
    private $startTime;
    private $errors = [];
    private $warnings = [];
    private $fatals = [];
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));
    }

    public function begin($workerCount, $testCount)
    {
        $this->workerCount = $workerCount;
        $this->expectedTests = $testCount;
        $this->startTime = microtime(true);

        $this->output->writeln("PHPUnit Parallel " . Version::VERSION);
        $this->output->writeln('');
    }

    public function testStarted(WorkerChildProcess $worker, TestRequest $request)
    {

    }

    public function testCompleted(WorkerChildProcess $worker, TestResult $result)
    {
        $this->executedTests++;
        $message = '.';

        foreach ($result->getErrors() as $error) {
            if ($error->severity == 'error') {
                $message = '<error>E</error>';
                $this->errors[] = $error;
            } elseif ($error->severity == 'warning') {
                $message = '<warn>W</warn>';
                $this->warnings[] = $error;
            } else {
                $message = '<error>F</error>';
                $this->fatals[] = $error;
            }
        }

        $this->output->write($message);
        $this->checkLineLength($this->executedTests);
    }

    public function end()
    {
        $this->output->writeln('');
        $this->output->writeln('');

        $elapsed = microtime(true) - $this->startTime;

        $this->output->writeln('');
        $this->output->writeln('');
        $memoryUsage = memory_get_peak_usage() / 1024 / 1024;

        $this->output->writeln(sprintf('Time: %0.2f seconds, Memory: %0.2fMb', $elapsed, $memoryUsage));

        $this->output->writeln('');

        $errorCount = count($this->errors);
        if ($errorCount > 0) {
            $this->output->writeln("There were $errorCount failures:");
            $this->output->writeln('');

            foreach ($this->errors as $errorNumber => $error) {
                $errorNumber++;
                $this->output->writeln("{$errorNumber}) {$error->getFormatted()}");
            }

            $this->output->writeln('');
        }

        $warningCount = count($this->warnings);
        if ($warningCount > 0) {
            $this->output->writeln("There were $warningCount warnings:");
            $this->output->writeln('');

            foreach ($this->warnings as $warningNumber => $warning) {
                $warningNumber++;
                $this->output->writeln("{$warningNumber}) {$warning->getFormatted()}");
            }
            $this->output->writeln('');
        }

        $fatalCount = count($this->fatals);
        if ($fatalCount > 0) {
            $this->output->writeln("There were $fatalCount fatal issues:");
            $this->output->writeln('');

            foreach ($this->fatals as $fatalNumber => $fatal) {
                $fatalNumber++;
                $this->output->writeln("{$fatalNumber}) {$fatal->getFormatted()}");
            }
            $this->output->writeln('');
        }

        if (!($this->errors || $this->warnings || $this->fatals)) {
            $this->output->writeln("OK ({$this->executedTests} tests)");
        }
    }

    private function checkLineLength($testNumber)
    {
        if ($this->executedTests % 63 === 0) {
            $progress = $testNumber / $this->expectedTests * 100;
            $this->output->writeln(sprintf("%5d / %5d (%3d%%)", $testNumber, $this->expectedTests, $progress));
        }
    }
}
