<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Parser;

use WebSystems\GutenbergBundle\Block\Block;

/**
 * Turns Block objects back into the WordPress block grammar.
 *
 * Round-trips whatever BlockParser produced, which makes it safe to build documents
 * programmatically (fixtures, imports, data migrations) and hand them to the editor.
 */
final class BlockSerializer
{
    /**
     * @param iterable<Block> $blocks
     */
    public function serialize(iterable $blocks): string
    {
        $out = '';

        foreach ($blocks as $block) {
            $out .= $this->serializeBlock($block);
        }

        return $out;
    }

    public function serializeBlock(Block $block): string
    {
        if ($block->isFreeform()) {
            return $block->innerHtml;
        }

        $name = str_starts_with($block->name, 'core/') ? substr($block->name, 5) : $block->name;
        $attributes = [] === $block->attributes
            ? ' '
            : ' '.json_encode($block->attributes, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE).' ';

        if ([] === $block->innerBlocks && '' === trim($block->innerHtml)) {
            return \sprintf('<!-- wp:%s%s/-->', $name, $attributes);
        }

        return \sprintf('<!-- wp:%s%s-->%s<!-- /wp:%s -->', $name, $attributes, $this->serializeInner($block), $name);
    }

    private function serializeInner(Block $block): string
    {
        if ([] === $block->innerContent) {
            return $block->innerHtml;
        }

        $out = '';
        $index = 0;

        foreach ($block->innerContent as $chunk) {
            if (null === $chunk) {
                $child = $block->innerBlocks[$index++] ?? null;
                $out .= null !== $child ? $this->serializeBlock($child) : '';

                continue;
            }

            $out .= $chunk;
        }

        return $out;
    }
}
