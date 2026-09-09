<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contributes a slice of the editor configuration.
 *
 * Providers are priority-ordered and each one only knows about its own concern (theme
 * palette, custom blocks, media endpoints…), which keeps the configuration open for
 * extension: an application adds a provider instead of patching the bundle.
 */
#[AutoconfigureTag('web_systems_gutenberg.editor_settings_provider')]
interface EditorSettingsProviderInterface
{
    public function configure(EditorSettings $settings): void;
}
