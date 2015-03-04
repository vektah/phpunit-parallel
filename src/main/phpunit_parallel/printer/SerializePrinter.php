<?php

namespace phpunit_parallel\printer;

use Exception;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestListener;
use PHPUnit_Framework_TestSuite;
use phpunit_parallel\model\TestRequest;
use phpunit_parallel\model\TestResult;

class SerializePrinter extends \PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener
{
    private static $instance;
    private $errors = [];
    private $failures = [];
    private $incomplete = false;
    private $skipped = false;
    private $risky = false;
    /** @var resource */
    private $fd;
    /** @var TestRequest */
    private $request;
    private $startingMemory;

    public function __construct($out = null)
    {
        parent::__construct($out);
        self::$instance = $this;
        $this->setAutoFlush(true);
        $this->fd = fopen("php://fd/3", "w");
    }

    public function __destruct()
    {
        fclose($this->fd);
    }

    /**
     * @return SerializePrinter
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * A test suite started.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
    }

    /**
     * A test started.
     *
     * @param  PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        $this->errors = [];
        $this->failures = [];
        $this->incomplete = false;
        $this->skipped = false;
        $this->startingMemory = memory_get_usage();
    }

    /**
     * An error occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception $e
     * @param  float $time
     */
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        if ($e instanceof \PHPUnit_Framework_ExpectationFailedException && $e->getComparisonFailure()) {
            $message = $e->getComparisonFailure()->toString();
        } elseif ($e instanceof \PHPUnit_Framework_SelfDescribing) {
            $message = $e->toString();
        } else {
            $message = $e->getMessage();
        }

        $this->errors[] = [
            'class' => get_class($e),
            'message' => $message,
            'filename' => $e->getFile(),
            'line' => $e->getLine(),
            'severity' => 'error',
            'stacktrace' => $e->getTraceAsString(),
        ];
    }

    /**
     * A failure occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  PHPUnit_Framework_AssertionFailedError $e
     * @param  float $time
     */
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $this->addError($test, $e, $time);
    }

    /**
     * Incomplete test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception $e
     * @param  float $time
     */
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->incomplete = true;
    }

    /**
     * Skipped test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception $e
     * @param  float $time
     * @since  Method available since Release 3.0.0
     */
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->skipped = true;
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->risky = true;
    }

    /**
     * A test ended.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  float $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $reflectionClass = new \ReflectionClass($test);

        $result = new TestResult([
            'testId' => $this->request->getId(),
            'class' => get_class($test),
            'name' => $test->getName(),
            'filename' => $reflectionClass->getFileName(),
            'elapsed' => $time,
            'memoryUsed' => memory_get_usage() - $this->startingMemory,
            'errors' => $this->errors,
            'incomplete' => $this->incomplete,
            'skipped' => $this->skipped,
            'risky' => $this->risky
        ]);

        $this->sendError($result);
    }

    /**
     * A test suite ended.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
    }

    public function write($buffer) {
        // Phpunit has a habit of writing junk to the printer. Lets stop that shit right here.
    }

    public function setCurrentRequest(TestRequest $request)
    {
        $this->request = $request;
    }

    public function sendError($result)
    {
        fwrite($this->fd, $result->encode());
    }
}
