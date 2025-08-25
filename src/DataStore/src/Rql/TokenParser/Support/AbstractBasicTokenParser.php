<?php
namespace rollun\datastore\Rql\TokenParser\Support;

use Graviton\RqlParser\AbstractNode;
use Graviton\RqlParser\NodeParserInterface;
use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;

abstract class AbstractBasicTokenParser implements NodeParserInterface
{
    abstract protected function getOperatorName();

    /** Совместимость со старым API — можно звать из вашего парсера */
    public function supports(TokenStream $tokenStream): bool
    {
        return $tokenStream->test(Token::T_OPERATOR, $this->getOperatorName());
    }

    /** Потомки реализуют parse(), если базовой логики недостаточно */
    abstract public function parse(TokenStream $tokenStream);
}
