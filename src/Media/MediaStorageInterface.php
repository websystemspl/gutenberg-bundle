<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Media;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Where images picked in the editor live.
 *
 * The default implementation writes to the public filesystem; swap in S3, Flysystem or an
 * existing DAM by registering another implementation.
 */
interface MediaStorageInterface
{
    /**
     * @throws MediaException when the file is rejected (size, mime type, unwritable target)
     */
    public function upload(UploadedFile $file): MediaItem;

    /**
     * @return list<MediaItem> newest first
     */
    public function list(int $limit = 100): array;

    public function delete(string $id): void;
}
