<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use WebSystems\GutenbergBundle\Block\Block;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistryInterface;
use WebSystems\GutenbergBundle\Renderer\Context\BlockRenderContext;

/**
 * Renders blocks backed by a PHP block type: attributes are resolved against the declared
 * schema and handed to the block's own render() (usually a Twig template).
 */
final class DynamicBlockRenderer implements BlockRendererInterface
{
    public function __construct(
        private readonly BlockTypeRegistryInterface $registry,
    ) {
    }

    public function supports(Block $block): bool
    {
        return !$block->isFreeform() && $this->registry->has($block->name);
    }

    public function render(Block $block, ContentRendererInterface $renderer, RenderOptions $options): string
    {
        $metadata = $this->registry->getMetadata($block->name);

        return $this->registry->get($block->name)->render(new BlockRenderContext(
            $block,
            $metadata->resolveAttributes($block->attributes),
            $renderer->renderBlocks($block->innerBlocks, $options),
            $options->preview,
            $options->context,
        ));
    }
}
