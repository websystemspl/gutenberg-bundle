<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer\Context;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * Everything a custom block needs while rendering: its resolved attributes, the already
 * rendered HTML of its nested blocks, and the raw parsed block.
 */
final class BlockRenderContext
{
    /**
     * @param array<string, mixed> $attributes Attributes merged with the declared defaults and coerced to their PHP types
     * @param string               $innerHtml  Rendered HTML of nested blocks, ready to print
     * @param bool                 $preview    True when rendering the live preview inside the editor
     * @param array<string, mixed> $context    Free-form data passed in by the caller (current entity, locale, …)
     */
    public function __construct(
        public readonly Block $block,
        public readonly array $attributes,
        public readonly string $innerHtml = '',
        public readonly bool $preview = false,
        public readonly array $context = [],
    ) {
    }

    /**
     * Reads a value from the caller-supplied render context.
     */
    public function context(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }
}
