<?php

namespace App\Controller\Admin;

use App\Service\LegacyDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/setting')]
final class SettingController extends AbstractController
{
    public function __construct(
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_setting_browse')]
    public function browse(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'setting', 'browse');
    }
}
