<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Finds the 5 most expensive tests by time and memory usage.
 */
class HighMemoryTestListener extends AbstractTestListener
{
    private $byMemory;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->byMemory = new \SplPriorityQueue();
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->byMemory->insert($result, $result->getMemoryUsed());
    }

    public function end()
    {
        if (count($this->byMemory) > 0) {
            $this->output->writeln("Most expensive tests by memory usage are:");
            for ($i = 0; $i < 5; $i++) {
                if (!$this->byMemory->isEmpty()) {
                    $test = $this->byMemory->extract();
                    $this->output->writeln(sprintf(
                        ' %5.1fMB %s::%s',
                        $test->getMemoryUsed() / 1024 / 1024,
                        $test->getShortClassName(),
                        $test->getName()
                    ));
                }
            }
            $this->output->writeln('');
        }
    }
}
