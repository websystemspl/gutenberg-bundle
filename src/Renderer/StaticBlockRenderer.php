<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * Fallback strategy for blocks whose markup the editor already saved (core/paragraph,
 * core/heading, core/columns…): the stored HTML *is* the front-end output.
 *
 * Nested blocks are stitched back in at the positions recorded in `innerContent`.
 */
final class StaticBlockRenderer implements BlockRendererInterface
{
    public function supports(Block $block): bool
    {
        return true;
    }

    public function render(Block $block, ContentRendererInterface $renderer, RenderOptions $options): string
    {
        if ([] === $block->innerContent) {
            return $block->innerHtml;
        }

        $html = '';
        $index = 0;

        foreach ($block->innerContent as $chunk) {
            if (null !== $chunk) {
                $html .= $chunk;

                continue;
            }

            $child = $block->innerBlocks[$index++] ?? null;

            if (null !== $child) {
                $html .= $renderer->renderBlock($child, $options);
            }
        }

        return $html;
    }
}
