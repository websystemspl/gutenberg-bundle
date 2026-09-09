<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Registry;

use WebSystems\GutenbergBundle\Block\BlockTypeInterface;

/**
 * Read-only access to the block types registered in the container.
 *
 * Consumers depend on this interface rather than on the concrete registry so that
 * alternative sources (a database-backed catalogue, a remote schema) can be substituted.
 */
interface BlockTypeRegistryInterface
{
    public function has(string $name): bool;

    /**
     * @throws \InvalidArgumentException when no block type is registered under $name
     */
    public function get(string $name): BlockTypeInterface;

    /**
     * @throws \InvalidArgumentException when no block type is registered under $name
     */
    public function getMetadata(string $name): BlockTypeMetadata;

    /**
     * @return array<string, BlockTypeMetadata> indexed by block name
     */
    public function all(): array;
}
