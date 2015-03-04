<?php

namespace phpunit_parallel\model;

class TestResultTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeDecode()
    {
        $request = new TestResult([
            'testId' =>1,
            'class' => 'a',
            'name' => 'b',
            'filename' => 'c',
            'elapsed' => 0.1,
            'memoryUsed' => 100,
            'errors' => [new Error()],
            'risky' => true,
            'incomplete' => true,
            'skipped' => true
        ]);

        $recodedRequest = TestResult::decode($request->encode());

        $this->assertEquals($recodedRequest, $request);
    }

    public function testCreateFromRequest()
    {
        $request = new TestRequest(1, 'a', 'b', 'c');

        $result = TestResult::errorFromRequest($request, 'asdf');

        $this->assertEquals(1, $result->getId());
    }
}
