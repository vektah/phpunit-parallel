<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\TestDistributor;
use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestResult;

class StopOnErrorListener extends AbstractTestListener
{
    private $distributor;

    public function __construct(TestDistributor $distributor)
    {
        $this->distributor = $distributor;
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        if (count($result->getErrors()) > 0) {
            $this->distributor->stop();
        }
    }
}
