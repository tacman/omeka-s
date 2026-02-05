<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony')]
final class LocaleController extends AbstractController
{
    #[Route('/locale/{newLocale}', name: 'app_set_locale', requirements: ['newLocale' => '[a-zA-Z_\-]+'])]
    public function setLocale(Request $request, string $newLocale): RedirectResponse
    {
        $supportedLocales = $this->getParameter('app.supported_locales');
        if (!in_array($newLocale, $supportedLocales, true)) {
            throw new NotFoundHttpException(sprintf('Locale not supported: %s', $newLocale));
        }

        $request->getSession()?->set('_locale', $newLocale);

        $redirectTo = (string) $request->query->get('redirect_to', '');
        if ($redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return new RedirectResponse($redirectTo);
        }

        return $this->redirectToRoute('app_admin', ['_locale' => $newLocale]);
    }
}
