<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use WebSystems\GutenbergBundle\Editor\EditorConfigurationFactory;
use WebSystems\GutenbergBundle\Security\EditorAccessCheckerInterface;

/**
 * Serves the JSON the editor boots from: theme settings, custom block schemas and endpoints.
 */
final class EditorConfigController
{
    public function __construct(
        private readonly EditorConfigurationFactory $configuration,
        private readonly EditorAccessCheckerInterface $accessChecker,
    ) {
    }

    #[Route(path: '/config', name: 'web_systems_gutenberg_config', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->accessChecker->assertGranted();

        return new JsonResponse($this->configuration->create());
    }
}
