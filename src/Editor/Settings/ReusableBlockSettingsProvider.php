<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor\Settings;

use WebSystems\GutenbergBundle\Editor\EditorSettings;
use WebSystems\GutenbergBundle\Editor\EditorSettingsProviderInterface;
use WebSystems\GutenbergBundle\Storage\ReusableBlockProviderInterface;

/**
 * Publishes the saved reusable blocks so the editor can offer each one in the inserter and
 * preview it, without a round trip of its own.
 */
final class ReusableBlockSettingsProvider implements EditorSettingsProviderInterface
{
    public function __construct(
        private readonly ReusableBlockProviderInterface $provider,
    ) {
    }

    public function configure(EditorSettings $settings): void
    {
        $blocks = [];

        foreach ($this->provider->listReferences() as $reference => $title) {
            $blocks[] = ['ref' => is_numeric($reference) ? (int) $reference : $reference, 'title' => $title];
        }

        $settings->set('reusableBlocks', $blocks);
    }
}
