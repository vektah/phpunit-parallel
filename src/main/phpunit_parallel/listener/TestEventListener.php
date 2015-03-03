<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerChildProcess;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;

interface TestEventListener
{
    public function begin($workerCount, $testCount);
    public function testStarted(WorkerChildProcess $worker, TestRequest $request);
    public function testCompleted(WorkerChildProcess $worker, TestResult $result);
    public function end();
}
