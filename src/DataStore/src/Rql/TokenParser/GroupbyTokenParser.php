<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\TokenParser;

use rollun\datastore\Rql\Node\GroupbyNode;
use Graviton\RqlParser\AbstractNode;
use Graviton\RqlParser\AbstractTokenParser;
use Graviton\RqlParser\Exception\SyntaxErrorException;
use Graviton\RqlParser\Token;
use Graviton\RqlParser\TokenStream;

class GroupbyTokenParser extends AbstractTokenParser
{
    /**
     * @param TokenStream $tokenStream
     * @return AbstractNode
     * @throws SyntaxErrorException
     */
    public function parse(TokenStream $tokenStream)
    {
        $fields = [];

        $tokenStream->expect(Token::T_OPERATOR, 'groupby');
        $tokenStream->expect(Token::T_OPEN_PARENTHESIS);

        do {
            $fields[] = $tokenStream->expect(Token::T_STRING)->getValue();
            if (!$tokenStream->nextIf(Token::T_COMMA)) {
                break ;
            }
        } while (true);

        $tokenStream->expect(Token::T_CLOSE_PARENTHESIS);

        return new GroupbyNode($fields);
    }

    /**
     * @param TokenStream $tokenStream
     * @return bool
     */
    public function supports(TokenStream $tokenStream)
    {
        return $tokenStream->test(Token::T_OPERATOR, 'groupby');
    }
}
