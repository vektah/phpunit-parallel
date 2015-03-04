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
    private $elapsed = 0.0;
    private $memoryUsed = 0;
    private $errors = [];
    private $incomplete = false;
    private $skipped = false;
    private $risky = false;

    private static $required = ['testId', 'class', 'name', 'filename'];
    private static $types = [
        'testId' => 'numeric',
        'class' => 'string',
        'name' => 'string',
        'filename' => 'string',
        'elapsed' => 'numeric',
        'memoryUsed' => 'numeric',
        'errors' => 'array',
        'incomplete' => 'boolean',
        'skipped' => 'boolean',
        'risky' => 'boolean',
    ];

    public static function errorFromRequest(TestRequest $request, $message) {
        return new TestResult([
            'testId' => $request->getId(),
            'class' => $request->getClass(),
            'name' => $request->getName(),
            'filename' => $request->getFilename(),
            'errors' => [new Error(['message' => $message])]
        ]);
    }

    public function __construct(array $data = []) {
        foreach (self::$required as $name) {
            if (!isset($data[$name])) {
                throw new \InvalidArgumentException("$name is required");
            }
        }

        foreach ($data as $key => $value) {
            if (!isset(self::$types[$key])) {
                throw new \InvalidArgumentException("Unknown argument $key");
            }

            $expected = self::$types[$key];
            $actual = gettype($value);
            if ($expected === 'numeric') {
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException("$key should be numeric, is $actual");
                }
            } else {
                if ($actual !== $expected) {
                    throw new \InvalidArgumentException("$key should be of type $expected, is $actual");
                }
            }

            $this->$key = $value;
        }
    }

    public static function fromArray(array $data)
    {
        if (isset($data['errors'])) {
            $data['errors'] = array_map(function ($error) {
                return Error::fromArray($error);
            }, $data['errors']);
        }

        return new TestResult($data);
    }

    public function toArray()
    {
        $data = [];
        foreach (self::$types as $key => $value) {
            $data[$key] = $this->$key;
        }

        return $data;
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

    /**
     * @return float seconds
     */
    public function getElapsed()
    {
        return $this->elapsed;
    }

    /**
     * @return int bytes
     */
    public function getMemoryUsed()
    {
        return $this->memoryUsed;
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
