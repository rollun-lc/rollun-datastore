<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 14.01.17
 * Time: 10:20 AM
 */

namespace rollun\datastore\Rql\TokenParser;


use rollun\datastore\Rql\Node\GroupByNode;
use Xiag\Rql\Parser\AbstractNode;
use Xiag\Rql\Parser\AbstractTokenParser;
use Xiag\Rql\Parser\Exception\SyntaxErrorException;
use Xiag\Rql\Parser\Token;
use Xiag\Rql\Parser\TokenStream;

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

        return new GroupByNode($fields);
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
