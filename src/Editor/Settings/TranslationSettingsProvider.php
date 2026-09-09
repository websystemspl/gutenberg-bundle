<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor\Settings;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WebSystems\GutenbergBundle\Editor\EditorSettings;
use WebSystems\GutenbergBundle\Editor\EditorSettingsProviderInterface;
use WebSystems\GutenbergBundle\Translation\TranslationCatalogueInterface;
use WebSystems\GutenbergBundle\Translation\WordPressLocale;

/**
 * Tells the editor which locale to run in and where to fetch its message map.
 */
final class TranslationSettingsProvider implements EditorSettingsProviderInterface
{
    /** Languages WordPress ships right-to-left builds for. */
    private const RTL_LANGUAGES = ['ar', 'ary', 'azb', 'ckb', 'dv', 'fa', 'haz', 'he', 'ps', 'sd', 'ug', 'ur', 'yi'];

    public function __construct(
        private readonly TranslationCatalogueInterface $catalogue,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $locale,
        private readonly bool $enabled = true,
    ) {
    }

    public function configure(EditorSettings $settings): void
    {
        $locale = WordPressLocale::normalize($this->locale);
        $available = $this->enabled && $this->catalogue->has($locale);

        $settings->set('translations', [
            'locale' => $locale,
            'available' => $available,
            'rtl' => \in_array(strtolower(explode('_', $locale)[0]), self::RTL_LANGUAGES, true),
            'url' => $available ? $this->url($locale) : null,
        ]);
    }

    private function url(string $locale): ?string
    {
        try {
            return $this->urlGenerator->generate('web_systems_gutenberg_translations', ['locale' => $locale]);
        } catch (\Throwable) {
            return null;
        }
    }
}
