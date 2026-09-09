<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Tests\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebSystems\GutenbergBundle\Parser\BlockParser;
use WebSystems\GutenbergBundle\Parser\BlockSerializer;

final class BlockParserTest extends TestCase
{
    private BlockParser $parser;

    protected function setUp(): void
    {
        $this->parser = new BlockParser();
    }

    public function testEmptyDocumentHasNoBlocks(): void
    {
        self::assertSame([], $this->parser->parse(null));
        self::assertSame([], $this->parser->parse('   '));
    }

    public function testParsesASelfClosingBlockWithAttributes(): void
    {
        $blocks = $this->parser->parse('<!-- wp:app/hero {"title":"Cześć","limit":3} /-->');

        self::assertCount(1, $blocks);
        self::assertSame('app/hero', $blocks[0]->name);
        self::assertSame(['title' => 'Cześć', 'limit' => 3], $blocks[0]->attributes);
        self::assertSame([], $blocks[0]->innerBlocks);
    }

    public function testUnprefixedNamesBelongToCore(): void
    {
        $blocks = $this->parser->parse('<!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph -->');

        self::assertSame('core/paragraph', $blocks[0]->name);
        self::assertSame('<p>Hi</p>', $blocks[0]->innerHtml);
    }

    public function testNestedBlocksRecordTheirPosition(): void
    {
        $blocks = $this->parser->parse(
            '<!-- wp:columns --><div class="wp-block-columns">'
            .'<!-- wp:column --><div class="wp-block-column">A</div><!-- /wp:column -->'
            .'</div><!-- /wp:columns -->'
        );

        self::assertCount(1, $blocks);
        self::assertSame('core/columns', $blocks[0]->name);
        self::assertCount(1, $blocks[0]->innerBlocks);
        self::assertSame('core/column', $blocks[0]->innerBlocks[0]->name);
        self::assertContains(null, $blocks[0]->innerContent, 'A null marks where the child belongs.');
    }

    public function testHtmlWithoutDelimitersBecomesFreeform(): void
    {
        $blocks = $this->parser->parse('<p>Legacy content</p>');

        self::assertCount(1, $blocks);
        self::assertTrue($blocks[0]->isFreeform());
        self::assertSame('<p>Legacy content</p>', $blocks[0]->innerHtml);
    }

    public function testStrayClosingDelimiterIsIgnored(): void
    {
        $blocks = $this->parser->parse('<!-- /wp:paragraph --><!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph -->');

        self::assertCount(1, $blocks);
        self::assertSame('core/paragraph', $blocks[0]->name);
    }

    public function testUnclosedBlockStillYieldsItsContent(): void
    {
        $blocks = $this->parser->parse('<!-- wp:paragraph --><p>Hi</p>');

        self::assertCount(1, $blocks);
        self::assertSame('core/paragraph', $blocks[0]->name);
        self::assertStringContainsString('<p>Hi</p>', $blocks[0]->innerHtml);
    }

    public function testMalformedAttributesDegradeToEmpty(): void
    {
        $blocks = $this->parser->parse('<!-- wp:app/hero {not json} /-->');

        self::assertCount(1, $blocks);
        self::assertSame([], $blocks[0]->attributes);
    }

    public function testWalkVisitsEveryDescendant(): void
    {
        $blocks = $this->parser->parse(
            '<!-- wp:columns --><div><!-- wp:column --><div><!-- wp:paragraph --><p>A</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->'
        );

        $names = [];

        foreach ($blocks[0]->walk() as $block) {
            $names[] = $block->name;
        }

        self::assertSame(['core/columns', 'core/column', 'core/paragraph'], $names);
    }

    #[DataProvider('documents')]
    public function testSerializingAParsedDocumentRestoresIt(string $document): void
    {
        $serializer = new BlockSerializer();

        self::assertSame($document, $serializer->serialize($this->parser->parse($document)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function documents(): iterable
    {
        yield 'single block' => ['<!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph -->'];
        yield 'void block' => ['<!-- wp:app/hero {"title":"Hi"} /-->'];
        yield 'attributes' => ['<!-- wp:heading {"level":3} --><h3>Hi</h3><!-- /wp:heading -->'];
        yield 'nested' => ['<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column">A</div><!-- /wp:column --></div><!-- /wp:columns -->'];
    }
}
