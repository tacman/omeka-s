<?php

namespace App\Controller;

use App\Tenant\TenantContext;
use Omeka\Entity\Site;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleRootController extends AbstractController
{
    #[Route('/', name: 'app_locale_root')]
    public function index(Request $request, TenantContext $tenantContext): Response
    {
        $supportedLocales = $this->getParameter('app.supported_locales');
        $currentLocale = $request->attributes->get('_locale');
        if (!is_string($currentLocale) || !in_array($currentLocale, $supportedLocales, true)) {
            throw new NotFoundHttpException('Locale not supported.');
        }

        $request->getSession()?->set('_locale', $currentLocale);

        $sites = $tenantContext->getRepository(Site::class)->findBy([], ['title' => 'ASC']);

        return $this->render('app/index.html.twig', [
            'sites' => $sites,
        ]);
    }
}
