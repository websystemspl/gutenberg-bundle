<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use WebSystems\GutenbergBundle\Translation\TranslationCatalogueInterface;

/**
 * Serves one locale's message map.
 *
 * Kept out of the main configuration payload because it is large, rarely changes and benefits
 * from ordinary HTTP caching.
 */
final class TranslationController
{
    public function __construct(
        private readonly TranslationCatalogueInterface $catalogue,
        private readonly int $maxAge = 86400,
    ) {
    }

    #[Route(path: '/translations/{locale}.json', name: 'web_systems_gutenberg_translations', requirements: ['locale' => '[A-Za-z0-9_-]+'], methods: ['GET'])]
    public function __invoke(string $locale, Request $request): Response
    {
        $messages = $this->catalogue->getMessages($locale)
            ?? throw new NotFoundHttpException(\sprintf('No editor translations are installed for "%s". Run: bin/console gutenberg:translations %s', $locale, $locale));

        $response = new JsonResponse($messages);
        $response->setPublic();
        $response->setMaxAge($this->maxAge);
        $response->setEtag(hash('xxh128', (string) $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }
}
