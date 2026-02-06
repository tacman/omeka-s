<?php

namespace App\Controller\Admin;

use App\Service\OmekaApiService;
use App\Service\OmekaAdminFormService;
use App\Service\LegacyViewRenderer;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/symfony/admin/item')]
final class ItemController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaAdminFormService $adminFormService,
        private readonly LegacyViewRenderer $legacyViewRenderer,
    ) {
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
    public function edit(Request $request, int $itemId): Response
    {
        $item = $this->api->read('items', $itemId)->getContent();
        $form = $this->adminFormService->createResourceForm(
            $item,
            $this->generateUrl('app_admin_item_edit', ['itemId' => $itemId]),
            'edit-item',
        );

        $errors = null;
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $data = $this->adminFormService->mergeValuesJson($data);
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api->getApiManager()->update('items', $itemId, $data, []);
                if ($response) {
                    $this->addFlash('success', 'Item successfully updated');
                    return $this->redirectToRoute('app_admin_item_show', ['itemId' => $itemId]);
                }
            } else {
                $errors = $form->getMessages();
            }
        }

        $mediaForms = $this->adminFormService->getMediaForms();
        $sectionNavHtml = $this->legacyViewRenderer->sectionNav([
            'resource-values' => 'Values',
            'item-media' => 'Media',
            'item-sets' => 'Item sets',
            'sites' => 'Sites',
            'advanced-settings' => 'Advanced',
        ], 'view.edit.section_nav');
        $mediaHtml = $this->legacyViewRenderer->render('omeka/admin/item/manage-media', [
            'mediaForms' => $mediaForms,
            'resource' => $item,
        ]);
        $itemSetsHtml = $this->legacyViewRenderer->render('omeka/admin/item/manage-item-sets', [
            'item' => $item,
        ]);
        $sitesHtml = $this->legacyViewRenderer->render('omeka/admin/item/manage-sites', [
            'item' => $item,
        ]);
        $resourceFormTemplatesHtml = $this->legacyViewRenderer->render('common/resource-form-templates');
        $settings = $this->adminFormService->getSettings();

        $uploadForm = $this->createFormBuilder(null, [
            'method' => 'POST',
            'csrf_protection' => false,
        ])
            ->add('file', DropzoneType::class, [
                'label' => 'Upload media',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-controller' => 'symfony--ux-dropzone--dropzone',
                    'data-dropzone-url-value' => $this->generateUrl('app_admin_media_upload'),
                ],
            ])
            ->getForm();

        return $this->render('admin/item/edit.html.twig', [
            'item' => $item,
            'form' => $form,
            'form_open' => $this->legacyViewRenderer->formOpen($form),
            'form_close' => $this->legacyViewRenderer->formClose(),
            'section_nav_html' => $sectionNavHtml,
            'media_html' => $mediaHtml,
            'item_sets_html' => $itemSetsHtml,
            'sites_html' => $sitesHtml,
            'resource_form_templates_html' => $resourceFormTemplatesHtml,
            'cancel_button_html' => $this->legacyViewRenderer->cancelButton(),
            'delete_button_html' => $item->userIsAllowed('delete')
                ? $this->legacyViewRenderer->hyperlink('', '#', ['class' => 'delete button o-icon-delete'])
                : '',
            'default_to_private_items' => (bool) $settings->get('default_to_private_items', false),
            'upload_form' => $uploadForm->createView(),
            'errors' => $errors,
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
