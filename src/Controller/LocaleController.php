<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony')]
final class LocaleController extends AbstractController
{
    #[Route('/locale/{_locale}', name: 'app_set_locale', requirements: ['_locale' => '[a-zA-Z_\-]+'])]
    public function setLocale(Request $request, string $_locale): RedirectResponse
    {
        $request->getSession()?->set('_locale', $_locale);

        return new RedirectResponse($request->headers->get('referer') ?? '/symfony/admin');
    }
}
