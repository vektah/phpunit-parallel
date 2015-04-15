<?php

namespace phpunit_parallel\system;

use phpunit_parallel\test\EndToEndTestCase;

class BootstrapTest extends EndToEndTestCase
{
    public function testBootstrap()
    {
        list($stdout, $stderr) = $this->runTestProcess(__DIR__ . '/../dummy/DummyTest.php --formatter noiseless --bootstrap ' . __DIR__ . '/../dummy/bootstrap.php');

        $this->assertContains('--master--', $stdout);
        $this->assertContains('--worker0--', $stdout);
        $this->assertNotContains('ERROR', $stdout);
    }
}
