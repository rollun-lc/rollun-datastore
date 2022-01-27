<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser\Basic\ScalarOperator;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\Rql\Node\AlikeNode;
use rollun\datastore\Rql\TokenParser\Query\Basic\ScalarOperator\AlikeTokenParser;
use Graviton\RqlParser\Parser\ExpressionParser;
use Graviton\RqlParser\Parser\Parser;
use Graviton\RqlParser\Parser\Token;
use Graviton\RqlParser\Parser\TokenStream;

class AlikeTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new AlikeTokenParser();
    }

    public function testParse()
    {
        $field = 'a';
        $value = 'b';
        $position = 0;

        /** @var AlikeTokenParser|PHPUnit_Framework_MockObject_MockObject $object */
        $object = $this->getMockBuilder(get_class($this->createObject()))->setMethods(['getParser'])->getMock();
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
            ->with($this->equalTo(Token::T_OPERATOR), $this->equalTo('alike'))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, '', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_OPEN_PARENTHESIS, $position++);

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_STRING))
            ->will($this->returnValue(new Token(Token::T_STRING, $field, 0)));

        $this->insertSimpleToken($tokenStream, Token::T_COMMA, $position++);

        $tokenStream
            ->expects($this->at($position++))
            ->method('nextIf')
            ->with($this->equalTo(Token::T_TYPE))
            ->will($this->returnValue(null));

        $tokenStream
            ->expects($this->at($position++))
            ->method('next')
            ->will($this->returnValue(new Token(Token::T_STRING, $value, 0)));

        $this->insertSimpleToken($tokenStream, Token::T_CLOSE_PARENTHESIS, $position);

        $node = new AlikeNode($field, $value);
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
