<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistry;

/**
 * Collects services tagged by #[AsBlock] into a lazy service locator plus a metadata map,
 * and injects both into the registry.
 */
final class BlockTypePass implements CompilerPassInterface
{
    public const TAG = 'web_systems_gutenberg.block';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(BlockTypeRegistry::class)) {
            return;
        }

        $references = [];
        $definitions = [];

        foreach ($container->findTaggedServiceIds(self::TAG, true) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $name = $tag['block_name'] ?? throw new InvalidArgumentException(\sprintf('Service "%s" is tagged "%s" without a block name; register block types with the #[AsBlock] attribute.', $serviceId, self::TAG));

                if (isset($references[$name])) {
                    throw new InvalidArgumentException(\sprintf('Two block types are registered under the name "%s"; block names must be unique.', $name));
                }

                $references[$name] = new Reference($serviceId);
                $definitions[$name] = [
                    'title' => $tag['title'] ?? $name,
                    'icon' => $tag['icon'] ?? 'block-default',
                    'category' => $tag['category'] ?? 'widgets',
                    'description' => $tag['description'] ?? null,
                    'keywords' => $tag['keywords'] ?? [],
                    'supports' => $tag['supports'] ?? [],
                    'innerBlocks' => $tag['innerBlocks'] ?? false,
                    'allowedBlocks' => $tag['allowedBlocks'] ?? [],
                ];
            }
        }

        ksort($definitions);

        $registry = $container->getDefinition(BlockTypeRegistry::class);
        $registry->setArgument('$blockTypes', new ServiceLocatorArgument($references));
        $registry->setArgument('$definitions', $definitions);
    }
}
