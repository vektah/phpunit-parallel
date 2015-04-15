<?php

namespace phpunit_parallel\phpunit\dummy;

if (getenv('PHPUNIT_PARALLEL') == 'master') {
    echo "--master--\n";
} elseif (getenv('PHPUNIT_PARALLEL') == 'worker') {
    echo '--worker' . getenv('PHPUNIT_PARALLEL_WORKER_ID') . "--\n";
} else {
    echo "ERROR - Unexpected PHPUNIT_PARALLEL='" .  getenv('PHPUNIT_PARALLEL') . "'\n";
}
