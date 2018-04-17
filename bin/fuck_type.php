<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 17.04.18
 * Time: 3:41 PM
 */

function test() {
    $fieldValue = 1.5;

    if(is_string($fieldValue) && is_integer($fieldValue)){
        $fieldValue.= "string int\t";
    }
    if(!is_string($fieldValue) && is_integer($fieldValue)){
        $fieldValue.= "only int\t";
    }
    return $fieldValue;
}

echo test();