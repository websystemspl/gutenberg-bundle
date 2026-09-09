<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * One rendering strategy for one family of blocks.
 *
 * Strategies form a priority-ordered chain: the first one whose supports() returns true wins.
 * Adding support for a new kind of block therefore means adding a service, never editing
 * an existing renderer.
 */
interface BlockRendererInterface
{
    public function supports(Block $block): bool;

    /**
     * @param ContentRendererInterface $renderer Use it to render nested blocks; never recurse manually
     */
    public function render(Block $block, ContentRendererInterface $renderer, RenderOptions $options): string;
}
