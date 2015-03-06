<?php

namespace phpunit_parallel\listener;

use Phake;
use PHPUnit_Framework_TestCase;
use phpunit_parallel\TestDistributor;
use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\Error;
use phpunit_parallel\model\TestResult;

class StopOnErrorListenerTest extends PHPUnit_Framework_TestCase
{
    public function testStopOnError()
    {
        $distributor = Phake::mock(TestDistributor::class);
        $executor = Phake::mock(WorkerTestExecutor::class);

        $listener = new StopOnErrorListener($distributor);

        $result = new TestResult([
            'errors' => [new Error(['message' => 'it failed'])]
        ]);

        $listener->testCompleted($executor, $result);
        Phake::verify($distributor, Phake::times(1))->stop();
    }

    public function testNotStopOnOk()
    {
        $distributor = Phake::mock(TestDistributor::class);
        $executor = Phake::mock(WorkerTestExecutor::class);

        $listener = new StopOnErrorListener($distributor);

        $result = new TestResult();

        $listener->testCompleted($executor, $result);
        Phake::verify($distributor, Phake::never())->stop();
    }
}
