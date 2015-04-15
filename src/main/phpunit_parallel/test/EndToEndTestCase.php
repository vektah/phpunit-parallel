<?php

namespace phpunit_parallel\test;

use React\ChildProcess\Process;
use React\EventLoop\Factory;
use vektah\common\json\Json;

class EndToEndTestCase extends \PHPUnit_Framework_TestCase
{
    public function runTestProcess($arguments, $expectedExitCode = 0, $timeout = 5)
    {
        $command = __DIR__ . '/../../../../bin/phpunit-parallel ' . $arguments;

        $loop = Factory::create();

        $p = new Process($command);

        $p->start($loop);

        $stdout = '';
        $stderr = '';

        $timer = $loop->addTimer($timeout, function() use ($arguments, $p, $stdout, $stderr) {
            $p->terminate(SIGKILL);
            $this->fail("running phpunit-parallel with '$arguments' did not complete in time\nstdout: $stdout\nstderr: $stderr\n");
        });

        $p->stdout->on('data', function($data) use (&$stdout) {
            $stdout .= $data;
        });

        $p->stderr->on('data', function($data) use (&$stderr) {
            $stderr .= $data;
        });

        $p->on('exit', function() use ($timer) {
            $timer->cancel();
        });

        $loop->run();
        if ($p->getExitCode() !== $expectedExitCode) {
            $this->fail("Process exited with code {$p->getExitCode()}, expected $expectedExitCode\nstdout: $stdout\nstderr: $stderr\n");
        }

        return [$stdout, $stderr];
    }

    public function assertNumberOfSuccessfulResults($expectedCount, $jsonTestResults) {
        $results = Json::decode($jsonTestResults);
        $count = 0;

        foreach ($results as $worker) {
            foreach ($worker as $testResult) {
                if (!$testResult['errors']) {
                    $count++;
                }
            }
        }

        $this->assertEquals($expectedCount, $count);
    }

    public function assertNumberOfFailures($expectedCount, $jsonTestResults) {
        $results = Json::decode($jsonTestResults);
        $count = 0;

        foreach ($results as $worker) {
            foreach ($worker as $testResult) {
                if ($testResult['errors']) {
                    $count++;
                }
            }
        }

        $this->assertEquals($expectedCount, $count);
    }
}
