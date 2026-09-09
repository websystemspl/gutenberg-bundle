<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor\Settings;

use WebSystems\GutenbergBundle\Asset\EditorAssetProvider;
use WebSystems\GutenbergBundle\Editor\EditorSettings;
use WebSystems\GutenbergBundle\Editor\EditorSettingsProviderInterface;
use WebSystems\GutenbergBundle\Theme\PresetStylesheet;

/**
 * Feeds the editor the look-and-feel declared under `web_systems_gutenberg.editor`: colour
 * palette, font sizes, content width and the stylesheets applied inside the canvas.
 *
 * The width and spacing of the canvas travel in `__experimentalFeatures`, which is the
 * theme.json-shaped bag the block editor actually reads its layout from; a plain `layout` key
 * on the settings object is ignored, which leaves every block full-bleed and flush to the edge.
 */
final class ThemeSettingsProvider implements EditorSettingsProviderInterface
{
    /**
     * @param list<array{name: string, slug: string, color: string}>    $palette
     * @param list<array{name: string, slug: string, size: int|string}> $fontSizes
     * @param list<array{name: string, slug: string, gradient: string}>  $gradients
     * @param list<string>                                              $styles        application stylesheets injected into the canvas
     * @param string|null                                               $paddingBlock  vertical padding of the canvas root, e.g. '44px'
     * @param string|null                                               $paddingInline horizontal padding of the canvas root, e.g. '28px'
     */
    public function __construct(
        private readonly array $palette,
        private readonly array $fontSizes,
        private readonly array $gradients,
        private readonly array $styles,
        private readonly EditorAssetProvider $assets,
        private readonly PresetStylesheet $presets,
        private readonly ?string $contentWidth,
        private readonly ?string $wideWidth,
        private readonly string $locale,
        private readonly ?string $paddingBlock = null,
        private readonly ?string $paddingInline = null,
        private readonly bool $customColors = true,
    ) {
    }

    public function configure(EditorSettings $settings): void
    {
        $layout = array_filter([
            'contentSize' => $this->contentWidth,
            'wideSize' => $this->wideWidth,
        ]);

        $settings
            ->set('locale', $this->locale)
            // The bundle's own canvas styles come first so the application can override them.
            ->set('styles', [...$this->assets->canvasStyles(), ...$this->assets->resolve($this->styles)])
            ->set('inlineStyles', $this->canvasCss())
            ->merge(['editor' => [
                // Kept for older consumers; the canonical source is __experimentalFeatures below.
                'colors' => $this->palette,
                'fontSizes' => $this->fontSizes,
                'disableCustomColors' => !$this->customColors,
                'disableCustomFontSizes' => false,
                'gradients' => $this->gradients,
                'alignWide' => null !== $this->wideWidth,
                'supportsLayout' => true,
                '__experimentalFeatures' => [
                    'layout' => $layout,
                    'useRootPaddingAwareAlignments' => false,
                    // A palette alone is not enough: each colour control is gated on its own
                    // flag, so without these the core blocks show no Colour panel at all.
                    'color' => [
                        'palette' => ['theme' => $this->palette],
                        'gradients' => ['theme' => $this->gradients],
                        'text' => true,
                        'background' => true,
                        'link' => true,
                        'heading' => true,
                        'button' => true,
                        'custom' => $this->customColors,
                        'customGradient' => $this->customColors,
                        'customDuotone' => $this->customColors,
                        // WordPress' own palette lives in its theme.json, which is absent here;
                        // showing the toggles for it would offer an empty section.
                        'defaultPalette' => false,
                        'defaultGradients' => false,
                        'defaultDuotone' => false,
                    ],
                    'typography' => [
                        'fontSizes' => ['theme' => $this->fontSizes],
                        'customFontSize' => true,
                        'lineHeight' => true,
                        'textAlign' => true,
                    ],
                    'spacing' => ['padding' => true, 'margin' => true, 'blockGap' => true],
                ],
            ]]);
    }

    /**
     * Reproduces the content column and the gutter of the front end inside the canvas.
     *
     * The constrained root layout is declared to the editor so blocks offer the right alignment
     * controls, but the editor package that normally emits its stylesheet is not part of this
     * bundle, so the equivalent rules are generated here from the same configuration.
     */
    private function canvasCss(): ?string
    {
        $block = trim((string) $this->paddingBlock) ?: '0px';
        $inline = trim((string) $this->paddingInline) ?: '0px';
        $rules = [];

        if ('' !== $presets = $this->presets->render()) {
            $rules[] = $presets;
        }

        if ('0px' !== $block || '0px' !== $inline) {
            $rules[] = <<<CSS
                .is-root-container {
                    --wsg-canvas-padding-block: {$block};
                    --wsg-canvas-padding-inline: {$inline};
                    padding: var(--wsg-canvas-padding-block) var(--wsg-canvas-padding-inline);
                    box-sizing: border-box;
                }
                CSS;
        }

        if (null !== $this->contentWidth) {
            $rules[] = <<<CSS
                .is-root-container > * {
                    max-width: {$this->contentWidth};
                    margin-left: auto;
                    margin-right: auto;
                }
                CSS;
        }

        if (null !== $this->wideWidth) {
            $rules[] = <<<CSS
                .is-root-container > .alignwide {
                    max-width: {$this->wideWidth};
                }
                CSS;
        }

        // Full-width blocks step back out of the gutter, as they do on the front end.
        $rules[] = <<<CSS
            .is-root-container > .alignfull {
                max-width: none;
                width: auto;
                margin-left: calc(-1 * var(--wsg-canvas-padding-inline, 0px));
                margin-right: calc(-1 * var(--wsg-canvas-padding-inline, 0px));
            }
            CSS;

        return [] === $rules ? null : implode("\n\n", $rules);
    }
}
