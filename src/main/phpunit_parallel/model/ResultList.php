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
     * @return Error[] keyed on the RESULT ID, not the error ID. This means there could be multiple
     *    errors with the same id if one test had more then one error.
     */
    public function getErrors()
    {
        foreach ($this->results as $id => $result) {
            if ($result['errors']) {
                foreach ($result['errors'] as $error) {
                    yield $id => new Error($error);
                }
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
            yield new TestResult($this->results[$i]);
        }
    }
}
