<?php

namespace phpunit_parallel\listener;

use phpunit_parallel\ipc\WorkerTestExecutor;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use Symfony\Component\Console\Output\OutputInterface;
use vektah\common\json\Json;

class JsonOutputFormatter implements TestEventListener
{
    private $output;
    private $json;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function begin($workerCount, $testCount)
    {
    }

    public function testStarted(WorkerTestExecutor $worker, TestRequest $request)
    {
    }

    public function testCompleted(WorkerTestExecutor $worker, TestResult $result)
    {
        $this->json["Worker{$worker->getId()}"][] = $result->toArray();
    }

    public function end()
    {
        $this->output->writeln(Json::encode($this->json));
    }
}
