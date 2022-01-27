<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser\Basic\BinaryOperator;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqnTokenParser;
use Graviton\RqlParser\Parser\Token;
use Graviton\RqlParser\Parser\TokenStream;

class EqnTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new EqnTokenParser();
    }
    public function testParse()
    {
        $field = 'a';
        $position = 0;

        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['expect', 'nextIf'])
            ->getMock();

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_OPERATOR), $this->equalTo('eqn'))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, '', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_OPEN_PARENTHESIS, $position++);

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_STRING))
            ->will($this->returnValue(new Token(Token::T_STRING, $field, 0)));

        $this->insertSimpleToken($tokenStream, Token::T_CLOSE_PARENTHESIS, $position);

        $node = new EqnNode($field);
        $this->assertEquals($this->createObject()->parse($tokenStream), $node);
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
