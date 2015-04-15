<?php

namespace phpunit_parallel;

use phpunit_parallel\ipc\WorkerProcess;
use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\listener\TestEventListener;
use phpunit_parallel\model\TestResult;
use React\EventLoop\Factory;
use vektah\common\subscriber\SubscriberList;

class TestDistributor
{
    private $loop;
    private $tests;
    private $listeners;
    private $workers = [];
    /** @var string */
    private $interpreterOptions;
    private $workerOptions;

    public function __construct(array $testRequests, $interpreterOptions, array $workerOptions)
    {
        $this->loop = Factory::create();
        $this->listeners = new SubscriberList();

        $this->tests = new \SplQueue();
        foreach ($testRequests as $test) {
            $this->tests->push($test);
        }

        $this->interpreterOptions = $interpreterOptions;
        $this->workerOptions = $workerOptions;
    }

    public function addListener(TestEventListener $listener)
    {
        $this->listeners->append($listener);

        $listener->init($this);
    }

    private function runNextTestOn(WorkerTestExecutor $worker) {
        if (count($this->tests) === 0) {
            $worker->stop();
            return null;
        }

        $test = $this->tests->dequeue();

        $this->listeners->testStarted($worker, $test);
        $worker->run($test);
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
            $process = new WorkerProcess($this->loop, $this->interpreterOptions, $i, $this->workerOptions);
            $process->addListener($worker = new WorkerTestExecutor($i, $this, $process));
            $this->runNextTestOn($worker);
            $this->workers[] = $worker;
        }

        $this->loop->run();

        $this->listeners->end();
    }

    public function isTrackingMemory()
    {
        return isset($this->workerOptions['--memory-tracking']) && $this->workerOptions['----memory-tracking'] == true;
    }
}
