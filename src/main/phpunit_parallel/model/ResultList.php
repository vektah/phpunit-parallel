<?php

namespace phpunit_parallel\model;

use ArrayIterator;

class ResultList implements \IteratorAggregate
{
    private $worker;
    private $results;

    /**
     * @param string $worker
     * @param array $results
     */
    public function __construct($worker, $results)
    {
        $this->worker = $worker;
        $this->results = $results;
    }

    public function getWorkerName()
    {
        return $this->worker;
    }

    /**
     * @return TestResult[]|\Generator
     */
    public function getTestsWithErrors()
    {
        foreach ($this->results as $id => $result) {
            if ($result['errors']) {
                yield $id => TestResult::fromArray($result);
            }
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->results);
    }

    /**
     * @param int $resultId
     * @return TestResult[]|\Generator
     */
    public function getAllTestsBefore($resultId)
    {
        if ($resultId >= count($this->results)) {
            throw new \InvalidArgumentException("Requested more tests then were run!");
        }

        for ($i = 0; $i < $resultId; $i++) {
            yield TestResult::fromArray($this->results[$i]);
        }
    }
}
