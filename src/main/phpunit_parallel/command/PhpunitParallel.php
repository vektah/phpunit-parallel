<?php

namespace phpunit_parallel\command;

use phpunit_parallel\TestDistributor;
use phpunit_parallel\listener\ExitStatusListener;
use phpunit_parallel\listener\ExpensiveTestListener;
use phpunit_parallel\listener\JsonOutputFormatter;
use phpunit_parallel\listener\LaneOutputFormatter;
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
        $this->addOption('formatter', 'F', InputOption::VALUE_REQUIRED, 'The formatter to use (xunit,tap,lane)', 'lane');
        $this->addOption('worker', 'w', InputOption::VALUE_NONE, 'Run as a worker, accepting a list of test files to run');
        $this->addArgument('filenames', InputArgument::IS_ARRAY, 'zero or more test filenames to run', []);
        $this->addOption('workers', 'C', InputOption::VALUE_REQUIRED, 'Number of workers to spawn', System::cpuCount() + 1);
        $this->addOption('write', 'W', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Takes a pair of format:filename, where format is one of the --formatter arguments');
        $this->addOption('stop-on-error', null, InputOption::VALUE_NONE, 'Stop if an error is encountered on any worker');
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

        $distributor = new TestDistributor($this->getTestSuite($config, $input->getArgument('filenames')));
        $distributor->addListener($this->getFormatter($formatter, $output));
        $distributor->addListener($exitStatus = new ExitStatusListener());
        if ($input->getOption('stop-on-error')) {
            $distributor->addListener(new StopOnErrorListener($distributor));
        }

        if ($formatter !== 'tap') {
            $distributor->addListener(new TestSummaryOutputFormatter($output));
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

    private function getTestSuite(\PHPUnit_Util_Configuration $config, array $filenames)
    {
        if ($filenames) {
            $tests = new \PHPUnit_Framework_TestSuite();
            foreach ($filenames as $filename) {
                foreach ($this->expandFilename($filename) as $test) {
                    $tests->addTestFile($test);
                }
            }
            return $tests;
        } else {
            return $config->getTestSuiteConfiguration();
        }
    }

    private function expandFilename($filename) {
        if (is_dir($filename)) {
            $directory = new \RecursiveDirectoryIterator($filename);
            $files = new \RecursiveIteratorIterator($directory);
            $files = new \RegexIterator($files, '#.php$#i', \RecursiveRegexIterator::GET_MATCH);

            $fileNames = [];
            foreach ($files as $filename => $file) {
                $fileNames[] = $filename;
            }
            return $fileNames;

        } elseif (is_file($filename)) {
            return [$filename];
        } else {
            throw new \RuntimeException("$filename does not exist!");
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
