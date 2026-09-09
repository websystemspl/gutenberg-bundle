<?php

declare(strict_types=1);

use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WebSystems\GutenbergBundle\Asset\EditorAssetProvider;
use WebSystems\GutenbergBundle\Command\DebugBlocksCommand;
use WebSystems\GutenbergBundle\Command\MakeBlockCommand;
use WebSystems\GutenbergBundle\Command\UpdateEditorCommand;
use WebSystems\GutenbergBundle\Command\UpdateTranslationsCommand;
use WebSystems\GutenbergBundle\Controller\BlockPreviewController;
use WebSystems\GutenbergBundle\Controller\EditorConfigController;
use WebSystems\GutenbergBundle\Controller\MediaController;
use WebSystems\GutenbergBundle\Controller\ReusableBlockController;
use WebSystems\GutenbergBundle\Controller\TranslationController;
use WebSystems\GutenbergBundle\Editor\EditorConfigurationFactory;
use WebSystems\GutenbergBundle\Editor\Settings\BlockTypeSettingsProvider;
use WebSystems\GutenbergBundle\Editor\Settings\EndpointSettingsProvider;
use WebSystems\GutenbergBundle\Editor\Settings\ReusableBlockSettingsProvider;
use WebSystems\GutenbergBundle\Editor\Settings\ThemeSettingsProvider;
use WebSystems\GutenbergBundle\Editor\Settings\TranslationSettingsProvider;
use WebSystems\GutenbergBundle\Translation\FileTranslationCatalogue;
use WebSystems\GutenbergBundle\Translation\TranslationCatalogueInterface;
use WebSystems\GutenbergBundle\Translation\TranslationInstaller;
use WebSystems\GutenbergBundle\Form\Type\GutenbergType;
use WebSystems\GutenbergBundle\Media\FilesystemMediaStorage;
use WebSystems\GutenbergBundle\Media\MediaStorageInterface;
use WebSystems\GutenbergBundle\Parser\BlockParser;
use WebSystems\GutenbergBundle\Parser\BlockSerializer;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistry;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistryInterface;
use WebSystems\GutenbergBundle\Renderer\ContentRenderer;
use WebSystems\GutenbergBundle\Renderer\ContentRendererInterface;
use WebSystems\GutenbergBundle\Renderer\DynamicBlockRenderer;
use WebSystems\GutenbergBundle\Renderer\FreeformBlockRenderer;
use WebSystems\GutenbergBundle\Renderer\ReusableBlockRenderer;
use WebSystems\GutenbergBundle\Renderer\StaticBlockRenderer;
use WebSystems\GutenbergBundle\Security\AlwaysGrantedAccessChecker;
use WebSystems\GutenbergBundle\Storage\NullReusableBlockProvider;
use WebSystems\GutenbergBundle\Storage\NullReusableBlockWriter;
use WebSystems\GutenbergBundle\Storage\NullRevisionStorage;
use WebSystems\GutenbergBundle\Theme\PresetStylesheet;
use WebSystems\GutenbergBundle\Twig\GutenbergExtension;
use WebSystems\GutenbergBundle\Twig\GutenbergRuntime;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure(false)
            ->private()
    ;

    // --- Parsing ---------------------------------------------------------------------
    $services->set(BlockParser::class);
    $services->set(BlockSerializer::class);

    // --- Block type registry (arguments are filled in by BlockTypePass) ----------------
    $services->set(BlockTypeRegistry::class)
        ->args(['$blockTypes' => service('service_container'), '$definitions' => []]);
    $services->alias(BlockTypeRegistryInterface::class, BlockTypeRegistry::class);

    // --- Rendering strategies, highest priority first ---------------------------------
    $services->set(ReusableBlockRenderer::class)
        ->tag('web_systems_gutenberg.block_renderer', ['priority' => 200]);
    $services->set(DynamicBlockRenderer::class)
        ->tag('web_systems_gutenberg.block_renderer', ['priority' => 100]);
    $services->set(FreeformBlockRenderer::class)
        ->tag('web_systems_gutenberg.block_renderer', ['priority' => 50]);
    $services->set(StaticBlockRenderer::class)
        ->tag('web_systems_gutenberg.block_renderer', ['priority' => -100]);

    $services->set(ContentRenderer::class)
        ->args([
            '$parser' => service(BlockParser::class),
            '$renderers' => tagged_iterator('web_systems_gutenberg.block_renderer'),
            '$logger' => service('logger')->nullOnInvalid(),
        ]);
    $services->alias(ContentRendererInterface::class, ContentRenderer::class);

    // --- Editor configuration ---------------------------------------------------------
    $services->set(EditorConfigurationFactory::class)
        ->args(['$providers' => tagged_iterator('web_systems_gutenberg.editor_settings_provider')]);

    $services->set('web_systems_gutenberg.editor_settings.theme', ThemeSettingsProvider::class)
        ->tag('web_systems_gutenberg.editor_settings_provider', ['priority' => 100]);
    $services->set('web_systems_gutenberg.editor_settings.block_types', BlockTypeSettingsProvider::class)
        ->tag('web_systems_gutenberg.editor_settings_provider', ['priority' => 50]);
    $services->set('web_systems_gutenberg.editor_settings.endpoints', EndpointSettingsProvider::class)
        ->tag('web_systems_gutenberg.editor_settings_provider', ['priority' => 0]);
    $services->set('web_systems_gutenberg.editor_settings.reusable', ReusableBlockSettingsProvider::class)
        ->tag('web_systems_gutenberg.editor_settings_provider', ['priority' => 40]);
    $services->set('web_systems_gutenberg.editor_settings.translations', TranslationSettingsProvider::class)
        ->tag('web_systems_gutenberg.editor_settings_provider', ['priority' => 75]);

    // --- Interface translations ---------------------------------------------------------
    $services->set(FileTranslationCatalogue::class);
    $services->alias(TranslationCatalogueInterface::class, FileTranslationCatalogue::class);
    // TranslationInstaller needs a real HTTP client, not a nullable one, so it is only
    // declared when symfony/http-client is installed; the command degrades with a message.
    if (class_exists(HttpClient::class)) {
        $services->set(TranslationInstaller::class)
            ->args(['$httpClient' => service('http_client')]);
    }

    // --- Assets, Twig, forms ----------------------------------------------------------
    $services->set('web_systems_gutenberg.asset_provider', EditorAssetProvider::class)
        ->args(['$packages' => service('assets.packages')->nullOnInvalid()]);
    $services->alias(EditorAssetProvider::class, 'web_systems_gutenberg.asset_provider');

    $services->set(PresetStylesheet::class);

    $services->set(GutenbergRuntime::class)
        ->args(['$assets' => service('web_systems_gutenberg.asset_provider')])
        ->tag('twig.runtime');
    $services->set(GutenbergExtension::class)->tag('twig.extension');

    $services->set('web_systems_gutenberg.form.type', GutenbergType::class)
        ->tag('form.type');

    // --- Storage defaults (overridden with Doctrine implementations when available) -----
    $services->set(NullRevisionStorage::class);
    $services->set(NullReusableBlockProvider::class);
    $services->set(NullReusableBlockWriter::class);

    $services->set(FilesystemMediaStorage::class);
    $services->alias(MediaStorageInterface::class, FilesystemMediaStorage::class);

    $services->set(AlwaysGrantedAccessChecker::class);

    // --- HTTP endpoints ---------------------------------------------------------------
    $services->set(EditorConfigController::class)->tag('controller.service_arguments');
    $services->set(BlockPreviewController::class)->tag('controller.service_arguments');
    $services->set(MediaController::class)->tag('controller.service_arguments');
    $services->set(ReusableBlockController::class)->tag('controller.service_arguments');
    $services->set(TranslationController::class)->tag('controller.service_arguments');

    // --- Console ----------------------------------------------------------------------
    // symfony/console is optional: without it these classes cannot even be reflected, so the
    // container must not try to register them.
    if (!class_exists(Command::class)) {
        return;
    }

    $services->set(UpdateEditorCommand::class)
        ->args(['$bundleDirectory' => \dirname(__DIR__), '$httpClient' => service('http_client')->nullOnInvalid()])
        ->tag('console.command');
    $services->set(MakeBlockCommand::class)
        ->args(['$projectDirectory' => '%kernel.project_dir%'])
        ->tag('console.command');
    $services->set(DebugBlocksCommand::class)
        ->tag('console.command');
    $services->set(UpdateTranslationsCommand::class)
        ->args(['$installer' => service(TranslationInstaller::class)->nullOnInvalid()])
        ->tag('console.command');
};
