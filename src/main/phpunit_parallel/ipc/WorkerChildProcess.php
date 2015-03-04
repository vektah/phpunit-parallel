<?php

namespace phpunit_parallel\ipc;

use phpunit_parallel\TestDistributor;
use phpunit_parallel\model\Error;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use phpunit_parallel\stream\BufferedReader;
use React\EventLoop\LoopInterface;

class WorkerChildProcess
{
    private $id;
    private $loop;
    private $comm;
    private $distributor;
    private $pendingRequests;

    private $testErr;

    public function __construct($id, LoopInterface $loop, TestDistributor $distributor)
    {
        $this->id = $id;
        $this->loop = $loop;
        $this->distributor = $distributor;
        $this->pendingRequests = new \SplQueue();

        $env = $_ENV;
        $env['TEST_TOKEN'] = substr(md5(rand()), 0, 7);

        $this->process = new FourChannelProcess(__DIR__ . '/../../../../bin/phpunit-parallel --worker -vvv', null, $env);

        $this->start();

        $this->startTest();
    }

    private function debug($msg = '') {
//        echo "Worker{$this->id} DBG: $msg\n";
    }

    private function startTest() {
        $test = $this->distributor->getNextTest($this);

        if (!$test) {
            if ($this->pendingRequests->count() === 0) {
                $this->stop();
            }
            return;
        }

        $this->debug("Added " . $test->encode());

        $this->testErr = '';
        $this->pendingRequests->enqueue($test);
        $this->process->stdin->write($test->encode());
    }

    public function stop()
    {
        $this->process->close();
    }

    public function start()
    {
        $this->process->start($this->loop);
        $this->comm = new BufferedReader($this->process->comm);

        $this->process->stderr->on('data', function($data) {
            $this->testErr .= $data;
        });

        $this->process->stdout->on('data', function($data) {
            echo $data;
        });

        $this->process->on('exit', function() {
            if ($this->pendingRequests->count() > 0) {
                $nextExpectedTest = $this->pendingRequests->dequeue();
                $this->distributor->testCompleted(
                    $this,
                    TestResult::errorFromRequest($nextExpectedTest, "Worker{$this->id} died\n{$this->testErr}")
                );
            }
        });

        $this->comm->onLine(function ($line) {
            if ($testResult = TestResult::decode($line)) {
                /** @var TestRequest $nextExpectedTest */
                $nextExpectedTest = $this->pendingRequests->dequeue();

                if ($nextExpectedTest->getClass() !== $testResult->getClass() ||
                    $nextExpectedTest->getName() !== $testResult->getName()) {
                    $this->debug("Bad things");

                    // TODO: Retry on another worker? What about other pending tests?
                    $this->distributor->testCompleted(
                        $this,
                        TestResult::errorFromRequest(
                            $nextExpectedTest,
                            "An unexpected test was run, this could be a naming issue:\n" .
                            "  Expected {$nextExpectedTest->getName()}::{$nextExpectedTest->getName()}\n" .
                            "  Got {$testResult->getName()}::{$testResult->getName()}\n"
                        )
                    );
                }

                if ($this->testErr) {
                    $testResult->addError(new Error(['message' => "STDERR: {$this->testErr}"]));
                }

                $this->distributor->testCompleted($this, $testResult);

                $this->startTest();
            }
        });
    }

    public function getId()
    {
        return $this->id;
    }
}
