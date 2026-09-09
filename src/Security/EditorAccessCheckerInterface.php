<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Security;

/**
 * Guards the editor's back-channel endpoints (preview, media, reusable blocks).
 *
 * Kept as an interface so that applications with unusual authorisation rules can plug their
 * own check in without the bundle depending on SecurityBundle.
 */
interface EditorAccessCheckerInterface
{
    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException when access is denied
     */
    public function assertGranted(): void;
}
