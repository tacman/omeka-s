<?php

namespace App\Controller\Admin;

use App\Service\LegacyDispatcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/job')]
final class JobController extends AbstractController
{
    public function __construct(
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_job_browse')]
    public function browse(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'job', 'browse');
    }

    #[Route('/{id}', name: 'app_admin_job_show', requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'job', 'show', ['id' => $id]);
    }
}
