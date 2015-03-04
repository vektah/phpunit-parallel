<?php

namespace phpunit_parallel\ipc;

use phpunit_parallel\model\Error;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use phpunit_parallel\TestDistributor;

class WorkerTestExecutor implements WorkerListener
{
    /** @var int */
    private $id;

    /** @var \SplQueue */
    private $pendingRequests;

    /** @var TestDistributor */
    private $distributor;

    /** @var string */
    private $testErr = '';

    /** @var WorkerProcess */
    private $process;

    public function __construct($id, TestDistributor $distributor, WorkerProcess $process)
    {
        $this->id = $id;
        $this->distributor = $distributor;
        $this->pendingRequests = new \SplQueue();
        $this->process = $process;
    }

    public function run(TestRequest $test) {
        $this->debug("Added " . $test->encode());

        $this->testErr = '';
        $this->pendingRequests->enqueue($test);
        $this->process->write($test->encode());
    }

    public function getId()
    {
        return $this->id;
    }

    public function onTestResult(TestResult $testResult)
    {
        /** @var TestRequest $nextExpectedTest */
        $nextExpectedTest = $this->pendingRequests->dequeue();

        if ($nextExpectedTest->getId() !== $testResult->getId()) {
            $this->debug("Bad things");

            $testResult->addError(new Error([
                'message' => "An unexpected test was run, this could be a naming issue:\n" .
                    "  Expected #{$nextExpectedTest->getId()} - {$nextExpectedTest->getClass()}::{$nextExpectedTest->getName()}\n" .
                    "  Got #{$testResult->getId()} - {$testResult->getClass()}::{$testResult->getName()}\n"
            ]));
        }

        if ($this->testErr) {
            $testResult->addError(new Error(['message' => "STDERR: {$this->testErr}"]));
        }

        $this->distributor->testCompleted($this, $testResult);
    }

    public function onStdOut($string)
    {
        echo $string;
    }

    public function onStdErr($string)
    {
        $this->testErr .= $string;
    }

    public function onExit($status)
    {
        if ($this->pendingRequests->count() > 0) {
            $nextExpectedTest = $this->pendingRequests->dequeue();
            $this->distributor->testCompleted(
                $this,
                TestResult::errorFromRequest($nextExpectedTest, "Worker{$this->id} died\n{$this->testErr}")
            );
        } elseif ($status) {
            $this->distributor->testCompleted(
                $this,
                new TestResult([
                    'errors' => [new Error(['message' => "Worker{$this->id} died while not running any tests! code: ${status}\n{$this->testErr}"])]
                ])
            );
        }
    }

    private function debug($msg = '') {
//        echo "Worker{$this->id} DBG: $msg\n";
    }

    public function stop()
    {
        $this->process->stop();
    }
}
