<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser\Basic\BinaryOperator;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqtNode;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqtTokenParser;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;

class EqtTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new EqtTokenParser();
    }

    public function testParse()
    {
        $field = 'a';

        $tokenStream = new TokenStream([
            new Token(Token::T_OPERATOR, 'eqt', 0),
            new Token(Token::T_OPEN_PARENTHESIS, '(', 1),
            new Token(Token::T_STRING, $field, 2),
            new Token(Token::T_CLOSE_PARENTHESIS, ')', 3),
            new Token(Token::T_END, '', 4),
        ]);

        $node = new EqtNode($field);
        $this->assertEquals($this->createObject()->parse($tokenStream), $node);
    }
}
