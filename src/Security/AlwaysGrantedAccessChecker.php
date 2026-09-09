<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Security;

/**
 * Default when no `access_role` is configured: authorisation is left entirely to the
 * application's firewall and access_control rules.
 */
final class AlwaysGrantedAccessChecker implements EditorAccessCheckerInterface
{
    public function assertGranted(): void
    {
    }
}
