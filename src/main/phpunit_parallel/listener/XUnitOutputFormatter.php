<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\Version;
use phpunit_parallel\ipc\WorkerTestExecutor;
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

    public function testStarted(WorkerTestExecutor $worker, TestRequest $request)
    {

    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->executedTests++;
        $message = '.';

        foreach ($result->getErrors() as $error) {
            if ($error->severity == 'error') {
                $message = '<error>E</error>';
            } elseif ($error->severity == 'warning') {
                $message = '<warn>W</warn>';
            } else {
                $message = '<error>F</error>';
            }
        }

        $this->output->write($message);
        $this->checkLineLength($this->executedTests);
    }

    public function end()
    {
        $this->output->writeln('');
        $this->output->writeln('');
    }

    private function checkLineLength($testNumber)
    {
        if ($this->executedTests % 63 === 0) {
            $progress = $testNumber / $this->expectedTests * 100;
            $this->output->writeln(sprintf("%5d / %5d (%3d%%)", $testNumber, $this->expectedTests, $progress));
        }
    }
}
