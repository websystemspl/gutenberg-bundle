<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * Turns stored block content into front-end HTML.
 *
 * This is the abstraction controllers, Twig and EasyAdmin depend on; the concrete
 * implementation composes the parser with the registered rendering strategies.
 */
interface ContentRendererInterface
{
    /**
     * Parses and renders a whole stored document.
     */
    public function render(?string $content, ?RenderOptions $options = null): string;

    /**
     * @param iterable<Block> $blocks
     */
    public function renderBlocks(iterable $blocks, ?RenderOptions $options = null): string;

    public function renderBlock(Block $block, ?RenderOptions $options = null): string;
}
