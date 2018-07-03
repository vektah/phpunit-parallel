# ABANDONED - phpunit-parallel [![Build Status](https://travis-ci.org/Vektah/phpunit-parallel.svg)](https://travis-ci.org/Vektah/phpunit-parallel)
Another Concurrent Test Executor for PHPUnit

I'm no longer maintaining this project, it is here as an archive only.

For an alternative, check out [fasttest](https://github.com/liuggio/fastest)

How does it work?
=================
phpunit-parallel uses react-php based even loop to talk to a number of worker processes. It communicates with these processes over a unix pipe (fd=3). STDOUT is passed through and STDERR causes tests to error. If a process dies you will know which test was running on that process when it dies.

Get it!
=======
Add into your projects `composer.json`
```
    "require-dev": {
        "vektah/phpunit-parallel": "~1.0"
    },

```
and `composer update vektah/phpunit-parallel`

Run it!
=======
By default no options are needed, phpunit-parallel will pick up your phpunit.xml and find your tests. Simply run:
```
./vendor/bin/phpunit-parallel
```
If your config is in a weird place `-c path/to/phpunit.xml`  should fix it.

You can also specify a list of test files to run:
```
./vendor/bin/phpunit-parallel src/tests/AllTheThingsTest.php
```

Still lost? Get help!
```
./vendor/bin/phpunit-parallel --help
```

Format it!
=========
The default `lane` formatter looks like this when running paratests own test suite.
```
phpunit-parallel % ./bin/phpunit-parallel
| | |✓| | |   1%      0ms    0.0MB  DataProviderTest::testNothing with data set #2
... snip ...
| | |✓| | |  78%      0ms    0.0MB  DataProviderTest::testNothing with data set #69
| | | |✓| |  79%     11ms    0.5MB  WorkerTestExecutorTest::testStartTestSendsNextTest
| | |✓| | |  80%      6ms    0.5MB  WorkerTestExecutorTest::testStdErrWhileExecutingTestSendsOnlyOneResult
|✓| | | | |  81%     10ms    0.6MB  WorkerTestExecutorTest::testCompletingTestSendsResultToDistributor
|✓| | | | |  82%      0ms    0.1MB  WorkerTestExecutorTest::testProcessSendsUnexpectedResult
| | |✓| | |  83%      1ms    0.1MB  WorkerTestExecutorTest::testProcessCrashWithoutStderr
| | | |✓| |  84%      1ms    0.1MB  WorkerTestExecutorTest::testProcessCrashWithActiveTest
|✓| | | | |  85%      2ms    0.2MB  TestRequestTest::testEncodeDecode
| | | |✓| |  86%      0ms    0.0MB  TestResultTest::testCreateFromRequest
| | |✓| | |  87%      3ms    0.2MB  TestResultTest::testEncodeDecode
|✓| | | | |  89%      0ms    0.1MB  BufferedReaderTest::testWaitingLines
|✓| | | | |  90%      0ms    0.0MB  BufferedReaderTest::testMultipleCallsForOneLineWaiting
| | | |✓| |  91%      2ms    0.1MB  BufferedReaderTest::testPendingPromises
| | |✓| | |  92%      2ms    0.1MB  BufferedReaderTest::testMixedWaitingAndPromises
|✓| | | | |  93%      0ms    0.1MB  BufferedReaderTest::testMultipleCallsForOneLinePending
| | | |✓| |  94%      0ms    0.0MB  BufferedReaderTest::testMultipleLinesInOneCallWaiting
|✓| | | | |  95%      0ms    0.0MB  BufferedReaderTest::testMultipleLinesSplitAcrossWaitingPending
| | |✓| | |  96%      0ms    0.0MB  BufferedReaderTest::testMultipleLinesInOneCallPending
| | | |✓| |  97%      0ms    0.0MB  BufferedReaderTest::testPendingThenWaiting
| | | | |✓|  98%   1000ms    0.0MB  SleepTest::testSleeps with data set #0
| |✓| | | | 100%   1000ms    0.0MB  SleepTest::testSleeps with data set #1
-----------


Time: 1.31 seconds, Memory: 4.62Mb

OK (91 tests)
Most expensive tests by memory usage are:
   0.6MB WorkerTestExecutorTest::testCompletingTestSendsResultToDistributor
   0.5MB WorkerTestExecutorTest::testStdErrWhileExecutingTestSendsOnlyOneResult
   0.5MB WorkerTestExecutorTest::testStartTestSendsNextTest
   0.2MB TestResultTest::testEncodeDecode
   0.2MB TestRequestTest::testEncodeDecode

Most expensive tests by duration are:
  1000ms SleepTest::testSleeps with data set #1
  1000ms SleepTest::testSleeps with data set #0
    11ms WorkerTestExecutorTest::testStartTestSendsNextTest
    10ms WorkerTestExecutorTest::testCompletingTestSendsResultToDistributor
     6ms WorkerTestExecutorTest::testStdErrWhileExecutingTestSendsOnlyOneResult

```
What is this madness? It draws a vertical lane for each worker, and on completion of a test it marks the result in that workers column. This can be useful if a test is modifying global/static state and causes another test to break. See a failure? Look up the lane and see which tests ran before.

There is also `xunit` for phpunit style output:
```
phpunit-parallel % ./bin/phpunit-parallel --formatter xunit
PHPUnit Parallel 1.0.0

...............................................................   63 /    91 ( 69%)
............................

Time: 1.31 seconds, Memory: 4.62Mb

OK (91 tests)
Most expensive tests by memory usage are:
   0.6MB WorkerTestExecutorTest::testCompletingTestSendsResultToDistributor
   0.5MB WorkerTestExecutorTest::testStdErrWhileExecutingTestSendsOnlyOneResult
   0.5MB WorkerTestExecutorTest::testStartTestSendsNextTest
   0.2MB TestResultTest::testEncodeDecode
   0.2MB TestRequestTest::testEncodeDecode

Most expensive tests by duration are:
  1000ms SleepTest::testSleeps with data set #0
  1000ms SleepTest::testSleeps with data set #1
    10ms WorkerTestExecutorTest::testStdErrWhileExecutingTestSendsOnlyOneResult
    10ms WorkerTestExecutorTest::testStartTestSendsNextTest
     7ms WorkerTestExecutorTest::testCompletingTestSendsResultToDistributor

```

or `tap`
```
phpunit-parallel % ./bin/phpunit-parallel --formatter tap
TAP version 13
1..91
ok 1 - phpunit_parallel\dummy\DataProviderTest::testNothing with data set #0
... snip ...
ok 67 - phpunit_parallel\dummy\DataProviderTest::testNothing with data set #66
 ok 71 - phpunit_parallel\dummy\OutputTest::testEcho
ok 74 - phpunit_parallel\ipc\WorkerTestExecutorTest\WorkerTestExecutorTest::testStartTestSendsNextTest
ok 75 - phpunit_parallel\ipc\WorkerTestExecutorTest\WorkerTestExecutorTest::testCompletingTestSendsResultToDistributor
ok 76 - phpunit_parallel\ipc\WorkerTestExecutorTest\WorkerTestExecutorTest::testStdErrWhileExecutingTestSendsOnlyOneResult
ok 77 - phpunit_parallel\ipc\WorkerTestExecutorTest\WorkerTestExecutorTest::testProcessCrashWithActiveTest
ok 78 - phpunit_parallel\ipc\WorkerTestExecutorTest\WorkerTestExecutorTest::testProcessCrashWithoutStderr
ok 79 - phpunit_parallel\ipc\WorkerTestExecutorTest\WorkerTestExecutorTest::testProcessSendsUnexpectedResult
ok 80 - phpunit_parallel\model\TestRequestTest::testEncodeDecode
ok 81 - phpunit_parallel\model\TestResultTest::testEncodeDecode
ok 82 - phpunit_parallel\model\TestResultTest::testCreateFromRequest
ok 84 - phpunit_parallel\stream\BufferedReaderTest::testPendingPromises
ok 83 - phpunit_parallel\stream\BufferedReaderTest::testWaitingLines
ok 86 - phpunit_parallel\stream\BufferedReaderTest::testMultipleCallsForOneLineWaiting
ok 85 - phpunit_parallel\stream\BufferedReaderTest::testMixedWaitingAndPromises
ok 87 - phpunit_parallel\stream\BufferedReaderTest::testMultipleCallsForOneLinePending
ok 88 - phpunit_parallel\stream\BufferedReaderTest::testMultipleLinesInOneCallWaiting
ok 91 - phpunit_parallel\stream\BufferedReaderTest::testPendingThenWaiting
ok 90 - phpunit_parallel\stream\BufferedReaderTest::testMultipleLinesSplitAcrossWaitingPending
ok 89 - phpunit_parallel\stream\BufferedReaderTest::testMultipleLinesInOneCallPending
ok 72 - phpunit_parallel\dummy\SleepTest::testSleeps with data set #0
ok 73 - phpunit_parallel\dummy\SleepTest::testSleeps with data set #1
```
