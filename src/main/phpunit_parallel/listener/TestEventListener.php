<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;

interface TestEventListener
{
    public function begin($workerCount, $testCount);
    public function testStarted(WorkerTestExecutor $worker, TestRequest $request);
    public function testCompleted(WorkerTestExecutor $worker, TestResult $result);
    public function end();
}
