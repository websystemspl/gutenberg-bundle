<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Editor\Settings;

use WebSystems\GutenbergBundle\Editor\EditorSettings;
use WebSystems\GutenbergBundle\Editor\EditorSettingsProviderInterface;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistryInterface;

/**
 * Exports every PHP-defined block type as a JSON schema; the editor turns each entry into a
 * registered block with a generated inspector panel and a server-rendered preview.
 */
final class BlockTypeSettingsProvider implements EditorSettingsProviderInterface
{
    /**
     * @param list<array{slug: string, title: string}> $categories
     * @param list<string>                             $allowedBlocks empty means "all core blocks"
     */
    public function __construct(
        private readonly BlockTypeRegistryInterface $registry,
        private readonly array $categories = [],
        private readonly array $allowedBlocks = [],
    ) {
    }

    public function configure(EditorSettings $settings): void
    {
        $blocks = [];

        foreach ($this->registry->all() as $metadata) {
            $blocks[] = $metadata->toEditorSchema();
        }

        $settings->set('customBlocks', $blocks);
        $settings->set('blockCategories', $this->categories);

        if ([] !== $this->allowedBlocks) {
            $names = array_column($blocks, 'name');
            $settings->merge(['editor' => ['allowedBlockTypes' => array_values(array_unique([...$this->allowedBlocks, ...$names]))]]);
        }
    }
}
