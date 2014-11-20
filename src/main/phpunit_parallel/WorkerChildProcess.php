<?php

namespace phpunit_parallel;

use phpunit_parallel\model\Error;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use phpunit_parallel\stream\BufferedReader;
use React\ChildProcess\Process;

class WorkerChildProcess
{
    private $id;
    private $loop;
    private $stdout;
    private $stderr;
    private $distributor;
    private $pendingRequests;

    public function __construct($id, $loop, TestDistributor $distributor)
    {
        $this->id = $id;
        $this->loop = $loop;
        $this->distributor = $distributor;
        $this->pendingRequests = new \SplQueue();

        $env = $_ENV;
        $env['TEST_TOKEN'] = substr(md5(rand()), 0, 7);

        $this->process = new Process(__DIR__ . '/../../../bin/phpunit-parallel --worker -vvv', null, $env);

        $this->start();

        $this->startTest();
    }

    private function debug($msg = '') {
        //echo "Worker{$this->id} DBG: $msg\n";
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
        $this->stdout = new BufferedReader($this->process->stdout);
        $this->stderr = new BufferedReader($this->process->stderr);

        $this->stdout->onLine(function ($line) {
            if ($testResult = TestResult::decode($line)) {
                /** @var TestRequest $nextExpectedTest */
                $nextExpectedTest = $this->pendingRequests->dequeue();

                if ($nextExpectedTest->getClass() !== $testResult->getClass() ||
                    $nextExpectedTest->getFilename() !== $testResult->getFilename()) {
                    $this->debug("Bad things");
                    $this->distributor->testCompleted($this, new TestResult($nextExpectedTest->getTestId(), $nextExpectedTest->getClass(), $nextExpectedTest->getName(), $nextExpectedTest->getFilename(), 0, [
                        new Error(['message' => "Was not run, bad things happened."]) // TODO: Retry on another worker? What about other pending tests?
                    ]));
                }

                $this->distributor->testCompleted($this, $testResult);

                $this->startTest();

                return $testResult;
            } else {
                echo "Worker{$this->id} STDOUT: " . $line;
                return null;
            }
        });

        $this->stderr->onLine(function($data) {
            echo 'Worker{$this->id} STDERR: ' . $data;
        });
    }

    public function getId()
    {
        return $this->id;
    }
}
