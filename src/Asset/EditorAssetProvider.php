<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Asset;

use Symfony\Component\Asset\Packages;

/**
 * Resolves the URLs of the editor and front-end assets shipped with the bundle, honouring the
 * application's asset versioning strategy when symfony/asset is installed.
 */
final class EditorAssetProvider
{
    /**
     * @param list<string> $editorStyles
     * @param list<string> $editorScripts
     * @param list<string> $frontStyles
     * @param list<string> $canvasStyles stylesheets injected inside the editor canvas iframe
     */
    public function __construct(
        private readonly array $editorStyles,
        private readonly array $editorScripts,
        private readonly array $frontStyles,
        private readonly array $canvasStyles = [],
        private readonly ?Packages $packages = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public function editorStyles(): array
    {
        return array_map($this->url(...), $this->editorStyles);
    }

    /**
     * @return list<string>
     */
    public function editorScripts(): array
    {
        return array_map($this->url(...), $this->editorScripts);
    }

    /**
     * @return list<string>
     */
    public function frontStyles(): array
    {
        return array_map($this->url(...), $this->frontStyles);
    }

    /**
     * @return list<string>
     */
    public function canvasStyles(): array
    {
        return $this->resolve($this->canvasStyles);
    }

    /**
     * Turns configured paths into URLs, leaving absolute ones untouched.
     *
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function resolve(array $paths): array
    {
        return array_map($this->url(...), $paths);
    }

    private function url(string $path): string
    {
        if (preg_match('#^(?:https?:)?//#', $path)) {
            return $path;
        }

        return $this->packages?->getUrl($path) ?? '/'.ltrim($path, '/');
    }
}
