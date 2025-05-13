<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser\Fiql\ScalarOperator;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\Rql\Node\AlikeGlobNode;
use rollun\datastore\Rql\TokenParser\Query\Fiql\ScalarOperator\AlikeGlobTokenParser;
use Xiag\Rql\Parser\ExpressionParser;
use Xiag\Rql\Parser\Parser;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;

class AlikeGlobTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new AlikeGlobTokenParser();
    }

    public function testParse()
    {
        $field = 'a';
        $value = 'b';
        $position = 0;

        /** @var AlikeGlobTokenParser|PHPUnit_Framework_MockObject_MockObject $object */
        $object = $this->getMockBuilder($this->createObject()::class)->setMethods(['getParser'])->getMock();
        $object->expects($this->once())
            ->method('getParser')
            ->will($this->returnValue(new Parser(new ExpressionParser())));

        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['expect', 'nextIf', 'next'])
            ->getMock();

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_STRING))
            ->will($this->returnValue(new Token(Token::T_STRING, $field, 0)));

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_OPERATOR), $this->equalTo(['alike']))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, '', 0)));

        $tokenStream
            ->expects($this->at($position++))
            ->method('nextIf')
            ->with($this->equalTo(Token::T_TYPE))
            ->will($this->returnValue(null));

        $tokenStream
            ->expects($this->at($position))
            ->method('next')
            ->will($this->returnValue(new Token(Token::T_STRING, $value, 0)));

        $node = new AlikeGlobNode($field, $value);
        $this->assertEquals($object->parse($tokenStream), $node);
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject $tokenStream
     * @param $type
     * @param $position
     */
    protected function insertSimpleToken(&$tokenStream, $type, $position)
    {
        $tokenStream
            ->expects($this->at($position))
            ->method('expect')
            ->with($this->equalTo($type))
            ->will($this->returnValue(new Token($type, '', 0)));
    }
}
