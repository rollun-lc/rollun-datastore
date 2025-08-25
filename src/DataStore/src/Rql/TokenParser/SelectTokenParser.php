<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser;

use Graviton\RqlParser\NodeParserInterface;
use Graviton\RqlParser\AbstractNode;
use Graviton\RqlParser\Exception\SyntaxErrorException;
use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;
use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\AggregateSelectNode;

//class SelectTokenParser extends AbstractTokenParser
class SelectTokenParser implements NodeParserInterface
{
    public function __construct(private array $allowedFunctions) {}

    /**
     * @param TokenStream $tokenStream
     * @return AbstractNode
     * @throws SyntaxErrorException
     */
    public function parse(TokenStream $tokenStream)
    {
        $fields = [];

        $tokenStream->expect(Token::T_OPERATOR, 'select');
        $tokenStream->expect(Token::T_OPEN_PARENTHESIS);

        do {
            if (($aggregate = $tokenStream->nextIf(Token::T_OPERATOR, $this->allowedFunctions)) !== null) {
                $tokenStream->expect(Token::T_OPEN_PARENTHESIS);

                $fields[] = new AggregateFunctionNode(
                    $aggregate->getValue(),
                    $tokenStream->expect(Token::T_STRING)->getValue()
                );

                $tokenStream->expect(Token::T_CLOSE_PARENTHESIS);
            } else {
                $fields[] = $tokenStream->expect(Token::T_STRING)->getValue();
            }

            if (!$tokenStream->nextIf(Token::T_COMMA)) {
                break;
            }
        } while (true);

        $tokenStream->expect(Token::T_CLOSE_PARENTHESIS);

        return new AggregateSelectNode($fields);
    }

    /**
     * @param TokenStream $tokenStream
     * @return bool
     */
    public function supports(TokenStream $tokenStream)
    {
        return $tokenStream->test(Token::T_OPERATOR, 'select');
    }
}
