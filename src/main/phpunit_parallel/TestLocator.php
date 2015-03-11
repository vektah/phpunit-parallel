<?php

namespace phpunit_parallel;

use PHPUnit_Framework_TestSuite as TestSuite;
use phpunit_parallel\model\ResultList;
use phpunit_parallel\model\TestRequest;
use vektah\common\json\Json;

class TestLocator
{
    private $lastTestId = 0;

    public function getTestsFromReplay($file, $worker)
    {
        $resultsRaw = Json::decode(file_get_contents($file));
        if (!isset($resultsRaw[$worker])) {
            throw new \RuntimeException("Invalid worker id, available in this dump are : " . implode(", ", array_keys($resultsRaw)));
        }

        $results = new ResultList($worker, $resultsRaw[$worker]);

        $tests = [];
        /** @var TestResult $result */
        foreach ($results as $result) {
            $tests[] = new TestRequest(
                $result->getId(),
                $result->getClass(),
                $result->getFilename(),
                $result->getName()
            );
        }

        return $tests;
    }

    public function getTestsFromFilenames(array $filenames)
    {
        $tests = new \PHPUnit_Framework_TestSuite();
        foreach ($filenames as $filename) {
            foreach ($this->expandFilename($filename) as $test) {
                $tests->addTestFile($test);
            }
        }
        return $this->enumerateTests($tests);
    }

    public function getTestsFromConfig(\PHPUnit_Util_Configuration $config) {
        return $this->enumerateTests($config->getTestSuiteConfiguration());
    }

    private function enumerateTests($tests) {
        $enumeratedTests = [];

        foreach ($tests as $test) {
            if ($test instanceof \PHPUnit_Framework_Warning) {
                // What. The. Hell?
            } elseif ($test instanceof TestSuite) {
                foreach ($this->enumerateTests($test) as $subtest) {
                    $enumeratedTests[] = $subtest;
                }
            } elseif ($test instanceof \PHPUnit_Framework_TestCase) {
                $reflectionClass = new \ReflectionClass(get_class($test));
                $filename = $reflectionClass->getFileName();

                $request = new TestRequest(++$this->lastTestId, get_class($test), $filename, $test->getName());

                $enumeratedTests[] = $request;
            } else {
                throw new \RuntimeException("Unexpected test class type: " . get_class($test));
            }
        }

        return $enumeratedTests;
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
}
