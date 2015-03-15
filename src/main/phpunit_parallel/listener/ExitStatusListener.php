<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestResult;

class ExitStatusListener extends AbstractTestListener
{
    private $hasErrors = false;
    private $hasSkipped = false;
    private $hasIncomplete = false;
    private $hasRisky = false;

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
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
