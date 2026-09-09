<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Scaffolds a custom block: one PHP class carrying #[AsBlock] and one Twig template.
 *
 * Nothing else is needed — the class is autoconfigured as a block type, and the editor builds
 * its inspector panel from the declared fields.
 */
#[AsCommand(
    name: 'make:gutenberg-block',
    description: 'Create a custom Gutenberg block (PHP class + Twig template)',
)]
final class MakeBlockCommand
{
    public function __construct(
        private readonly string $projectDirectory,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Block name in StudlyCaps, e.g. "CallToAction"')]
        string $name,
        #[Option(description: 'Block namespace used in the editor, e.g. "app" in "app/call-to-action"')]
        string $prefix = 'app',
        #[Option(description: 'Human readable title shown in the inserter')]
        ?string $title = null,
        #[Option(description: 'Inserter category: text, media, design, widgets, embed or a custom slug')]
        string $category = 'widgets',
        #[Option(description: 'Dashicon slug shown in the inserter')]
        string $icon = 'block-default',
        #[Option(description: 'Overwrite existing files')]
        bool $force = false,
    ): int {
        $class = $this->studly($name);

        if ('' === $class) {
            $io->error('The block name must contain at least one letter.');

            return Command::INVALID;
        }

        $slug = $this->kebab($class);
        $blockName = $prefix.'/'.$slug;
        $title ??= ucfirst(str_replace('-', ' ', $slug));

        $classPath = \sprintf('%s/src/Block/%sBlock.php', $this->projectDirectory, $class);
        $templatePath = \sprintf('%s/templates/blocks/%s.html.twig', $this->projectDirectory, $slug);

        foreach ([$classPath, $templatePath] as $path) {
            if (!$force && $this->filesystem->exists($path)) {
                $io->error(\sprintf('"%s" already exists. Re-run with --force to overwrite it.', $path));

                return Command::FAILURE;
            }
        }

        $this->filesystem->dumpFile($classPath, $this->renderClass($class, $blockName, $title, $category, $icon, $slug));
        $this->filesystem->dumpFile($templatePath, $this->renderTemplate($slug));

        $io->success('Block created.');
        $io->listing([
            \sprintf('%s  (declare the editable fields here)', $classPath),
            \sprintf('%s  (front-end markup)', $templatePath),
        ]);
        $io->comment(\sprintf('It is already available in the editor as "%s". Verify with: bin/console debug:gutenberg-blocks', $blockName));

        return Command::SUCCESS;
    }

    private function renderClass(string $class, string $blockName, string $title, string $category, string $icon, string $slug): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace App\\Block;

            use WebSystems\\GutenbergBundle\\Attribute\\AsBlock;
            use WebSystems\\GutenbergBundle\\Block\\AbstractBlockType;
            use WebSystems\\GutenbergBundle\\Block\\Definition\\AttributeBuilder;

            #[AsBlock(
                name: '{$blockName}',
                title: '{$title}',
                icon: '{$icon}',
                category: '{$category}',
                template: 'blocks/{$slug}.html.twig',
            )]
            final class {$class}Block extends AbstractBlockType
            {
                public function configureAttributes(AttributeBuilder \$builder): void
                {
                    \$builder
                        ->richText('title', 'Title', 'Hello')
                        ->textarea('text', 'Text')
                    ;
                }
            }

            PHP;
    }

    private function renderTemplate(string $slug): string
    {
        return <<<TWIG
            {# Rendered on the front end and, live, inside the editor. #}
            <div class="block-{$slug}">
                <h2>{{ attributes.title|raw }}</h2>

                {% if attributes.text %}
                    <p>{{ attributes.text }}</p>
                {% endif %}
            </div>

            TWIG;
    }

    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords((string) preg_replace('/[^A-Za-z0-9]+/', ' ', $value)));
    }

    private function kebab(string $studly): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $studly));
    }
}
