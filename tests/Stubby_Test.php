<?php

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/db_helpers.php';

class Stubby_Test extends TestCase
{

    /**
     * @group stubby
     */
    public function test_something() {
        DbHelpers::ensure_using_test_db();
        $this->assertEquals(1,1);
    }

}