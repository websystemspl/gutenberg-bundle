<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Exposes block rendering to templates:
 *
 *     {{ gutenberg_render(page.content) }}
 *     {{ page.content|gutenberg_excerpt(200) }}
 */
final class GutenbergExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gutenberg_render', [GutenbergRuntime::class, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('gutenberg_blocks', [GutenbergRuntime::class, 'parse']),
            new TwigFunction('gutenberg_has_blocks', [GutenbergRuntime::class, 'hasBlocks']),
            new TwigFunction('gutenberg_excerpt', [GutenbergRuntime::class, 'excerpt']),
            new TwigFunction('gutenberg_editor_assets', [GutenbergRuntime::class, 'editorAssets'], ['is_safe' => ['html']]),
            new TwigFunction('gutenberg_front_assets', [GutenbergRuntime::class, 'frontAssets'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('gutenberg_render', [GutenbergRuntime::class, 'render'], ['is_safe' => ['html']]),
            new TwigFilter('gutenberg_excerpt', [GutenbergRuntime::class, 'excerpt']),
        ];
    }
}
