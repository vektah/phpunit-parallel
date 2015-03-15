<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Finds the 5 most expensive tests by time and memory usage.
 */
class LongTestListener extends AbstractTestListener
{
    private $byDuration;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->byDuration = new \SplPriorityQueue();
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->byDuration->insert($result, $result->getElapsed());
    }

    public function end()
    {
        if (count($this->byDuration) > 0) {
            $this->output->writeln("Most expensive tests by duration are:");
            for ($i = 0; $i < 5; $i++) {
                if (!$this->byDuration->isEmpty()) {
                    $test = $this->byDuration->extract();
                    $this->output->writeln(sprintf(
                        ' %5dms %s::%s',
                        $test->getElapsed() * 1000,
                        $test->getShortClassName(),
                        $test->getName()
                    ));
                }
            }
            $this->output->writeln('');
        }
    }
}
