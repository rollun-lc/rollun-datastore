<?php

namespace rollun\datastore\Rql\TokenParser\Support\Fiql;

use Graviton\RqlParser\NodeParserInterface;
use Graviton\RqlParser\Parser;
use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;

abstract class AbstractFiqlTokenParser implements NodeParserInterface
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @inheritdoc
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }
    /**
     * @return array
     */
    abstract protected function getOperatorNames();

    /**
     * @inheritdoc
     */
    public function supports(TokenStream $tokenStream)
    {
        return $tokenStream->test(Token::T_STRING) &&
            $tokenStream->lookAhead()->test(Token::T_OPERATOR, $this->getOperatorNames());
    }
}