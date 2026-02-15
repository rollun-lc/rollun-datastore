<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\Rql\TokenParser;

use PHPUnit\Framework\TestCase;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\TokenParser\GroupbyTokenParser;
use Xiag\Rql\Parser\Exception\SyntaxErrorException;
use Xiag\Rql\Parser\Lexer;
use Xiag\Rql\Parser\TokenStream;

class GroupbyTokenParserTest extends TestCase
{
    private function createTokenStream(string $rql): TokenStream
    {
        $lexer = new Lexer();
        return $lexer->tokenize($rql);
    }

    public function testParseSingleField(): void
    {
        $tokenStream = $this->createTokenStream('groupby(category)');
        $parser = new GroupbyTokenParser();

        $this->assertTrue($parser->supports($tokenStream));
        $node = $parser->parse($tokenStream);

        $this->assertInstanceOf(GroupbyNode::class, $node);
        $this->assertSame(['category'], $node->getFields());
    }

    public function testParseMultipleFields(): void
    {
        $tokenStream = $this->createTokenStream('groupby(category,brand,status)');
        $parser = new GroupbyTokenParser();

        $this->assertTrue($parser->supports($tokenStream));
        $node = $parser->parse($tokenStream);

        $this->assertInstanceOf(GroupbyNode::class, $node);
        $this->assertSame(['category', 'brand', 'status'], $node->getFields());
    }

    public function testSupportsReturnsTrueForGroupby(): void
    {
        $tokenStream = $this->createTokenStream('groupby(field)');
        $parser = new GroupbyTokenParser();

        $this->assertTrue($parser->supports($tokenStream));
    }

    public function testSupportsReturnsFalseForOtherOperators(): void
    {
        $tokenStream = $this->createTokenStream('select(field)');
        $parser = new GroupbyTokenParser();

        $this->assertFalse($parser->supports($tokenStream));
    }

    /**
     * Edge case: Field names with underscores.
     */
    public function testParseFieldsWithUnderscores(): void
    {
        $tokenStream = $this->createTokenStream('groupby(user_id,product_category)');
        $parser = new GroupbyTokenParser();

        $node = $parser->parse($tokenStream);

        $this->assertSame(['user_id', 'product_category'], $node->getFields());
    }
}
