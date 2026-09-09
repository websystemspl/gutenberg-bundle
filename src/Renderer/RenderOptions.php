<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

/**
 * Immutable per-render settings, passed down the whole block tree.
 *
 * Kept as an object rather than a bare flag so that new options can be introduced without
 * changing the signature of every renderer.
 */
final class RenderOptions
{
    /**
     * @param bool                 $preview True while rendering the live preview shown inside the editor
     * @param array<string, mixed> $context Free-form data forwarded to block templates (locale, current entity, …)
     */
    public function __construct(
        public readonly bool $preview = false,
        public readonly array $context = [],
    ) {
    }

    public static function preview(): self
    {
        return new self(true);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self
    {
        return new self($this->preview, [...$this->context, ...$context]);
    }
}
