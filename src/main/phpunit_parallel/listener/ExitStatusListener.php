<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerChildProcess;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;

class ExitStatusListener implements TestEventListener
{
    private $hasErrors = false;
    private $hasSkipped = false;
    private $hasIncomplete = false;
    private $hasRisky = false;

    public function begin($workerCount, $testCount)
    {
    }

    public function testStarted(WorkerChildProcess $worker, TestRequest $request)
    {
    }

    public function testCompleted(WorkerChildProcess $worker, TestResult $result)
    {
        if ($result->getErrors()) {
            $this->hasErrors = true;
        }
        if ($result->getIncomplete()) {
            $this->hasIncomplete = true;
        }
        if ($result->getSkipped()) {
            $this->hasSkipped = true;
        }
        if ($result->getRisky()) {
            $this->hasRisky = true;
        }
    }

    public function end()
    {
    }

    public function getExitStatus()
    {
        if ($this->hasErrors) {
            return 1;
        }
        if ($this->hasIncomplete) {
            return 2;
        }
        if ($this->hasSkipped) {
            return 3;
        }
        if ($this->hasRisky) {
            return 4;
        }
    }
}
