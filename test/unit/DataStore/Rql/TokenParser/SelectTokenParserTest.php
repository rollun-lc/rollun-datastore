<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser;

use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;
use rollun\datastore\Rql\TokenParser\SelectTokenParser;

class SelectTokenParserTest extends TestCase
{
    protected function createObject(array $allowedFunctions)
    {
        return new SelectTokenParser($allowedFunctions);
    }

    public function testSupportTrue()
    {
        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();

        $tokenStream->expects($this->once())->method('test')->will($this->returnValue(true));

        $tokenParser = $this->createObject([]);
        $this->assertTrue($tokenParser->supports($tokenStream));
    }

    public function testSupportFalse()
    {
        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();

        $tokenStream->expects($this->once())->method('test')->will($this->returnValue(false));

        $tokenParser = $this->createObject([]);
        $this->assertFalse($tokenParser->supports($tokenStream));
    }

    public function testParseWithAggregateFunction()
    {
        $position = 0;

        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['expect', 'nextIf'])
            ->getMock();

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_OPERATOR), $this->equalTo('select'))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, '', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_OPEN_PARENTHESIS, $position++);

        $tokenStream
            ->expects($this->at($position++))
            ->method('nextIf')
            ->with($this->equalTo(Token::T_OPERATOR))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, 'sum', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_OPEN_PARENTHESIS, $position++);

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_STRING))
            ->will($this->returnValue(new Token(Token::T_STRING, 'a', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_CLOSE_PARENTHESIS, $position++);

        $tokenStream
            ->expects($this->at($position++))
            ->method('nextIf')
            ->with($this->equalTo(Token::T_COMMA))
            ->will($this->returnValue(false));

        $this->insertSimpleToken($tokenStream, Token::T_CLOSE_PARENTHESIS, $position);

        $node = new AggregateSelectNode([new AggregateFunctionNode('sum', 'a'),]);
        $this->assertEquals($this->createObject([])->parse($tokenStream), $node);
    }

    public function testSimpleParse()
    {
        $position = 0;
        $fields = ['a', 'b', 'c'];

        /** @var Token[] $fieldTokens */
        $fieldTokens = [];

        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['expect', 'nextIf'])
            ->getMock();

        $tokenStream
            ->expects($this->at($position++))
            ->method('expect')
            ->with($this->equalTo(Token::T_OPERATOR), $this->equalTo('select'))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, '', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_OPEN_PARENTHESIS, $position++);

        foreach ($fields as $field) {
            $fieldTokens[] = new Token(Token::T_STRING, $field, 0);
        }

        while (count($fieldTokens)) {
            $tokenStream
                ->expects($this->at($position++))
                ->method('nextIf')
                ->with($this->equalTo(Token::T_OPERATOR))
                ->will($this->returnValue(null));

            $fieldToken = array_shift($fieldTokens);
            $tokenStream
                ->expects($this->at($position++))
                ->method('expect')
                ->with($this->equalTo($fieldToken->getType()))
                ->will($this->returnValue($fieldToken));

            $tokenStream
                ->expects($this->at($position++))
                ->method('nextIf')
                ->with($this->equalTo(Token::T_COMMA))
                ->will($this->returnValue(count($fieldTokens)));
        }

        $this->insertSimpleToken($tokenStream, Token::T_CLOSE_PARENTHESIS, $position);

        $node = new AggregateSelectNode($fields);
        $this->assertEquals($this->createObject([])->parse($tokenStream), $node);
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
