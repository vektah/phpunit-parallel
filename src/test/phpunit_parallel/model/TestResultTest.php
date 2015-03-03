<?php

namespace phpunit_parallel\model;

class TestResultTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeDecode()
    {
        $request = new TestResult(1, 'a', 'b', 'c', 0.1, [new Error()], true, true, true);

        $recodedRequest = TestResult::decode($request->encode());

        $this->assertEquals($recodedRequest, $request);
    }
}
