<?php

namespace phpunit_parallel\model;

/**
 * Sent from the worker back to the main process for display
 */
class TestResult extends Message
{
    const PREAMBLE = '!!(╯°□°）╯︵ ┻━┻!!';

    private $testId;
    private $class;
    private $name;
    private $filename;
    private $elapsed;
    private $errors;
    private $incomplete;
    private $skipped;
    private $risky;

    /**
     * @param int $testId
     * @param string $class
     * @param string $name
     * @param string $filename
     * @param float $elapsed
     * @param Error[] $errors
     * @param boolean $incomplete
     * @param boolean $skipped
     * @param boolean $risky
     */
    public function __construct($testId, $class, $name, $filename, $elapsed, array $errors = [], $incomplete = false, $skipped = false, $risky = false)
    {
        foreach (['class' => $class, 'name' => $name, 'filename' => $filename] as $argName => $arg) {
            if (!is_string($arg)) {
                throw new \RuntimeException("$argName must be a string");
            }
        }

        foreach (['incomplete' => $incomplete, 'skipped' => $skipped, 'risky' => $risky] as $argName => $arg) {
            if (!is_bool($arg)) {
                throw new \RuntimeException("$argName must be a bool");
            }
        }

        if (!is_int($testId)) {
            throw new \RuntimeException('$testId must be an int');
        }

        $this->testId = $testId;
        $this->class = $class;
        $this->name = $name;
        $this->filename = $filename;
        $this->elapsed = $elapsed;
        $this->errors = $errors;
        $this->incomplete = $incomplete;
        $this->skipped = $skipped;
        $this->risky = $risky;
    }

    public static function fromArray(array $data)
    {
        if (count($data) !== 9) {
            throw new \RuntimeException("Garbage received from worker: " . print_r($data, true));
        }

        list($testId, $class, $name, $filename, $elapsed, $errors, $incomplete, $skipped, $risky) = $data;

        $errors = array_map(function($error) {
            return Error::fromArray($error);
        }, $errors);

        return new TestResult($testId, $class, $name, $filename, $elapsed, $errors, $incomplete, $skipped, $risky);
    }

    public function toArray()
    {
        return [
            $this->testId,
            $this->class,
            $this->name,
            $this->filename,
            $this->elapsed,
            $this->errors,
            $this->incomplete,
            $this->skipped,
            $this->risky,
        ];
    }

    public function addError(Error $error)
    {
        $this->errors[] = $error;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function getElapsed()
    {
        return $this->elapsed;
    }

    /**
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function getIncomplete()
    {
        return $this->incomplete;
    }

    /**
     * @return bool
     */
    public function getSkipped()
    {
        return $this->skipped;
    }

    /**
     * @return bool
     */
    public function getRisky()
    {
        return $this->risky;
    }
}
