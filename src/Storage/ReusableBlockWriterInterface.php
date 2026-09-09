<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

/**
 * Creates reusable blocks out of content selected in the editor.
 *
 * Kept apart from ReusableBlockProviderInterface so that read-only consumers — the renderer,
 * the editor configuration — never depend on the ability to write.
 */
interface ReusableBlockWriterInterface
{
    /**
     * @throws \RuntimeException when reusable blocks cannot be stored
     *
     * @return array{ref: int|string, title: string} the reference the editor should insert
     */
    public function create(string $title, string $content): array;
}
