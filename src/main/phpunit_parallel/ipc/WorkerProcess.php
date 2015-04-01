<?php

namespace phpunit_parallel\ipc;

use phpunit_parallel\model\TestResult;
use phpunit_parallel\stream\BufferedReader;
use React\EventLoop\LoopInterface;
use vektah\common\subscriber\SubscriberList;

class WorkerProcess
{
    private $loop;
    private $comm;
    private $listeners;

    public function __construct(LoopInterface $loop, $interpreterOptions, $trackMemory = true, $configFilename = null)
    {
        $this->loop = $loop;
        $this->listeners = new SubscriberList();

        $env = $_ENV;
        $env['TEST_TOKEN'] = substr(md5(rand()), 0, 7);

        $arguments = [
            "php $interpreterOptions -d display_errors=stderr",
            __DIR__ . '/../../../../bin/phpunit-parallel',
            '--worker',
            '-vvv',
            'memory-tracking ' . ($trackMemory ? 'true' : 'false'),
        ];

        if ($configFilename) {
            $arguments[] = "--configuration $configFilename";
        }

        $cmd = implode(' ', $arguments);

        $this->process = new FourChannelProcess($cmd, getcwd(), $env);

        $this->process->on('exit', function($exitCode) {
            $this->listeners->onExit($exitCode);
        });

        $this->start();
    }

    public function addListener(WorkerListener $listener)
    {
        $this->listeners->append($listener);
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
            $this->listeners->onStdErr($data);
        });

        $this->process->stdout->on('data', function($data) {
            $this->listeners->onStdOut($data);
        });

        $this->comm->onLine(function ($line) {
            if ($testResult = TestResult::decode($line)) {
                $this->listeners->onTestResult($testResult);
            }
        });
    }

    public function write($string)
    {
        $this->process->stdin->write($string);
    }

    public function isRunning()
    {
        return $this->process->isRunning();
    }
}
