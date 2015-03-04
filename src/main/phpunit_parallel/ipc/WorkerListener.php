<?php

namespace phpunit_parallel\ipc;

use phpunit_parallel\model\TestResult;

interface WorkerListener
{
    public function onStdOut($string);
    public function onStdErr($string);
    public function onTestResult(TestResult $result);
    public function onExit($status);
}
