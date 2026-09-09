<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use WebSystems\GutenbergBundle\Media\MediaException;
use WebSystems\GutenbergBundle\Media\MediaItem;
use WebSystems\GutenbergBundle\Media\MediaStorageInterface;
use WebSystems\GutenbergBundle\Security\EditorAccessCheckerInterface;

/**
 * Minimal media library backing the editor's image controls.
 */
final class MediaController
{
    public function __construct(
        private readonly MediaStorageInterface $storage,
        private readonly EditorAccessCheckerInterface $accessChecker,
    ) {
    }

    #[Route(path: '/media', name: 'web_systems_gutenberg_media', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->accessChecker->assertGranted();

        return new JsonResponse(['items' => array_map(static fn (MediaItem $item): array => $item->toArray(), $this->storage->list())]);
    }

    #[Route(path: '/media', name: 'web_systems_gutenberg_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $this->accessChecker->assertGranted();

        $file = $request->files->get('file') ?? throw new BadRequestHttpException('No file was uploaded under the "file" key.');

        try {
            return new JsonResponse($this->storage->upload($file)->toArray(), Response::HTTP_CREATED);
        } catch (MediaException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route(path: '/media/{id}', name: 'web_systems_gutenberg_media_delete', methods: ['DELETE'], requirements: ['id' => '.+'])]
    public function delete(string $id): JsonResponse
    {
        $this->accessChecker->assertGranted();

        try {
            $this->storage->delete($id);
        } catch (MediaException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
