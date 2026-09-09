<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor;

/**
 * Builds the editor configuration by running every registered provider over a shared bag.
 */
final class EditorConfigurationFactory
{
    /**
     * @param iterable<EditorSettingsProviderInterface> $providers priority-ordered
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(): array
    {
        $settings = new EditorSettings();

        foreach ($this->providers as $provider) {
            $provider->configure($settings);
        }

        return $settings->all();
    }
}
