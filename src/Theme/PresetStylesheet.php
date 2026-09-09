<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Theme;

/**
 * Generates the CSS behind the theme presets.
 *
 * When an editor picks a palette colour, Gutenberg does not write the colour into the markup —
 * it writes a class such as `has-primary-background-color`. Something has to define those
 * classes, and in WordPress that something is the stylesheet generated from theme.json. This
 * class fills the same role, so a colour chosen in the editor is a colour the visitor sees.
 *
 * The output mirrors WordPress': custom properties on the root, and `!important` on the preset
 * classes so they win over a block's own styles, exactly as upstream does.
 */
final class PresetStylesheet
{
    /**
     * @param list<array{name: string, slug: string, color: string}>       $palette
     * @param list<array{name: string, slug: string, gradient: string}>    $gradients
     * @param list<array{name: string, slug: string, size: int|string}>    $fontSizes
     */
    public function __construct(
        private readonly array $palette = [],
        private readonly array $gradients = [],
        private readonly array $fontSizes = [],
        private readonly bool $enabled = true,
    ) {
    }

    public function isEmpty(): bool
    {
        return !$this->enabled || ([] === $this->palette && [] === $this->gradients && [] === $this->fontSizes);
    }

    public function render(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $variables = [];
        $rules = [];

        foreach ($this->palette as $colour) {
            $slug = $this->slug($colour['slug'] ?? null);
            $value = $this->value($colour['color'] ?? null);

            if (null === $slug || null === $value) {
                continue;
            }

            $variables[] = \sprintf('--wp--preset--color--%s: %s;', $slug, $value);
            $reference = \sprintf('var(--wp--preset--color--%s)', $slug);
            $rules[] = \sprintf('.has-%s-color{color:%s !important}', $slug, $reference);
            $rules[] = \sprintf('.has-%s-background-color{background-color:%s !important}', $slug, $reference);
            $rules[] = \sprintf('.has-%s-border-color{border-color:%s !important}', $slug, $reference);
        }

        foreach ($this->gradients as $gradient) {
            $slug = $this->slug($gradient['slug'] ?? null);
            $value = $this->value($gradient['gradient'] ?? null);

            if (null === $slug || null === $value) {
                continue;
            }

            $variables[] = \sprintf('--wp--preset--gradient--%s: %s;', $slug, $value);
            $rules[] = \sprintf('.has-%s-gradient-background{background:var(--wp--preset--gradient--%s) !important}', $slug, $slug);
        }

        foreach ($this->fontSizes as $size) {
            $slug = $this->slug($size['slug'] ?? null);
            $value = $this->value(\is_int($size['size'] ?? null) ? $size['size'].'px' : ($size['size'] ?? null));

            if (null === $slug || null === $value) {
                continue;
            }

            $variables[] = \sprintf('--wp--preset--font-size--%s: %s;', $slug, $value);
            $rules[] = \sprintf('.has-%s-font-size{font-size:var(--wp--preset--font-size--%s) !important}', $slug, $slug);
        }

        if ([] === $variables) {
            return '';
        }

        return ':root{'.implode('', $variables).'}'.implode('', $rules);
    }

    private function slug(mixed $slug): ?string
    {
        if (!\is_string($slug)) {
            return null;
        }

        $slug = strtolower(trim($slug));

        // Slugs reach the stylesheet from configuration and become part of a selector.
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) ? $slug : null;
    }

    private function value(mixed $value): ?string
    {
        if (!\is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        // A declaration value must not be able to close its block or start a new one.
        return '' !== $value && !preg_match('/[{}<>;]/', $value) ? $value : null;
    }
}
