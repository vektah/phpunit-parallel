<?php

namespace phpunit_parallel\model;


/**
 * Sent by the main process to the worker
 */
class TestRequest extends Message
{
    const PREAMBLE = '!!¯\_(ツ)_/¯!!';

    private $testId;
    private $class;
    private $filename;
    private $name;

    public function __construct($testId, $class, $filename, $name)
    {
        if (!is_int($testId)) {
            throw new \RuntimeException('$testId must be an int');
        }
        if (!is_string($class)) {
            throw new  \RuntimeException('$class must be a classname');
        }
        if (!is_string($filename)) {
            throw new \RuntimeException('$filename must be a string');
        }
        if (!is_string($name)) {
            throw new \RuntimeException('$filter must be a string');
        }

        $this->testId = $testId;
        $this->class = $class;
        $this->filename = $filename;
        $this->name = $name;
    }

    public static function fromArray(array $data)
    {
        if (count($data) !== 4) {
            throw new \RuntimeException("Garbage received from worker: " . print_r($data, true));
        }

        list($testId, $class, $filename, $filter) = $data;
        return new TestRequest($testId, $class, $filename, $filter);
    }

    public function toArray()
    {
        return [$this->testId, $this->class, $this->filename, $this->name];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->testId;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
