<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Block;

/**
 * A single parsed Gutenberg block.
 *
 * Mirrors the shape produced by WordPress' `parse_blocks()` so that content written
 * by this bundle stays readable by WordPress and vice versa.
 */
final class Block
{
    /**
     * @param string|null          $name         Fully qualified block name (`core/paragraph`), or null for
     *                                           classic/freeform HTML carrying no block delimiter
     * @param array<string, mixed> $attributes   Attributes decoded from the delimiter's JSON payload
     * @param list<self>           $innerBlocks  Nested blocks, in document order
     * @param string               $innerHtml    Concatenated HTML of this block, without nested block markup
     * @param list<string|null>    $innerContent HTML chunks interleaved with nulls; every null marks the
     *                                           position of the next entry of $innerBlocks
     */
    public function __construct(
        public readonly ?string $name,
        public readonly array $attributes = [],
        public readonly array $innerBlocks = [],
        public readonly string $innerHtml = '',
        public readonly array $innerContent = [],
    ) {
    }

    public static function freeform(string $html): self
    {
        return new self(null, [], [], $html, [$html]);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param list<self>           $innerBlocks
     */
    public static function create(string $name, array $attributes = [], array $innerBlocks = [], string $innerHtml = ''): self
    {
        $innerContent = '' === $innerHtml ? [] : [$innerHtml];

        foreach ($innerBlocks as $_) {
            $innerContent[] = null;
        }

        return new self($name, $attributes, $innerBlocks, $innerHtml, $innerContent);
    }

    public function isFreeform(): bool
    {
        return null === $this->name;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Depth-first iteration over this block and all of its descendants.
     *
     * @return iterable<self>
     */
    public function walk(): iterable
    {
        yield $this;

        foreach ($this->innerBlocks as $child) {
            yield from $child->walk();
        }
    }
}
