<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Media;

/**
 * One entry of the media library, in the shape the editor's image control expects.
 */
final class MediaItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly string $alt = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'name' => $this->name,
            'mime' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
        ];
    }
}
