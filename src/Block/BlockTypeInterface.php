<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Block;

use WebSystems\GutenbergBundle\Block\Definition\AttributeBuilder;
use WebSystems\GutenbergBundle\Renderer\Context\BlockRenderContext;

/**
 * A server-rendered ("dynamic") block type.
 *
 * Implementations are registered by the #[AsBlock] attribute, which also declares the editor metadata.
 * In practice you extend AbstractBlockType and only describe the fields.
 */
interface BlockTypeInterface
{
    /**
     * Declares the editable fields; the editor builds its controls from this description.
     */
    public function configureAttributes(AttributeBuilder $builder): void;

    public function render(BlockRenderContext $context): string;
}
