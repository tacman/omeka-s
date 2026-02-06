<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        // Redirect root to admin dashboard
        return $this->redirectToRoute('app_admin');
    }

    /**
     * Catch-all for routes not yet implemented in Symfony.
     * Shows a clear message instead of redirecting to legacy.
     */
    #[Route('/legacy/{routeName}', name: 'app_legacy_proxy', requirements: ['routeName' => '.+'], priority: -1000)]
    public function legacyProxy(Request $request, string $routeName): Response
    {
        return $this->render('app/not_implemented.html.twig', [
            'route_name' => $routeName,
            'params' => $request->query->all(),
            'request_uri' => $request->getRequestUri(),
        ]);
    }
}
