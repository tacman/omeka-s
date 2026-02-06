<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\LegacyOmekaApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Dev-only authenticator that auto-logs in as the first admin user.
 * This should NEVER be used in production.
 */
final class DevAutoLoginAuthenticator extends AbstractAuthenticator
{
    private ?string $adminEmail = null;

    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Only for admin routes
        if (!str_starts_with($request->getPathInfo(), '/admin')) {
            return false;
        }

        // Don't authenticate if already logged in
        if ($request->hasPreviousSession()) {
            try {
                if ($request->getSession()->has('_security_main')) {
                    return false;
                }
            } catch (\Throwable) {
                // Session may contain corrupt Laminas data; clear it
                $request->getSession()->invalidate();
            }
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $this->getAdminEmail();

        return new SelfValidatingPassport(
            new UserBadge($email)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Continue with the request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Continue anyway in dev
        return null;
    }

    private function getAdminEmail(): string
    {
        if ($this->adminEmail !== null) {
            return $this->adminEmail;
        }

        // Get the first global_admin user from Omeka
        $em = $this->legacyApp->getServiceManager()->get('Omeka\EntityManager');
        $user = $em->getRepository(\Omeka\Entity\User::class)->findOneBy([
            'role' => 'global_admin',
            'isActive' => true,
        ]);

        if ($user) {
            $this->adminEmail = $user->getEmail();
            return $this->adminEmail;
        }

        // Fallback
        $this->adminEmail = 'admin@example.com';
        return $this->adminEmail;
    }
}
