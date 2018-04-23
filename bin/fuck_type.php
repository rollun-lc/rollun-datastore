<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 17.04.18
 * Time: 3:41 PM
 */

function test(&$testParams) {
    $testParams = "new data";
}

$testParams = "data";
test($testParams);
echo $testParams;
