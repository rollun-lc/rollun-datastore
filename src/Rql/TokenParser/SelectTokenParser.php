<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.06.16
 * Time: 10:29
 */

namespace rolluncom\datastore\Rql\TokenParser;

use Xiag\Rql\Parser\AbstractNode;
use Xiag\Rql\Parser\AbstractTokenParser;
use Xiag\Rql\Parser\Exception\SyntaxErrorException;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;
use rolluncom\datastore\Rql\Node\AggregateFunctionNode;
use rolluncom\datastore\Rql\Node\AggregateSelectNode;


class SelectTokenParser extends AbstractTokenParser
{

    private $allowedFunctions;

    public function __construct(array $allowedFunctions)
    {
        $this->allowedFunctions = $allowedFunctions;
    }

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
