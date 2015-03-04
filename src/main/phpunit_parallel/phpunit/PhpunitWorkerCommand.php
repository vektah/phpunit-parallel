<?php

namespace phpunit_parallel\phpunit;

use PHPUnit_Util_Configuration;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;
use phpunit_parallel\printer\SerializePrinter;

class PhpunitWorkerCommand extends \PHPUnit_TextUI_Command
{

    public function run(array $argv, $exit = true) {
        require_once(__DIR__ . '/../printer/SerializePrinter.php');
        $this->arguments['printer'] = $this->handlePrinter('phpunit_parallel\\printer\\SerializePrinter');

        $this->handleConfig();

        $runner = $this->createRunner();

        while ($testDetails = fgets(STDIN)) {
            if ($request = TestRequest::decode($testDetails)) {
                SerializePrinter::getInstance()->setCurrentRequest($request);
                $this->arguments['filter'] = $request->getName() . '$';

                $suite = new \PHPUnit_Framework_TestSuite($request->getClass());
                $suite->addTestFile($request->getFilename());

                $result = $runner->doRun($suite, $this->arguments);

                if ($result->count() === 0) {
                    $this->showError($request, "Test not found!");
                } elseif ($result->count() > 1) {
                    $this->showError($request, "Multiple tests executed!");
                }
            }
        }

        return 0;
    }

    private function showError(TestRequest $request, $string) {
        SerializePrinter::getInstance()->sendError(TestResult::errorFromRequest($request, $string));
    }

    /**
     * Taken from PHPUnit_TextUI_Command::handleArguments
     */
    public function handleConfig()
    {
        if (isset($this->arguments['configuration']) &&
            is_dir($this->arguments['configuration'])) {
            $configurationFile = $this->arguments['configuration'] .
                '/phpunit.xml';

            if (file_exists($configurationFile)) {
                $this->arguments['configuration'] = realpath(
                    $configurationFile
                );
            } elseif (file_exists($configurationFile . '.dist')) {
                $this->arguments['configuration'] = realpath(
                    $configurationFile . '.dist'
                );
            }
        } elseif (!isset($this->arguments['configuration']) &&
            $this->arguments['useDefaultConfiguration']) {
            if (file_exists('phpunit.xml')) {
                $this->arguments['configuration'] = realpath('phpunit.xml');
            } elseif (file_exists('phpunit.xml.dist')) {
                $this->arguments['configuration'] = realpath(
                    'phpunit.xml.dist'
                );
            }
        }

        if (isset($this->arguments['configuration'])) {
            try {
                $configuration = PHPUnit_Util_Configuration::getInstance(
                    $this->arguments['configuration']
                );
            } catch (\Exception $e) {
                print $e->getMessage() . "\n";
                exit(\PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
            }

            $phpunit = $configuration->getPHPUnitConfiguration();

            $configuration->handlePHPConfiguration();

            if (!isset($this->arguments['bootstrap']) && isset($phpunit['bootstrap'])) {
                $this->handleBootstrap($phpunit['bootstrap']);
            }

            if (isset($phpunit['testSuiteLoaderClass'])) {
                if (isset($phpunit['testSuiteLoaderFile'])) {
                    $file = $phpunit['testSuiteLoaderFile'];
                } else {
                    $file = '';
                }

                $this->arguments['loader'] = $this->handleLoader($phpunit['testSuiteLoaderClass'], $file);
            }
        }
    }
}
