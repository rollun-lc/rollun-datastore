<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\TokenParser\GroupbyTokenParser;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;

class GroupByTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new GroupByTokenParser();
    }

    public function dataProvider()
    {
        return [
            [
                ['a'],
                ['a', 'b'],
                ['a', 'b', 'c'],
            ]
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param array $fields
     */
    public function testParse($fields)
    {
        $node = new GroupbyNode($fields);
        $tokenStream = $this->getMockTokenStream($fields);
        $this->assertEquals($this->createObject()->parse($tokenStream), $node);
    }

    public function testSupportTrue()
    {
        /** @var TokenStream|PHPUnit_Framework_MockObject_MockObject $tokenStream */
        $tokenStream = $this->getMockBuilder(TokenStream::class)
            ->disableOriginalConstructor()
            ->setMethods(['test'])
            ->getMock();

        $tokenStream->expects($this->once())->method('test')->will($this->returnValue(true));

        $tokenParser = $this->createObject();
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

        $tokenParser = $this->createObject();
        $this->assertFalse($tokenParser->supports($tokenStream));
    }

    /**
     * @param array $fields
     * @return PHPUnit_Framework_MockObject_MockObject|TokenStream
     */
    protected function getMockTokenStream($fields)
    {
        $position = 0;

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
            ->with($this->equalTo(Token::T_OPERATOR), $this->equalTo('groupby'))
            ->will($this->returnValue(new Token(Token::T_OPERATOR, '', 0)));

        $this->insertSimpleToken($tokenStream, Token::T_OPEN_PARENTHESIS, $position++);

        foreach ($fields as $field) {
            $fieldTokens[] = new Token(Token::T_STRING, $field, 0);
        }

        while (count($fieldTokens)) {
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

        return $tokenStream;
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
