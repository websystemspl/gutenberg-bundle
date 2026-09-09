<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * Renders classic HTML that carries no block delimiter, typically content imported from a
 * pre-Gutenberg editor.
 */
final class FreeformBlockRenderer implements BlockRendererInterface
{
    public function supports(Block $block): bool
    {
        return $block->isFreeform();
    }

    public function render(Block $block, ContentRendererInterface $renderer, RenderOptions $options): string
    {
        return $block->innerHtml;
    }
}
