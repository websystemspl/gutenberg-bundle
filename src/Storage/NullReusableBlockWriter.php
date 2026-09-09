<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Storage;

/**
 * Used when no ORM is available: saving is refused with an explanation instead of failing
 * somewhere deeper.
 */
final class NullReusableBlockWriter implements ReusableBlockWriterInterface
{
    public function create(string $title, string $content): array
    {
        throw new \RuntimeException('Reusable blocks need a Doctrine entity manager; install doctrine/orm to enable them.');
    }
}
