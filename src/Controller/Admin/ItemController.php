<?php

namespace App\Controller\Admin;

use App\Service\OmekaApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony/admin/item')]
final class ItemController extends AbstractController
{
    public function __construct(private readonly OmekaApiService $api)
    {
    }

    #[Route('/{itemId}/show', name: 'app_admin_item_show', requirements: ['itemId' => '\\d+'])]
    public function show(int $itemId): Response
    {
        $item = $this->api->read('items', $itemId)->getContent();

        return $this->render('admin/item/show.html.twig', [
            'item' => $item,
            'legacy_route' => 'admin/id',
            'legacy_params' => ['controller' => 'item', 'action' => 'show', 'id' => $itemId],
        ]);
    }

    #[Route('/{itemId}/edit', name: 'app_admin_item_edit', requirements: ['itemId' => '\\d+'])]
    public function edit(int $itemId): Response
    {
        $item = $this->api->read('items', $itemId)->getContent();
        $titleValue = $item->displayTitle() ?? '';
        $descriptionValue = $item->value('dcterms:description')?->value() ?? '';
        $isPublic = $item->isPublic();

        $form = $this->createFormBuilder([
            'title' => $titleValue,
            'description' => $descriptionValue,
            'is_public' => $isPublic,
        ])
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('is_public', CheckboxType::class, [
                'required' => false,
            ])
            ->getForm();

        return $this->render('admin/item/edit.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
            'legacy_route' => 'admin/id',
            'legacy_params' => ['controller' => 'item', 'action' => 'edit', 'id' => $itemId],
        ]);
    }

    #[Route('/{itemId}/delete', name: 'app_admin_item_delete', requirements: ['itemId' => '\\d+'])]
    public function delete(int $itemId): Response
    {
        $item = $this->api->read('items', $itemId)->getContent();

        return $this->render('admin/item/delete.html.twig', [
            'item' => $item,
            'legacy_route' => 'admin/id',
            'legacy_params' => ['controller' => 'item', 'action' => 'delete', 'id' => $itemId],
        ]);
    }
}
