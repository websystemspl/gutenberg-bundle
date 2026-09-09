<?php

declare(strict_types=1);

namespace WebSystems\GutenbergBundle\Security;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Enforces the role configured under `web_systems_gutenberg.security.access_role`.
 */
final class RoleAccessChecker implements EditorAccessCheckerInterface
{
    public function __construct(
        private readonly string $role,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
    ) {
    }

    public function assertGranted(): void
    {
        if (null === $this->authorizationChecker) {
            throw new AccessDeniedHttpException(\sprintf('Role "%s" is required for the block editor endpoints, but SecurityBundle is not available to check it.', $this->role));
        }

        if (!$this->authorizationChecker->isGranted($this->role)) {
            throw new AccessDeniedHttpException(\sprintf('Role "%s" is required for the block editor endpoints.', $this->role));
        }
    }
}
