<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WebSystems\GutenbergBundle\Attribute\AsBlock;
use WebSystems\GutenbergBundle\DependencyInjection\Compiler\BlockTypePass;
use WebSystems\GutenbergBundle\Command\UpdateTranslationsCommand;
use WebSystems\GutenbergBundle\Controller\TranslationController;
use WebSystems\GutenbergBundle\EasyAdmin\Controller\ReusableBlockCrudController;
use WebSystems\GutenbergBundle\Media\FilesystemMediaStorage;
use WebSystems\GutenbergBundle\Renderer\BlockRendererInterface;
use WebSystems\GutenbergBundle\Renderer\ContentRenderer;
use WebSystems\GutenbergBundle\Repository\ReusableBlockRepository;
use WebSystems\GutenbergBundle\Repository\RevisionRepository;
use WebSystems\GutenbergBundle\Security\AlwaysGrantedAccessChecker;
use WebSystems\GutenbergBundle\Security\EditorAccessCheckerInterface;
use WebSystems\GutenbergBundle\Security\RoleAccessChecker;
use WebSystems\GutenbergBundle\Storage\DoctrineReusableBlockProvider;
use WebSystems\GutenbergBundle\Storage\DoctrineReusableBlockWriter;
use WebSystems\GutenbergBundle\Storage\DoctrineRevisionStorage;
use WebSystems\GutenbergBundle\Storage\NullReusableBlockProvider;
use WebSystems\GutenbergBundle\Storage\NullReusableBlockWriter;
use WebSystems\GutenbergBundle\Storage\NullRevisionStorage;
use WebSystems\GutenbergBundle\Storage\ReusableBlockProviderInterface;
use WebSystems\GutenbergBundle\Storage\ReusableBlockWriterInterface;
use WebSystems\GutenbergBundle\Storage\RevisionStorageInterface;
use WebSystems\GutenbergBundle\Theme\PresetStylesheet;
use WebSystems\GutenbergBundle\Translation\FileTranslationCatalogue;

final class WebSystemsGutenbergBundle extends AbstractBundle
{
    protected string $extensionAlias = 'web_systems_gutenberg';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('editor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('height')->defaultValue(720)->info('Default height of the editor canvas, in pixels.')->end()
                        ->scalarNode('locale')->defaultValue('%kernel.default_locale%')->end()
                        ->scalarNode('content_width')->defaultValue('840px')->info('Width of a normal block inside the canvas.')->end()
                        ->scalarNode('wide_width')->defaultValue('1100px')->info('Width of a "wide" aligned block; null disables wide alignment.')->end()
                        ->scalarNode('canvas_padding_block')->defaultValue('44px')->info('Vertical breathing room inside the editor canvas.')->end()
                        ->scalarNode('canvas_padding_inline')->defaultValue('28px')->info('Horizontal breathing room inside the editor canvas; full-width blocks escape it.')->end()
                        ->arrayNode('allowed_blocks')
                            ->info('Restricts the inserter to these core blocks. Custom blocks are always allowed.')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('canvas_styles')
                            ->info('Stylesheets injected inside the editor canvas so it matches the front end.')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('palette')
                            ->info('Theme colour palette offered by the colour controls.')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('slug')->isRequired()->end()
                                    ->scalarNode('color')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('font_sizes')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('slug')->isRequired()->end()
                                    ->scalarNode('size')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('gradients')
                            ->info('Theme gradients offered next to the colour palette.')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('slug')->isRequired()->end()
                                    ->scalarNode('gradient')->isRequired()->info('Any CSS gradient, e.g. "linear-gradient(135deg,#1d3557,#457b9d)".')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->booleanNode('preset_styles')
                            ->defaultTrue()
                            ->info('Emits the has-*-color / has-*-font-size classes the editor writes into content. Turn off when your own stylesheet already defines them.')
                        ->end()
                        ->booleanNode('custom_colors')
                            ->defaultTrue()
                            ->info('Lets editors pick colours outside the palette. Turn off to enforce the brand palette.')
                        ->end()
                        ->arrayNode('categories')
                            ->info('Extra inserter categories that custom blocks can be filed under.')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('slug')->isRequired()->end()
                                    ->scalarNode('title')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('assets')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('editor_styles')
                            ->scalarPrototype()->end()
                            ->defaultValue(['bundles/websystemsgutenberg/editor.css'])
                        ->end()
                        ->arrayNode('editor_scripts')
                            ->scalarPrototype()->end()
                            ->defaultValue(['bundles/websystemsgutenberg/editor.js'])
                        ->end()
                        ->arrayNode('canvas_styles')
                            ->info('Stylesheets loaded inside the editor canvas iframe; without them the in-canvas UI is unstyled.')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'bundles/websystemsgutenberg/canvas.css',
                                'bundles/websystemsgutenberg/front.css',
                            ])
                        ->end()
                        ->arrayNode('front_styles')
                            ->info('Stylesheets required to render saved blocks on the front end.')
                            ->scalarPrototype()->end()
                            ->defaultValue(['bundles/websystemsgutenberg/front.css'])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('media')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('directory')->defaultValue('%kernel.project_dir%/public/uploads/gutenberg')->end()
                        ->scalarNode('public_prefix')->defaultValue('/uploads/gutenberg')->end()
                        ->integerNode('max_file_size')->defaultValue(8 * 1024 * 1024)->end()
                        ->arrayNode('allowed_mime_types')
                            ->scalarPrototype()->end()
                            ->defaultValue(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/svg+xml'])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('translations')
                    ->info('Editor interface language, installed from the official WordPress.org language packs.')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('directory')->defaultValue('%kernel.project_dir%/var/gutenberg/translations')->end()
                        ->integerNode('http_cache_max_age')->defaultValue(86400)->info('Cache lifetime of the message-map response, in seconds.')->end()
                    ->end()
                ->end()
                ->arrayNode('revisions')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                    ->children()
                        ->integerNode('limit')->defaultValue(20)->info('How many snapshots to keep per field.')->end()
                    ->end()
                ->end()
                ->arrayNode('easyadmin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('reusable_block_crud')
                            ->defaultTrue()
                            ->info('Registers the bundle\'s screen for reusable blocks. Turn it off when the application ships its own CRUD controller: EasyAdmin requires unique controller class names.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('access_role')->defaultNull()->info('Role required by the editor back-channel endpoints; null leaves it to access_control.')->end()
                    ->end()
                ->end()
                ->integerNode('max_render_depth')
                    ->defaultValue(10)
                    ->min(1)
                    ->info('Guards against content that renders itself, directly or through another document.')
                ->end()
                ->booleanNode('doctrine')->defaultNull()->info('Force Doctrine-backed storage on or off; null auto-detects DoctrineBundle.')->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $doctrine = $config['doctrine'] ?? null;
        $doctrine ??= isset($builder->getParameter('kernel.bundles')['DoctrineBundle']);

        $services = $container->services();

        $services->get(FilesystemMediaStorage::class)
            ->arg('$directory', $config['media']['directory'])
            ->arg('$publicPrefix', $config['media']['public_prefix'])
            ->arg('$allowedMimeTypes', $config['media']['allowed_mime_types'])
            ->arg('$maxFileSize', $config['media']['max_file_size']);

        $services->get('web_systems_gutenberg.editor_settings.theme')
            ->arg('$palette', $config['editor']['palette'])
            ->arg('$fontSizes', $config['editor']['font_sizes'])
            ->arg('$gradients', $config['editor']['gradients'])
            ->arg('$customColors', $config['editor']['custom_colors'])
            ->arg('$styles', $config['editor']['canvas_styles'])
            ->arg('$contentWidth', $config['editor']['content_width'])
            ->arg('$wideWidth', $config['editor']['wide_width'])
            ->arg('$locale', $config['editor']['locale'])
            ->arg('$paddingBlock', $config['editor']['canvas_padding_block'])
            ->arg('$paddingInline', $config['editor']['canvas_padding_inline']);

        $services->get('web_systems_gutenberg.editor_settings.block_types')
            ->arg('$categories', $config['editor']['categories'])
            ->arg('$allowedBlocks', $config['editor']['allowed_blocks']);

        $services->get('web_systems_gutenberg.editor_settings.endpoints')
            ->arg('$mediaEnabled', $config['media']['enabled']);

        $services->get(FileTranslationCatalogue::class)
            ->arg('$directory', $config['translations']['directory']);

        $services->get(TranslationController::class)
            ->arg('$maxAge', $config['translations']['http_cache_max_age']);

        $services->get('web_systems_gutenberg.editor_settings.translations')
            ->arg('$locale', $config['editor']['locale'])
            ->arg('$enabled', $config['translations']['enabled']);

        $services->get(UpdateTranslationsCommand::class)
            ->arg('$configuredLocale', $config['editor']['locale']);

        $services->get(PresetStylesheet::class)
            ->arg('$palette', $config['editor']['palette'])
            ->arg('$gradients', $config['editor']['gradients'])
            ->arg('$fontSizes', $config['editor']['font_sizes'])
            ->arg('$enabled', $config['editor']['preset_styles']);

        $services->get(ContentRenderer::class)
            ->arg('$maxDepth', $config['max_render_depth']);

        $services->get('web_systems_gutenberg.asset_provider')
            ->arg('$editorStyles', $config['assets']['editor_styles'])
            ->arg('$editorScripts', $config['assets']['editor_scripts'])
            ->arg('$frontStyles', $config['assets']['front_styles'])
            ->arg('$canvasStyles', $config['assets']['canvas_styles']);

        $services->get('web_systems_gutenberg.form.type')
            ->arg('$defaultHeight', $config['editor']['height'])
            ->arg('$defaultAllowedBlocks', $config['editor']['allowed_blocks']);

        // Storage: Doctrine-backed when an ORM is available, no-ops otherwise, so that
        // consumers can always depend on the interfaces.
        if ($doctrine) {
            $services->set(ReusableBlockRepository::class)->autowire()->tag('doctrine.repository_service');
            $services->set(RevisionRepository::class)->autowire()->tag('doctrine.repository_service');
            $services->set(DoctrineRevisionStorage::class)
                ->autowire()
                ->arg('$limit', $config['revisions']['limit']);
            $services->set(DoctrineReusableBlockProvider::class)->autowire();
            $services->set(DoctrineReusableBlockWriter::class)->autowire();
        }

        $services->alias(RevisionStorageInterface::class, $doctrine && $config['revisions']['enabled'] ? DoctrineRevisionStorage::class : NullRevisionStorage::class);
        $services->alias(ReusableBlockProviderInterface::class, $doctrine ? DoctrineReusableBlockProvider::class : NullReusableBlockProvider::class);
        $services->alias(ReusableBlockWriterInterface::class, $doctrine ? DoctrineReusableBlockWriter::class : NullReusableBlockWriter::class);

        // Managing reusable blocks needs both an ORM to store them and an admin to edit them.
        if ($doctrine && $config['easyadmin']['reusable_block_crud'] && isset($builder->getParameter('kernel.bundles')['EasyAdminBundle'])) {
            $services->set(ReusableBlockCrudController::class)
                ->autowire()
                ->autoconfigure()
                ->tag('controller.service_arguments');
        }

        if (null !== $role = $config['security']['access_role']) {
            $services->set(RoleAccessChecker::class)
                ->arg('$role', $role)
                ->arg('$authorizationChecker', new Reference('security.authorization_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE));
        }

        $services->alias(EditorAccessCheckerInterface::class, null !== $role ? RoleAccessChecker::class : AlwaysGrantedAccessChecker::class);

        $builder->setParameter('web_systems_gutenberg.editor.height', $config['editor']['height']);
        $builder->setParameter('web_systems_gutenberg.media.enabled', $config['media']['enabled']);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $bundles = $builder->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $container->extension('twig', [
                'form_themes' => ['@WebSystemsGutenberg/form/gutenberg_widget.html.twig'],
            ]);
        }

        if (isset($bundles['DoctrineBundle'])) {
            $container->extension('doctrine', [
                'orm' => [
                    'mappings' => [
                        'WebSystemsGutenbergBundle' => [
                            'type' => 'attribute',
                            'dir' => $this->getPath().'/src/Entity',
                            'prefix' => 'WebSystems\GutenbergBundle\Entity',
                            'alias' => 'Gutenberg',
                            'is_bundle' => false,
                        ],
                    ],
                ],
            ]);
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new BlockTypePass());

        // A class carrying #[AsBlock] becomes a block type; nothing else has to be wired.
        $container->registerAttributeForAutoconfiguration(
            AsBlock::class,
            static function (ChildDefinition $definition, AsBlock $attribute): void {
                $definition->addTag(BlockTypePass::TAG, [
                    'block_name' => $attribute->name,
                    'title' => $attribute->title,
                    'icon' => $attribute->icon,
                    'category' => $attribute->category,
                    'description' => $attribute->description,
                    'keywords' => $attribute->keywords,
                    'supports' => $attribute->supports,
                    'innerBlocks' => $attribute->innerBlocks,
                    'allowedBlocks' => $attribute->allowedBlocks,
                ]);
            },
        );

        $container->registerForAutoconfiguration(BlockRendererInterface::class)
            ->addTag('web_systems_gutenberg.block_renderer');
    }
}
