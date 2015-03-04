<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Finds the 5 most expensive tests by time and memory usage.
 */
class ExpensiveTestListener implements TestEventListener
{
    private $byMemory;
    private $byDuration;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->byMemory = new \SplPriorityQueue();
        $this->byDuration = new \SplPriorityQueue();
    }

    public function begin($workerCount, $testCount)
    {
    }

    public function testStarted(WorkerTestExecutor $worker, TestRequest $request)
    {
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->byMemory->insert($result, $result->getMemoryUsed());
        $this->byDuration->insert($result, $result->getElapsed());
    }

    public function end()
    {
        if (count($this->byMemory) > 0) {
            $this->output->writeln("Most expensive tests by memory usage are:");
            for ($i = 0; $i < 5; $i++) {
                if ($test = $this->byMemory->extract()) {
                    $this->output->writeln(sprintf(
                        ' %5.1fMB %s::%s',
                        $test->getMemoryUsed() / 1024 / 1024,
                        $test->getClass(),
                        $test->getName()
                    ));
                }
            }
            $this->output->writeln('');
        }

        if (count($this->byDuration) > 0) {
            $this->output->writeln("Most expensive tests by duration are:");
            for ($i = 0; $i < 5; $i++) {
                if ($test = $this->byDuration->extract()) {
                    $this->output->writeln(sprintf(
                        ' %5dms %s::%s',
                        $test->getElapsed() * 1000,
                        $test->getClass(),
                        $test->getName()
                    ));
                }
            }
            $this->output->writeln('');
        }
    }
}
