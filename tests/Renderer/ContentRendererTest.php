<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use WebSystems\GutenbergBundle\Block\Block;
use WebSystems\GutenbergBundle\Block\BlockTypeInterface;
use WebSystems\GutenbergBundle\Block\Definition\AttributeBuilder;
use WebSystems\GutenbergBundle\Parser\BlockParser;
use WebSystems\GutenbergBundle\Registry\BlockTypeMetadata;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistryInterface;
use WebSystems\GutenbergBundle\Renderer\ContentRenderer;
use WebSystems\GutenbergBundle\Renderer\ContentRendererInterface;
use WebSystems\GutenbergBundle\Renderer\DynamicBlockRenderer;
use WebSystems\GutenbergBundle\Renderer\FreeformBlockRenderer;
use WebSystems\GutenbergBundle\Renderer\RenderOptions;
use WebSystems\GutenbergBundle\Renderer\StaticBlockRenderer;
use WebSystems\GutenbergBundle\Renderer\Context\BlockRenderContext;

final class ContentRendererTest extends TestCase
{
    public function testStaticBlocksEmitTheirSavedMarkup(): void
    {
        $renderer = $this->createRenderer();

        self::assertSame(
            '<p>Hi</p>',
            $renderer->render('<!-- wp:paragraph --><p>Hi</p><!-- /wp:paragraph -->')
        );
    }

    public function testNestedBlocksAreStitchedBackInPlace(): void
    {
        $renderer = $this->createRenderer();
        $html = $renderer->render(
            '<!-- wp:columns --><div class="cols"><!-- wp:column --><span>A</span><!-- /wp:column --></div><!-- /wp:columns -->'
        );

        self::assertSame('<div class="cols"><span>A</span></div>', $html);
    }

    public function testFreeformHtmlIsPassedThrough(): void
    {
        self::assertSame('<p>Legacy</p>', $this->createRenderer()->render('<p>Legacy</p>'));
    }

    public function testUnknownBlockFallsBackToItsSavedMarkup(): void
    {
        self::assertSame(
            '<p>from an uninstalled plugin</p>',
            $this->createRenderer()->render('<!-- wp:acme/widget --><p>from an uninstalled plugin</p><!-- /wp:acme/widget -->')
        );
    }

    public function testDynamicBlockIsRenderedByItsBlockType(): void
    {
        $renderer = $this->createRenderer($this->registryWithGreeting());

        self::assertSame(
            '<em>Hello Ada</em>',
            $renderer->render('<!-- wp:test/greeting {"name":"Ada"} /-->')
        );
    }

    public function testDeclaredDefaultsAreAppliedToMissingAttributes(): void
    {
        $renderer = $this->createRenderer($this->registryWithGreeting());

        self::assertSame('<em>Hello world</em>', $renderer->render('<!-- wp:test/greeting /-->'));
    }

    public function testRenderContextReachesTheBlock(): void
    {
        $renderer = $this->createRenderer($this->registryWithGreeting());
        $html = $renderer->render('<!-- wp:test/greeting /-->', new RenderOptions(false, ['suffix' => '!']));

        self::assertSame('<em>Hello world!</em>', $html);
    }

    public function testSelfReferencingContentStopsAtTheDepthLimit(): void
    {
        $registry = $this->createStub(BlockTypeRegistryInterface::class);
        $registry->method('has')->willReturnCallback(static fn (string $name): bool => 'test/loop' === $name);
        $registry->method('getMetadata')->willReturn(new BlockTypeMetadata('test/loop', 'Loop', 'block-default', 'widgets', null, [], [], false, [], []));

        $calls = 0;
        $renderer = null;
        $blockType = new class($calls, $renderer) implements BlockTypeInterface {
            public function __construct(private int &$calls, private ?ContentRendererInterface &$renderer)
            {
            }

            public function configureAttributes(AttributeBuilder $builder): void
            {
            }

            public function render(BlockRenderContext $context): string
            {
                ++$this->calls;

                // A block that renders a document containing itself: reachable through content alone.
                return (string) $this->renderer?->render('<!-- wp:test/loop /-->');
            }
        };
        $registry->method('get')->willReturn($blockType);

        $renderer = $this->createRenderer($registry, maxDepth: 4);
        $renderer->render('<!-- wp:test/loop /-->');

        self::assertSame(4, $calls, 'Rendering must stop once the depth limit is reached.');
    }

    private function registryWithGreeting(): BlockTypeRegistryInterface
    {
        $builder = new AttributeBuilder();
        $builder->text('name', 'Name', 'world');

        $metadata = new BlockTypeMetadata('test/greeting', 'Greeting', 'block-default', 'widgets', null, [], [], false, [], $builder->all());

        $registry = $this->createStub(BlockTypeRegistryInterface::class);
        $registry->method('has')->willReturnCallback(static fn (string $name): bool => 'test/greeting' === $name);
        $registry->method('getMetadata')->willReturn($metadata);
        $registry->method('get')->willReturn(new class implements BlockTypeInterface {
            public function configureAttributes(AttributeBuilder $builder): void
            {
                $builder->text('name', 'Name', 'world');
            }

            public function render(BlockRenderContext $context): string
            {
                return sprintf('<em>Hello %s%s</em>', $context->get('name'), $context->context('suffix', ''));
            }
        });

        return $registry;
    }

    private function createRenderer(?BlockTypeRegistryInterface $registry = null, int $maxDepth = 10): ContentRenderer
    {
        $registry ??= $this->createStub(BlockTypeRegistryInterface::class);

        return new ContentRenderer(
            new BlockParser(),
            [
                new DynamicBlockRenderer($registry),
                new FreeformBlockRenderer(),
                new StaticBlockRenderer(),
            ],
            $maxDepth,
        );
    }
}
