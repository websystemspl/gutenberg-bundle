<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Attribute;

/**
 * Registers a class as a Gutenberg block type.
 *
 * Everything the editor needs — the entry in the inserter, the icon, the category and the
 * inspector panel — is derived from this attribute plus the class' attribute schema, so a
 * custom block needs no JavaScript at all.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsBlock
{
    /**
     * @param string               $name        Fully qualified block name, e.g. `app/hero`. Must contain a namespace.
     * @param string               $title       Label shown in the inserter and the block toolbar
     * @param string               $icon        Icon name resolved against @wordpress/icons, in camelCase (`listView`)
     *                                          or kebab-case (`list-view`); the common Dashicon slugs
     *                                          (`cover-image`, `format-quote`, …) are mapped to their SVG
     *                                          equivalents. Raw `<svg>` markup is also accepted. Dashicons
     *                                          themselves are a WordPress font that ships with no npm package,
     *                                          so a bare slug with no mapping falls back to the default icon.
     * @param string               $category    Editor category: `text`, `media`, `design`, `widgets`, `embed`, or a custom one
     * @param string|null          $template    Twig template used to render the block; required by AbstractBlockType
     * @param string|null          $description Sentence shown in the inserter tooltip
     * @param list<string>         $keywords    Extra search terms for the inserter
     * @param array<string, mixed> $supports    Raw Gutenberg `supports` payload (align, color, spacing, …)
     * @param bool                 $innerBlocks Whether the block accepts nested blocks (renders an InnerBlocks area)
     * @param list<string>         $allowedBlocks Restricts which blocks may be nested, when $innerBlocks is true
     * @param string|null          $example     Attribute set used to render the inserter preview
     */
    public function __construct(
        public string $name,
        public string $title,
        public string $icon = 'block-default',
        public string $category = 'widgets',
        public ?string $template = null,
        public ?string $description = null,
        public array $keywords = [],
        public array $supports = [],
        public bool $innerBlocks = false,
        public array $allowedBlocks = [],
        public ?string $example = null,
    ) {
    }
}
