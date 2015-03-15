<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\TestDistributor;
use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class NoiselessOutputFormatter extends AbstractTestListener
{
    private $expectedTests;
    private $executedTests = 0;
    private $lastReported = 0;
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $output->getFormatter()->setStyle('good', new OutputFormatterStyle('black', 'green'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));
    }

    public function init(TestDistributor $distributor)
    {
        if ($distributor->isTrackingMemory()) {
            $distributor->addListener(new HighMemoryTestListener($this->output));
        }

        $distributor->addListener(new LongTestListener($this->output));
    }

    public function begin($workerCount, $testCount)
    {
        $this->expectedTests = $testCount;
        $this->printStatus();
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->executedTests++;

        $percentage = $this->executedTests / $this->expectedTests;
        if ($percentage - $this->lastReported > 0.1) {
            $this->lastReported = $percentage;
            $this->printStatus();
        }

        foreach ($result->getErrors() as $error) {
            $this->output->writeln('');
            $this->output->writeln("<warn>{$error->severity} in {$result->getClass()}::{$result->getName()}</warn>");

            $indentedError = '  ' . str_replace("\n", "\n  ", $error->getFormatted());
            $this->output->writeln($indentedError);
        }
    }

    public function end()
    {
        $this->output->writeln("Complete. {$this->executedTests} tests run.");
        $this->output->writeln('');
    }

    private function printStatus() {
        $this->output->writeln(sprintf(
            "Testing in progress... %4d/%-4d %2d%%",
            $this->executedTests,
            $this->expectedTests,
            ($this->executedTests / $this->expectedTests) * 100
        ));
    }
}
