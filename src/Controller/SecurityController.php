<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This should be intercepted by the firewall logout handler.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        // TODO: Implement password reset request (send email with token)
        return $this->render('security/forgot_password.html.twig', [
            'success' => false,
        ]);
    }

    #[Route('/create-password/{key}', name: 'app_create_password', requirements: ['key' => '.+'])]
    public function createPassword(Request $request, string $key): Response
    {
        // TODO: Implement password creation/reset via token
        return $this->render('security/create_password.html.twig', [
            'key' => $key,
        ]);
    }
}
