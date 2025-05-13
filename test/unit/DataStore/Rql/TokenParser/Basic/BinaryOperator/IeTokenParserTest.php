<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser\Basic\BinaryOperator;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\IeNode;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\IeTokenParser;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;

class IeTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new IeTokenParser();
    }
    public function testParse()
    {
        $field = 'a';

        $tokenStream = new TokenStream([
            new Token(Token::T_OPERATOR, 'ie', 0),
            new Token(Token::T_OPEN_PARENTHESIS, '(', 1),
            new Token(Token::T_STRING, $field, 2),
            new Token(Token::T_CLOSE_PARENTHESIS, ')', 3),
            new Token(Token::T_END, '', 4),
        ]);

        $node = new IeNode($field);
        $this->assertEquals($this->createObject()->parse($tokenStream), $node);
    }
}
