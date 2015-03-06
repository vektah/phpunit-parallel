<?php

namespace phpunit_parallel\command;

use phpunit_parallel\model\ResultList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vektah\common\json\Json;

class CommonAncestor extends Command
{
    public function configure()
    {
        $this->setName('common-ancestor');
        $this->setDescription('This will group test failures from a number of json test results and look for common tests in the workers history.');

        $this->addArgument('results', InputArgument::IS_ARRAY, 'The json results to analyze');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $runs = $this->loadRuns($input->getArgument('results'));

        $groupedErrors = [];

        foreach ($runs as $runId => $run) {
            foreach ($run as $resultList) {
                foreach ($resultList->getErrors() as $resultId => $error) {
                    $precedingTestNames = [];
                    foreach ($resultList->getAllTestsBefore($resultId) as $precedingTest) {
                        $precedingTestNames["{$precedingTest->getShortClassName()}::{$precedingTest->getName()}"] = 1;
                    }

                    $groupedErrors[$error->message][] = [
                        'error' => $error,
                        'precedingTests' => $precedingTestNames,
                        'runId' => $runId
                    ];
                }
            }
        }

        foreach ($groupedErrors as $message => $errorSummaries) {
            $numSamples = count($errorSummaries);

            if ($numSamples == 0) {
                $output->writeln("Wat? No errors with $message. This is a bug.");
                continue;
            }

            $firstTest = array_pop($errorSummaries);
            $output->writeln($firstTest['error']->getFormatted());

            if ($numSamples == 1) {
                $output->writeln("Not enough samples to collect precursor data.");
                continue;
            }

            $precedingTestCount = $firstTest['precedingTests'];

            foreach ($errorSummaries as $summary) {
                foreach ($summary['precedingTests'] as $precedingTest => $_) {
                    if (isset($precedingTestCount[$precedingTest])) {
                        $precedingTestCount[$precedingTest]++;
                    } else {
                        $precedingTestCount[$precedingTest] = 1;
                    }
                }
            }

            $output->writeln("These tests may be precursors:");
            uasort($precedingTestCount, function($a, $b) {
                return $b - $a;
            });

            $limit = 5;
            foreach ($precedingTestCount as $commonTest => $count) {
                $output->writeln(sprintf(
                    '%3d%% %s',
                    $count / $numSamples * 100,
                    $commonTest
                ));

                if (--$limit < 0) {
                    break;
                }
            }
        }
    }

    /**
     * @param array $results
     * @return ResultList[][]
     */
    private function loadRuns(array $results)
    {
        foreach ($results as $resultFile) {
            if (!file_exists($resultFile) || !is_readable($resultFile)) {
                throw new \RuntimeException("Unable to read input file $resultFile");
            }

            yield $this->loadResults(Json::decode(file_get_contents($resultFile)));
        }
    }

    /**
     * @param array
     * @return ResultList[]
     */
    private function loadResults(array $run) {
        foreach ($run as $worker => $results) {
            yield new ResultList($worker, $results);
        }
    }
}
