<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Media;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores uploads under a directory of the public document root and serves them straight from
 * the web server.
 */
final class FilesystemMediaStorage implements MediaStorageInterface
{
    /**
     * @param string       $directory    absolute path to write into
     * @param string       $publicPrefix URL prefix that maps onto $directory
     * @param list<string> $allowedMimeTypes
     * @param int          $maxFileSize  in bytes
     */
    public function __construct(
        private readonly string $directory,
        private readonly string $publicPrefix,
        private readonly array $allowedMimeTypes,
        private readonly int $maxFileSize,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function upload(UploadedFile $file): MediaItem
    {
        if (!$file->isValid()) {
            throw new MediaException($file->getErrorMessage());
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new MediaException(\sprintf('The file is larger than the allowed %d bytes.', $this->maxFileSize));
        }

        $mimeType = (string) $file->getMimeType();

        if ([] !== $this->allowedMimeTypes && !\in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new MediaException(\sprintf('Files of type "%s" are not allowed.', $mimeType));
        }

        $this->filesystem->mkdir($this->directory);
        $name = $this->uniqueName($file);
        $file->move($this->directory, $name);

        return $this->describe($this->directory.'/'.$name);
    }

    public function list(int $limit = 100): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        $finder = (new Finder())->files()->in($this->directory)->sortByModifiedTime()->reverseSorting();
        $items = [];

        foreach ($finder as $file) {
            $items[] = $this->describe($file->getPathname());

            if (\count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    public function delete(string $id): void
    {
        $path = $this->directory.'/'.basename($id);

        if (str_contains($id, '..') || !is_file($path)) {
            throw new MediaException(\sprintf('Media "%s" does not exist.', $id));
        }

        $this->filesystem->remove($path);
    }

    private function describe(string $path): MediaItem
    {
        $name = basename($path);
        $size = @getimagesize($path);

        return new MediaItem(
            $name,
            rtrim($this->publicPrefix, '/').'/'.rawurlencode($name),
            $name,
            (string) (mime_content_type($path) ?: 'application/octet-stream'),
            false !== $size ? $size[0] : null,
            false !== $size ? $size[1] : null,
        );
    }

    private function uniqueName(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension() ?: 'bin';
        $base = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $base = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $base));
        $base = trim($base, '-') ?: 'file';

        return \sprintf('%s-%s.%s', substr($base, 0, 60), bin2hex(random_bytes(4)), $extension);
    }
}
