<?php

namespace App\Controller;

use App\Service\LaminasRouterService;
use App\Tenant\TenantContext;
use Omeka\Entity\Site;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony')]
final class AppController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly LaminasRouterService $laminasRouter,
    ) {
    }

    #[Route('/app', name: 'app_app')]
    public function index(): Response
    {
        $sites = $this->tenantContext->getRepository(Site::class)->findBy([], ['title' => 'ASC']);

        return $this->render('app/index.html.twig', [
            'sites' => $sites,
        ]);
    }

    #[Route('/legacy/{routeName}', name: 'app_legacy_proxy', requirements: ['routeName' => '.+'])]
    public function legacyProxy(Request $request, string $routeName): Response
    {
        $params = $request->query->all();
        $url = $this->laminasRouter->url($routeName, $params);

        return new RedirectResponse($url);
    }

    #[Route('/admin/site/s/{siteSlug}', name: 'app_site_admin', requirements: ['siteSlug' => '[a-zA-Z0-9_-]+'])]
    public function siteAdmin(string $siteSlug): Response
    {
        $site = $this->tenantContext->getRepository(Site::class)->findOneBy(['slug' => $siteSlug]);
        if (!$site) {
            throw new NotFoundHttpException(sprintf('Site not found: %s', $siteSlug));
        }

        return $this->render('app/site_admin.html.twig', [
            'site' => $site,
        ]);
    }
}
