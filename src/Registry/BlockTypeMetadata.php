<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Registry;

use WebSystems\GutenbergBundle\Block\Definition\AttributeDefinition;

/**
 * Immutable description of a registered block type: the editor metadata declared with
 * #[AsBlock] plus the field schema declared by the block itself.
 */
final class BlockTypeMetadata
{
    /**
     * @param list<string>                          $keywords
     * @param array<string, mixed>                  $supports
     * @param list<string>                          $allowedBlocks
     * @param array<string, AttributeDefinition>    $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly string $icon,
        public readonly string $category,
        public readonly ?string $description,
        public readonly array $keywords,
        public readonly array $supports,
        public readonly bool $innerBlocks,
        public readonly array $allowedBlocks,
        public readonly array $attributes,
    ) {
    }

    /**
     * The payload consumed by the editor's `registerBlockType()` bridge.
     *
     * @return array<string, mixed>
     */
    public function toEditorSchema(): array
    {
        $attributes = [];
        $controls = [];

        foreach ($this->attributes as $definition) {
            $attributes[$definition->name] = array_filter(
                ['type' => $definition->type, 'default' => $definition->default],
                static fn (mixed $value): bool => null !== $value,
            );
            $controls[] = $definition->toEditorSchema();
        }

        return [
            'name' => $this->name,
            'title' => $this->title,
            'icon' => $this->icon,
            'category' => $this->category,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'supports' => $this->supports,
            'innerBlocks' => $this->innerBlocks,
            'allowedBlocks' => $this->allowedBlocks,
            'attributes' => $attributes,
            'controls' => $controls,
        ];
    }

    /**
     * Merges editor-supplied values with the declared defaults and coerces them to their PHP types.
     *
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function resolveAttributes(array $raw): array
    {
        $resolved = [];

        foreach ($this->attributes as $name => $definition) {
            $resolved[$name] = $definition->coerce($raw[$name] ?? null);
        }

        // Keep unknown attributes: Gutenberg core supports (align, className…) live alongside ours.
        return $resolved + $raw;
    }
}
