<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use WebSystems\GutenbergBundle\Security\EditorAccessCheckerInterface;
use WebSystems\GutenbergBundle\Storage\ReusableBlockProviderInterface;
use WebSystems\GutenbergBundle\Storage\ReusableBlockWriterInterface;

/**
 * Lists the reusable blocks available to the inserter, and turns a selection made in the
 * editor into a new one.
 */
final class ReusableBlockController
{
    public function __construct(
        private readonly ReusableBlockProviderInterface $provider,
        private readonly ReusableBlockWriterInterface $writer,
        private readonly EditorAccessCheckerInterface $accessChecker,
    ) {
    }

    #[Route(path: '/reusable', name: 'web_systems_gutenberg_reusable', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->accessChecker->assertGranted();

        $items = [];

        foreach ($this->provider->listReferences() as $reference => $title) {
            $items[] = ['ref' => $reference, 'title' => $title];
        }

        return new JsonResponse(['items' => $items]);
    }

    #[Route(path: '/reusable', name: 'web_systems_gutenberg_reusable_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->accessChecker->assertGranted();

        try {
            $payload = $request->toArray();
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException('Malformed JSON payload.', $exception);
        }

        $title = \is_string($payload['title'] ?? null) ? $payload['title'] : '';
        $content = \is_string($payload['content'] ?? null) ? $payload['content'] : '';

        if ('' === trim($content)) {
            throw new BadRequestHttpException('A reusable block needs some content.');
        }

        try {
            return new JsonResponse($this->writer->create($title, $content), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_IMPLEMENTED);
        }
    }
}
