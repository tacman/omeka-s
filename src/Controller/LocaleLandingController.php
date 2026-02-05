<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleLandingController extends AbstractController
{
    #[Route('/', name: 'app_locale_landing')]
    public function landing(Request $request): Response
    {
        $supportedLocales = $this->getParameter('app.supported_locales');
        $sessionLocale = $request->getSession()?->get('_locale');
        if ($sessionLocale && in_array($sessionLocale, $supportedLocales, true)) {
            return $this->redirectToRoute('app_locale_root', ['_locale' => $sessionLocale]);
        }

        $preferredLocale = $request->getPreferredLanguage($supportedLocales);
        if ($preferredLocale) {
            return $this->redirectToRoute('app_locale_root', ['_locale' => $preferredLocale]);
        }

        if (count($supportedLocales) === 1) {
            return $this->redirectToRoute('app_locale_root', ['_locale' => $supportedLocales[0]]);
        }

        return $this->render('app/locale_landing.html.twig', [
            'locales' => $supportedLocales,
        ]);
    }

}
