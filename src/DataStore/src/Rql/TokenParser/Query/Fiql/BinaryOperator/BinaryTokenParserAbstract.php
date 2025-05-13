<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser\Query\Fiql\BinaryOperator;

use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenParser\Query\AbstractFiqlTokenParser;
use Xiag\Rql\Parser\TokenStream;

abstract class BinaryTokenParserAbstract extends AbstractFiqlTokenParser
{
    abstract protected function createNode(string $field);

    public function parse(TokenStream $tokenStream)
    {
        $tokenStream->expect(Token::T_OPERATOR, $this->getOperatorNames());
        $tokenStream->expect(Token::T_OPEN_PARENTHESIS);

        $field = $tokenStream->expect(Token::T_STRING)->getValue();
        $tokenStream->expect(Token::T_CLOSE_PARENTHESIS);

        return $this->createNode($field);
    }
}
