<?php

namespace phpunit_parallel\dummy;

class DataProviderTest extends \PHPUnit_Framework_TestCase
{

    public static function provider()
    {
        $tests = [];

        for ($i = 0; $i < 70; $i++) {
            $tests[] = ['a'];
        }

        return $tests;
    }

    /**
     * @dataProvider provider
     */
    public function testNothing()
    {
        // Yeah this test does nothing.
    }
}
