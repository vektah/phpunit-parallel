<?php

namespace phpunit_parallel;

use PHPUnit_Framework_TestSuite as TestSuite;
use phpunit_parallel\ipc\WorkerProcess;
use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\listener\TestEventListener;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use React\EventLoop\Factory;
use vektah\common\subscriber\SubscriberList;

class TestDistributor
{
    private $loop;

    private $tests;

    private $lastTestId = 0;

    private $listeners;

    private $workers = [];

    public function __construct(TestSuite $suite)
    {
        $this->loop = Factory::create();
        $this->listeners = new SubscriberList();

        $this->tests = new \SplQueue();
        $this->enumerateTests($suite);
    }

    public function addListener(TestEventListener $listener)
    {
        $this->listeners->append($listener);
    }

    private function enumerateTests($tests) {
        foreach ($tests as $test) {
            if ($test instanceof \PHPUnit_Framework_Warning) {
                // What. The. Hell?
            } elseif ($test instanceof TestSuite) {
                $this->enumerateTests($test);
            } elseif ($test instanceof \PHPUnit_Framework_TestCase) {
                $this->tests->enqueue($test);
            } else {
                throw new \RuntimeException("Unexpected test class type: " . get_class($test));
            }
        }
    }

    private function runNextTestOn(WorkerTestExecutor $worker) {
        if (count($this->tests) === 0) {
            $worker->stop();
            return null;
        }

        $test = $this->tests->dequeue();
        $reflectionClass = new \ReflectionClass(get_class($test));
        $filename = $reflectionClass->getFileName();

        $request = new TestRequest(++$this->lastTestId, get_class($test), $filename, $test->getName());
        $this->listeners->testStarted($worker, $request);

        $worker->run($request);
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->listeners->testCompleted($worker, $result);
        $this->runNextTestOn($worker);
    }

    public function stop()
    {
        foreach ($this->workers as $worker) {
            $worker->stop();
        }
        $this->loop->stop();
    }

    public function run($numWorkers)
    {
        $numWorkers = min(count($this->tests), $numWorkers);

        $this->listeners->begin($numWorkers, count($this->tests));

        for ($i = 0; $i < $numWorkers; $i++) {
            $process = new WorkerProcess($this->loop);
            $process->addListener($worker = new WorkerTestExecutor($i, $this, $process));
            $this->runNextTestOn($worker);
            $this->workers[] = $worker;
        }

        $this->loop->run();

        $this->listeners->end();
    }
}
