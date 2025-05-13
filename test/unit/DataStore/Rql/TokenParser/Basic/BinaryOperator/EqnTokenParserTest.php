<?php

namespace rollun\test\unit\DataStore\Rql\TokenParser\Basic\BinaryOperator;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\BinaryNode\EqnNode;
use rollun\datastore\Rql\TokenParser\Query\Basic\BinaryOperator\EqnTokenParser;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;

class EqnTokenParserTest extends TestCase
{
    protected function createObject()
    {
        return new EqnTokenParser();
    }
    public function testParse()
    {
        $field = 'a';

        $tokenStream = new TokenStream([
            new Token(Token::T_OPERATOR, 'eqn', 0),
            new Token(Token::T_OPEN_PARENTHESIS, '(', 1),
            new Token(Token::T_STRING, $field, 2),
            new Token(Token::T_CLOSE_PARENTHESIS, ')', 3),
            new Token(Token::T_END, '', 4),
        ]);

        $node = new EqnNode($field);
        $this->assertEquals($this->createObject()->parse($tokenStream), $node);
    }
}
