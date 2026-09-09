<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Renderer;

use WebSystems\GutenbergBundle\Block\Block;
use WebSystems\GutenbergBundle\Storage\ReusableBlockProviderInterface;

/**
 * Expands `core/block` references in place by rendering the referenced content, guarding
 * against reference cycles.
 */
final class ReusableBlockRenderer implements BlockRendererInterface
{
    /** @var array<string, true> */
    private array $rendering = [];

    public function __construct(
        private readonly ReusableBlockProviderInterface $provider,
    ) {
    }

    public function supports(Block $block): bool
    {
        return 'core/block' === $block->name && null !== $block->getAttribute('ref');
    }

    public function render(Block $block, ContentRendererInterface $renderer, RenderOptions $options): string
    {
        $reference = (string) $block->getAttribute('ref');

        if (isset($this->rendering[$reference])) {
            return ''; // Cycle: a reusable block referencing itself, directly or transitively.
        }

        $content = $this->provider->findContent($reference);

        if (null === $content) {
            return '';
        }

        $this->rendering[$reference] = true;

        try {
            return $renderer->render($content, $options);
        } finally {
            unset($this->rendering[$reference]);
        }
    }
}
