<?php

namespace App\Controller\Admin;

use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/media')]
final class MediaUploadController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'default.storage')] private readonly FilesystemOperator $storage,
    ) {
    }

    #[Route('/upload', name: 'app_admin_media_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Missing file'], 400);
        }

        $filename = sprintf('uploads/%s_%s', uniqid('', true), $file->getClientOriginalName());
        $this->storage->write($filename, $file->getContent());

        return new JsonResponse([
            'path' => $filename,
            'originalName' => $file->getClientOriginalName(),
        ]);
    }
}
