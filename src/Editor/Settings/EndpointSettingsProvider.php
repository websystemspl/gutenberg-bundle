<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor\Settings;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WebSystems\GutenbergBundle\Editor\EditorSettings;
use WebSystems\GutenbergBundle\Editor\EditorSettingsProviderInterface;

/**
 * Publishes the URLs the editor calls back into: server-side block preview, media library
 * and reusable blocks.
 */
final class EndpointSettingsProvider implements EditorSettingsProviderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly bool $mediaEnabled,
    ) {
    }

    public function configure(EditorSettings $settings): void
    {
        $settings->set('endpoints', array_filter([
            'preview' => $this->generate('web_systems_gutenberg_preview'),
            'media' => $this->mediaEnabled ? $this->generate('web_systems_gutenberg_media') : null,
            'reusable' => $this->generate('web_systems_gutenberg_reusable'),
        ]));
    }

    private function generate(string $route): ?string
    {
        try {
            return $this->urlGenerator->generate($route);
        } catch (\Throwable) {
            // Routes not imported: the editor degrades to client-only behaviour.
            return null;
        }
    }
}
