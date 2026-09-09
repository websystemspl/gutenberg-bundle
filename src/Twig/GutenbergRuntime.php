<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Twig;

use Twig\Extension\RuntimeExtensionInterface;
use WebSystems\GutenbergBundle\Asset\EditorAssetProvider;
use WebSystems\GutenbergBundle\Block\Block;
use WebSystems\GutenbergBundle\Parser\BlockParser;
use WebSystems\GutenbergBundle\Renderer\ContentRendererInterface;
use WebSystems\GutenbergBundle\Renderer\RenderOptions;
use WebSystems\GutenbergBundle\Theme\PresetStylesheet;

/**
 * Lazily instantiated implementation behind the `gutenberg_*` Twig functions.
 */
final class GutenbergRuntime implements RuntimeExtensionInterface
{
    /**
     * Guards against emitting the editor bundle twice when a page holds several fields, or
     * when the developer already printed the tags in the <head>.
     */
    private bool $editorAssetsRendered = false;
    private bool $frontAssetsRendered = false;

    public function __construct(
        private readonly ContentRendererInterface $renderer,
        private readonly BlockParser $parser,
        private readonly EditorAssetProvider $assets,
        private readonly PresetStylesheet $presets,
    ) {
    }

    /**
     * @param array<string, mixed> $context forwarded to block templates as `context`
     */
    public function render(?string $content, array $context = []): string
    {
        return $this->renderer->render($content, new RenderOptions(false, $context));
    }

    /**
     * @return list<Block>
     */
    public function parse(?string $content): array
    {
        return $this->parser->parse($content);
    }

    public function hasBlocks(?string $content): bool
    {
        return [] !== array_filter($this->parser->parse($content), static fn (Block $block): bool => !$block->isFreeform());
    }

    /**
     * Plain-text excerpt, handy for listings and meta descriptions.
     */
    public function excerpt(?string $content, int $length = 160, string $ellipsis = '…'): string
    {
        $text = trim(html_entity_decode(strip_tags($this->render($content)), \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace > 0 ? mb_substr($cut, 0, $lastSpace) : $cut).$ellipsis;
    }

    /**
     * Renders the <link>/<script> tags of the editor bundle, at most once per request.
     */
    public function editorAssets(): string
    {
        if ($this->editorAssetsRendered) {
            return '';
        }

        $this->editorAssetsRendered = true;
        $html = '';

        foreach ($this->assets->editorStyles() as $url) {
            $html .= \sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($url, \ENT_QUOTES));
        }

        foreach ($this->assets->editorScripts() as $url) {
            $html .= \sprintf('<script defer src="%s"></script>', htmlspecialchars($url, \ENT_QUOTES));
        }

        return $html;
    }

    public function frontAssets(): string
    {
        if ($this->frontAssetsRendered) {
            return '';
        }

        $this->frontAssetsRendered = true;
        $html = '';

        foreach ($this->assets->frontStyles() as $url) {
            $html .= \sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($url, \ENT_QUOTES));
        }

        // Defines the has-*-color / has-*-font-size classes the editor writes into content.
        $presets = $this->presets->render();

        if ('' !== $presets) {
            $html .= '<style>'.$presets.'</style>';
        }

        return $html;
    }
}
