<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Registry;

use Psr\Container\ContainerInterface;
use WebSystems\GutenbergBundle\Block\BlockTypeInterface;
use WebSystems\GutenbergBundle\Block\Definition\AttributeBuilder;

/**
 * Registry backed by the service container.
 *
 * Block types stay lazy: the service locator instantiates one only when it is rendered or
 * when its field schema is requested, so a catalogue of a hundred blocks costs nothing
 * on a request that renders two of them.
 */
final class BlockTypeRegistry implements BlockTypeRegistryInterface
{
    /** @var array<string, BlockTypeMetadata> */
    private array $metadataCache = [];

    /**
     * @param ContainerInterface                 $blockTypes service locator, indexed by block name
     * @param array<string, array<string, mixed>> $definitions metadata collected from #[AsBlock], indexed by block name
     */
    public function __construct(
        private readonly ContainerInterface $blockTypes,
        private readonly array $definitions,
    ) {
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function get(string $name): BlockTypeInterface
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(\sprintf('No block type is registered under the name "%s". Registered names: %s.', $name, implode(', ', array_keys($this->definitions)) ?: '(none)'));
        }

        return $this->blockTypes->get($name);
    }

    public function getMetadata(string $name): BlockTypeMetadata
    {
        return $this->metadataCache[$name] ??= $this->buildMetadata($name);
    }

    public function all(): array
    {
        $all = [];

        foreach (array_keys($this->definitions) as $name) {
            $all[$name] = $this->getMetadata($name);
        }

        return $all;
    }

    private function buildMetadata(string $name): BlockTypeMetadata
    {
        $definition = $this->definitions[$name] ?? throw new \InvalidArgumentException(\sprintf('No block type is registered under the name "%s".', $name));

        $builder = new AttributeBuilder();
        $this->get($name)->configureAttributes($builder);

        return new BlockTypeMetadata(
            $name,
            $definition['title'] ?? $name,
            $definition['icon'] ?? 'block-default',
            $definition['category'] ?? 'widgets',
            $definition['description'] ?? null,
            $definition['keywords'] ?? [],
            $definition['supports'] ?? [],
            $definition['innerBlocks'] ?? false,
            $definition['allowedBlocks'] ?? [],
            $builder->all(),
        );
    }
}
