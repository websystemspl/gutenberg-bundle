<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Translation;

/**
 * Reads catalogues written by `bin/console gutenberg:translations`, one JSON file per locale.
 *
 * Keeping them on disk rather than inside the package means updating a translation never
 * requires releasing a new version of the bundle.
 */
final class FileTranslationCatalogue implements TranslationCatalogueInterface
{
    /** @var array<string, array<string, mixed>|null> */
    private array $cache = [];

    public function __construct(
        private readonly string $directory,
    ) {
    }

    public function has(string $locale): bool
    {
        return is_file($this->path($locale));
    }

    public function getMessages(string $locale): ?array
    {
        if (\array_key_exists($locale, $this->cache)) {
            return $this->cache[$locale];
        }

        $path = $this->path($locale);

        if (!is_file($path)) {
            return $this->cache[$locale] = null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->cache[$locale] = null;
        }

        return $this->cache[$locale] = \is_array($decoded) ? $decoded : null;
    }

    public function getInstalledLocales(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        $locales = [];

        foreach ((array) glob($this->directory.'/*.json') as $file) {
            $locales[] = basename((string) $file, '.json');
        }

        sort($locales);

        return $locales;
    }

    public function getPath(string $locale): string
    {
        return $this->path($locale);
    }

    private function path(string $locale): string
    {
        // Locale strings reach this class from configuration and URLs alike.
        return $this->directory.'/'.preg_replace('/[^A-Za-z0-9_-]/', '', $locale).'.json';
    }
}
