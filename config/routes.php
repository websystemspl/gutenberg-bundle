<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Back-channel endpoints used by the editor. Import them from the application with:
 *
 *     # config/routes/web_systems_gutenberg.yaml
 *     web_systems_gutenberg:
 *         resource: '@WebSystemsGutenbergBundle/config/routes.php'
 *         prefix: /_gutenberg
 */
return static function (RoutingConfigurator $routes): void {
    $routes->import(\dirname(__DIR__).'/src/Controller/', 'attribute');
};
