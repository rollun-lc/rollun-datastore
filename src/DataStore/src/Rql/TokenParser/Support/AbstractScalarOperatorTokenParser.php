<?php
namespace rollun\datastore\Rql\TokenParser\Support;

use Graviton\RqlParser\AbstractNode;
use Graviton\RqlParser\NodeParserInterface;
use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;

abstract class AbstractScalarOperatorTokenParser implements NodeParserInterface
{
    abstract protected function getOperatorName();
    /** @return AbstractNode */
    abstract protected function createNode(string $field, $value);

    public function supports(TokenStream $ts): bool
    {
        return $ts->test(Token::T_OPERATOR, $this->getOperatorName());
    }

    /** @return AbstractNode */
    public function parse(TokenStream $ts)
    {
        $ts->expect(Token::T_OPERATOR, $this->getOperatorName());
        $ts->expect(Token::T_OPEN_PARENTHESIS);

        $field = $ts->expect(Token::T_STRING)->getValue();
        $ts->expect(Token::T_COMMA);
        $value = $ts->expect(Token::T_STRING)->getValue(); // как и в твоём коде

        $ts->expect(Token::T_CLOSE_PARENTHESIS);

        return $this->createNode($field, $value);
    }
}
