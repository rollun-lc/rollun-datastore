<?php

namespace rollun\datastore\Rql\TokenParser\Support\Fiql;


use Graviton\RqlParser\Node\Query\AbstractScalarOperatorNode;
use Graviton\RqlParser\NodeParserInterface;
use Graviton\RqlParser\Parser;
use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;

abstract class AbstractScalarOperatorTokenParser implements NodeParserInterface
{

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param string $field
     * @param mixed $value
     * @return AbstractScalarOperatorNode
     */
    abstract protected function createNode($field, $value);

    /**
     * @inheritdoc
     */
    public function parse(TokenStream $tokenStream)
    {
        $field = $tokenStream->expect(Token::T_STRING)->getValue();
        $tokenStream->expect(Token::T_OPERATOR, $this->getOperatorNames());
        $value = $this->getParser()->getExpressionParser()->parseScalar($tokenStream);

        return $this->createNode($field, $value);
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
}