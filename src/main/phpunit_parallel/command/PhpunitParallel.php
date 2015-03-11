<?php

namespace phpunit_parallel\command;

use phpunit_parallel\TestDistributor;
use phpunit_parallel\TestLocator;
use phpunit_parallel\listener\ExitStatusListener;
use phpunit_parallel\listener\ExpensiveTestListener;
use phpunit_parallel\listener\JsonOutputFormatter;
use phpunit_parallel\listener\LaneOutputFormatter;
use phpunit_parallel\listener\NoiselessOutputFormatter;
use phpunit_parallel\listener\StopOnErrorListener;
use phpunit_parallel\listener\TapOutputFormatter;
use phpunit_parallel\listener\TestSummaryOutputFormatter;
use phpunit_parallel\listener\XUnitOutputFormatter;
use phpunit_parallel\phpunit\PhpunitWorkerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use vektah\common\System;

class PhpunitParallel extends Command
{
    public function configure()
    {
        $this->setName('phpunit-parallel');
        $this->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Read configuration from XML file.');
        $this->addOption('formatter', 'F', InputOption::VALUE_REQUIRED, 'The formatter to use (xunit,tap,lane,noiseless)', 'lane');
        $this->addOption('worker', 'w', InputOption::VALUE_NONE, 'Run as a worker, accepting a list of test files to run');
        $this->addArgument('filenames', InputArgument::IS_ARRAY, 'zero or more test filenames to run', []);
        $this->addOption('workers', 'C', InputOption::VALUE_REQUIRED, 'Number of workers to spawn', System::cpuCount() + 1);
        $this->addOption('write', 'W', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Takes a pair of format:filename, where format is one of the --formatter arguments');
        $this->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stop if an error is encountered on any worker');
        $this->addOption('replay', null, InputOption::VALUE_REQUIRED, 'filename:WorkerX - Replay from a result.json file the tests that ran on a single worker.');
        $this->addOption('interpreter-options', null, InputOption::VALUE_REQUIRED, 'Options to be passed through to the workers php interpreter');
    }

    public function runWorker()
    {
        $command = new PhpunitWorkerCommand();
        $args = $_SERVER['argv'];

        if (($key = array_search('--worker', $args)) !== false) {
            unset($args[$key]);
        }

        return $command->run($args);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('worker')) {
            return $this->runWorker();
        }

        $configFile = $this->getConfigFile($input);
        $config = \PHPUnit_Util_Configuration::getInstance($configFile);

        if (isset($config->getPHPUnitConfiguration()['bootstrap'])) {
            dont_leak_env_and_include($config->getPHPUnitConfiguration()['bootstrap']);
        }

        $formatter = $input->getOption('formatter');
        $tests = $this->getTestSuite($config, $input->getArgument('filenames'), $input->getOption('replay'));

        $distributor = new TestDistributor($tests, $input->getOption('interpreter-options'));
        $distributor->addListener($this->getFormatter($formatter, $output));
        $distributor->addListener($exitStatus = new ExitStatusListener());
        if ($input->getOption('stop-on-error')) {
            $distributor->addListener(new StopOnErrorListener($distributor));
        }

        if ($formatter == 'xunit' || $formatter == 'lane') {
            $distributor->addListener(new TestSummaryOutputFormatter($output));
        }

        if (!($formatter == 'tap' || $formatter == 'json')) {
            $distributor->addListener(new ExpensiveTestListener($output));
        }

        $this->handleWriters($input->getOption('write'), $distributor);

        $distributor->run($input->getOption('workers'));

        return $exitStatus->getExitStatus();
    }

    private function handleWriters(array $writers, TestDistributor $distributor)
    {
        foreach ($writers as $writer) {
            if (!strpos($writer, ':')) {
                throw new \InvalidArgumentException("Writers must be in the format format:filename");
            }

            list($formatter, $filename) = explode(':', $writer);

            $file = fopen($filename, 'w');
            $output = new StreamOutput($file);
            $distributor->addListener($this->getFormatter($formatter, $output));
        }
    }

    private function getTestSuite(\PHPUnit_Util_Configuration $config, array $filenames, $replay)
    {
        $testLocator = new TestLocator();

        if ($replay) {
            if (strpos($replay, ':') === false) {
                throw new \RuntimeException("Replay must be in the form filename:WorkerX");
            }

            list($filename, $workerId) = explode(':', $replay, 2);

            return $testLocator->getTestsFromReplay($filename, $workerId);

        } elseif ($filenames) {
            return $testLocator->getTestsFromFilenames($filenames);
        } else {
            return $testLocator->getTestsFromConfig($config);
        }
    }

    private function getFormatter($formatterName, OutputInterface $output) {
        switch ($formatterName) {
            case 'tap':
                return new TapOutputFormatter($output);

            case 'lane':
                return new LaneOutputFormatter($output);

            case 'xunit':
                return new XUnitOutputFormatter($output);

            case 'json':
                return new JsonOutputFormatter($output);

            case 'noiseless':
                return new NoiselessOutputFormatter($output);

            default:
                throw new \RuntimeException("Unknown formatter $formatterName");
        }
    }

    private function getConfigFile(InputInterface $input)
    {
        if ($configuration = $input->getOption('configuration')) {
            return $configuration;
        }

        if (file_exists('phpunit.xml')) {
            return 'phpunit.xml';
        }

        if (file_exists('phpunit.xml.dist')) {
            return 'phpunit.xml.dist';
        }

        throw new \RuntimeException('Unable to find config file');
    }
}

function dont_leak_env_and_include($file) {
    include($file);
}
