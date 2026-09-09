<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use WebSystems\GutenbergBundle\Block\Block;
use WebSystems\GutenbergBundle\Parser\BlockParser;
use WebSystems\GutenbergBundle\Registry\BlockTypeRegistryInterface;
use WebSystems\GutenbergBundle\Renderer\ContentRendererInterface;
use WebSystems\GutenbergBundle\Renderer\RenderOptions;
use WebSystems\GutenbergBundle\Security\EditorAccessCheckerInterface;

/**
 * Renders block content server-side so the editor can preview it, the same way WordPress'
 * ServerSideRender component works.
 *
 * Two payload shapes are accepted:
 *  - {"name": "app/hero", "attributes": {…}} renders one PHP-defined block type;
 *  - {"markup": "<!-- wp:block {\"ref\":3} /-->"} renders arbitrary block markup, which is how
 *    the editor previews reusable blocks it cannot expand on its own.
 */
final class BlockPreviewController
{
    public function __construct(
        private readonly ContentRendererInterface $renderer,
        private readonly BlockTypeRegistryInterface $registry,
        private readonly BlockParser $parser,
        private readonly EditorAccessCheckerInterface $accessChecker,
    ) {
    }

    #[Route(path: '/preview', name: 'web_systems_gutenberg_preview', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $this->accessChecker->assertGranted();

        try {
            $payload = $request->toArray();
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException('Malformed JSON payload.', $exception);
        }

        try {
            $html = \is_string($payload['markup'] ?? null)
                ? $this->renderer->render($payload['markup'], RenderOptions::preview())
                : $this->renderBlockType($payload);
        } catch (BadRequestHttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            // A broken template must not break the editor; show the reason in the canvas instead.
            return new JsonResponse(['html' => '', 'error' => $exception->getMessage()]);
        }

        return new JsonResponse(['html' => $html]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderBlockType(array $payload): string
    {
        $name = \is_string($payload['name'] ?? null)
            ? $payload['name']
            : throw new BadRequestHttpException('Either "name" or "markup" is required.');

        if (!$this->registry->has($name)) {
            throw new BadRequestHttpException(\sprintf('Unknown block type "%s".', $name));
        }

        $attributes = \is_array($payload['attributes'] ?? null) ? $payload['attributes'] : [];
        $innerBlocks = $this->parser->parse(\is_string($payload['innerHtml'] ?? null) ? $payload['innerHtml'] : '');

        return $this->renderer->renderBlock(Block::create($name, $attributes, $innerBlocks), RenderOptions::preview());
    }
}
