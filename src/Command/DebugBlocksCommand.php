<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistryInterface;

/**
 * Lists the PHP-defined blocks the editor will offer, and the fields each one exposes.
 */
#[AsCommand(
    name: 'debug:gutenberg-blocks',
    description: 'List the registered custom Gutenberg block types',
)]
final class DebugBlocksCommand
{
    public function __construct(
        private readonly BlockTypeRegistryInterface $registry,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Show the full field schema of a single block')]
        ?string $name = null,
    ): int {
        $blocks = $this->registry->all();

        if ([] === $blocks) {
            $io->warning('No custom block types are registered. Create one with: bin/console make:gutenberg-block Hero');

            return Command::SUCCESS;
        }

        if (null === $name) {
            $io->title('Registered block types');
            $io->table(
                ['Name', 'Title', 'Category', 'Icon', 'Fields'],
                array_map(
                    static fn ($metadata): array => [
                        $metadata->name,
                        $metadata->title,
                        $metadata->category,
                        $metadata->icon,
                        \count($metadata->attributes),
                    ],
                    array_values($blocks),
                ),
            );
            $io->comment('Run "debug:gutenberg-blocks <name>" to inspect one block.');

            return Command::SUCCESS;
        }

        if (!$this->registry->has($name)) {
            $io->error(\sprintf('Block "%s" is not registered. Known blocks: %s.', $name, implode(', ', array_keys($blocks))));

            return Command::FAILURE;
        }

        $metadata = $this->registry->getMetadata($name);
        $io->title($metadata->title.' ('.$metadata->name.')');

        if (null !== $metadata->description) {
            $io->text($metadata->description);
        }

        $io->definitionList(
            ['Category' => $metadata->category],
            ['Icon' => $metadata->icon],
            ['Inner blocks' => $metadata->innerBlocks ? 'yes' : 'no'],
            ['Keywords' => implode(', ', $metadata->keywords) ?: '-'],
        );

        $io->table(
            ['Field', 'Control', 'Label', 'Type', 'Default', 'Placement'],
            array_map(
                static fn ($attribute): array => [
                    $attribute->name,
                    $attribute->control,
                    $attribute->label,
                    $attribute->type,
                    \is_scalar($attribute->default) ? var_export($attribute->default, true) : '-',
                    $attribute->inContent ? 'canvas' : 'sidebar',
                ],
                array_values($metadata->attributes),
            ),
        );

        return Command::SUCCESS;
    }
}
