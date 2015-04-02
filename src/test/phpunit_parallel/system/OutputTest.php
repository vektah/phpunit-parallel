<?php

namespace phpunit_parallel\system;

use phpunit_parallel\test\EndToEndTestCase;

class OutputTest extends EndToEndTestCase
{
    public function testAllOutputIsPassedThrough()
    {
        list($stdout, $stderr) = $this->runTestProcess(__DIR__ . '/../dummy/OutputTest.php --formatter noiseless');

        $this->assertContains('[---A---]', $stdout);
        $this->assertContains('[---B---]', $stdout);
    }
}
