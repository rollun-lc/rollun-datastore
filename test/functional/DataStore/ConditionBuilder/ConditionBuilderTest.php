<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\ConditionBuilder;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\ConditionBuilder\ConditionBuilderAbstract;
use Xiag\Rql\Parser\DataType\Glob;

abstract class ConditionBuilderTest extends TestCase
{
    abstract protected function createObject(): ConditionBuilderAbstract;

    abstract public function providerPrepareFieldName();

    abstract public function providerGetValueFromGlob();

    abstract public function providerInvoke();

    /**
     * @dataProvider providerPrepareFieldName
     * @param $in
     * @param $out
     */
    public function testPrepareFieldName($in, $out)
    {
        $fieldName = $this->createObject()
            ->prepareFieldName($in);
        $this->assertEquals($out, $fieldName);
    }

    /**
     * @dataProvider providerGetValueFromGlob
     * @param $in
     * @param $out
     */
    public function testGetValueFromGlob($in, $out)
    {
        $globObject = new Glob($in);
        $value = $this->createObject()
            ->getValueFromGlob($globObject);
        $this->assertEquals($out, $value);
    }

    /**
     * @dataProvider providerInvoke
     * @param $rootQueryNode
     * @param $out
     */
    public function testInvoke($rootQueryNode, $out)
    {
        $condition = $this->createObject()->__invoke($rootQueryNode);
        $this->assertEquals($out, $condition);
    }
}
