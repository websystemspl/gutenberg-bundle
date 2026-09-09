<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Translation;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Downloads an official WordPress language pack and distils it into one catalogue file.
 *
 * A core pack ships the editor's script translations as dozens of per-file jed documents;
 * merging them yields a single message map that @wordpress/i18n can load in one request.
 */
final class TranslationInstaller
{
    private const VERSION_CHECK = 'https://api.wordpress.org/core/version-check/1.7/';
    private const PACK_URL = 'https://downloads.wordpress.org/translation/core/%s/%s.zip';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly FileTranslationCatalogue $catalogue,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @throws TranslationException when the pack cannot be downloaded or read
     *
     * @return array{locale: string, version: string, messages: int, path: string}
     */
    public function install(string $locale, ?string $version = null): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new TranslationException('The zip PHP extension is required to install language packs.');
        }

        $locale = WordPressLocale::normalize($locale);
        $version ??= $this->latestWordPressVersion();
        $archive = $this->download($locale, $version);

        try {
            $messages = $this->extractMessages($archive);
        } finally {
            $this->filesystem->remove($archive);
        }

        if ([] === $messages) {
            throw new TranslationException(\sprintf('The language pack for "%s" contains no editor translations.', $locale));
        }

        $path = $this->catalogue->getPath($locale);
        $this->filesystem->mkdir(\dirname($path));
        $this->filesystem->dumpFile($path, (string) json_encode($messages, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));

        return ['locale' => $locale, 'version' => $version, 'messages' => \count($messages), 'path' => $path];
    }

    public function latestWordPressVersion(): string
    {
        try {
            $offers = $this->httpClient->request('GET', self::VERSION_CHECK, ['timeout' => 15])->toArray();
        } catch (\Throwable $exception) {
            throw new TranslationException('Could not ask WordPress.org for the current release: '.$exception->getMessage(), previous: $exception);
        }

        return $offers['offers'][0]['current'] ?? throw new TranslationException('WordPress.org did not report a current release.');
    }

    private function download(string $locale, string $version): string
    {
        $url = \sprintf(self::PACK_URL, $version, $locale);
        $target = tempnam(sys_get_temp_dir(), 'wsg-lang-').'.zip';

        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 120]);

            if (200 !== $response->getStatusCode()) {
                throw new TranslationException(\sprintf('WordPress.org has no "%s" language pack for version %s (HTTP %d).', $locale, $version, $response->getStatusCode()));
            }

            $this->filesystem->dumpFile($target, $response->getContent());
        } catch (TranslationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new TranslationException(\sprintf('Downloading %s failed: %s', $url, $exception->getMessage()), previous: $exception);
        }

        return $target;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractMessages(string $archivePath): array
    {
        $zip = new \ZipArchive();

        if (true !== $zip->open($archivePath)) {
            throw new TranslationException('The downloaded language pack is not a readable ZIP archive.');
        }

        $messages = [];

        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = (string) $zip->getNameIndex($i);

                if (!str_ends_with($name, '.json')) {
                    continue;
                }

                $contents = $zip->getFromIndex($i);

                if (false === $contents) {
                    continue;
                }

                try {
                    $document = json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    continue;
                }

                $domain = $document['domain'] ?? 'messages';
                $data = $document['locale_data'][$domain] ?? null;

                if (\is_array($data)) {
                    // The "" entry carries the plural rules; keeping the first one is enough.
                    $messages = [...$data, ...$messages];
                }
            }
        } finally {
            $zip->close();
        }

        return $messages;
    }
}
