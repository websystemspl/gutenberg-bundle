<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use Psr\Log\LoggerInterface;
use WebSystems\GutenbergBundle\Block\Block;
use WebSystems\GutenbergBundle\Parser\BlockParser;

/**
 * Composite renderer: parses a document once, then walks the tree delegating every block to
 * the first strategy that supports it.
 *
 * A depth guard bounds the recursion. Dynamic blocks may render other documents (a list block
 * showing excerpts of other pages, a reusable block referencing another one), and those
 * documents may in turn contain the same block, so cycles are reachable through content alone
 * and must be stopped here rather than by every block author.
 */
final class ContentRenderer implements ContentRendererInterface
{
    private int $depth = 0;

    /**
     * @param iterable<BlockRendererInterface> $renderers priority-ordered
     * @param int                              $maxDepth  how many nested render passes are allowed
     */
    public function __construct(
        private readonly BlockParser $parser,
        private readonly iterable $renderers,
        private readonly int $maxDepth = 10,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function render(?string $content, ?RenderOptions $options = null): string
    {
        return $this->renderBlocks($this->parser->parse($content), $options);
    }

    public function renderBlocks(iterable $blocks, ?RenderOptions $options = null): string
    {
        $options ??= new RenderOptions();
        $html = '';

        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block, $options);
        }

        return $html;
    }

    public function renderBlock(Block $block, ?RenderOptions $options = null): string
    {
        $options ??= new RenderOptions();

        if ($this->depth >= $this->maxDepth) {
            $this->logger?->warning('Gutenberg rendering stopped at the maximum depth of {depth}; block "{block}" was skipped. This usually means the content references itself.', [
                'depth' => $this->maxDepth,
                'block' => $block->name ?? 'freeform',
            ]);

            return '';
        }

        ++$this->depth;

        try {
            foreach ($this->renderers as $renderer) {
                if ($renderer->supports($block)) {
                    return $renderer->render($block, $this, $options);
                }
            }

            return '';
        } finally {
            --$this->depth;
        }
    }
}
