<?php

namespace App\Controller\Admin;

use App\Service\LegacyDispatcher;
use App\Service\OmekaApiService;
use App\Service\OmekaAdminFormService;
use App\Service\OmekaBrowseService;
use App\Service\LegacyViewRenderer;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/item')]
final class ItemController extends AbstractController
{
    public function __construct(
        private readonly OmekaApiService $api,
        private readonly OmekaAdminFormService $adminFormService,
        private readonly OmekaBrowseService $browseService,
        private readonly LegacyViewRenderer $legacyViewRenderer,
        private readonly LegacyDispatcher $legacyDispatcher,
    ) {
    }

    #[Route('', name: 'app_admin_item_browse')]
    #[Template('admin/browse.html.twig')]
    public function browse(Request $request): array
    {
        $query = $request->query->all();

        return [
            'result' => $this->browseService->browse('items', $query),
        ];
    }

    #[Route('/add', name: 'app_admin_item_add')]
    public function add(Request $request): Response
    {
        return $this->legacyDispatcher->dispatch($request, 'item', 'add');
    }

    #[Route('/{itemId}', name: 'app_admin_item_show_short', requirements: ['itemId' => '\\d+'])]
    public function showShort(int $itemId): Response
    {
        return $this->redirectToRoute('app_admin_item_show', ['itemId' => $itemId]);
    }

    #[Route('/{itemId}/show', name: 'app_admin_item_show', requirements: ['itemId' => '\\d+'])]
    #[Template('admin/item/show.html.twig')]
    public function show(Request $request, int $itemId): Response|array
    {
        $item = $this->api->read('items', $itemId)->getContent();

        // For sidebar/details view, return just the content without layout
        if ($request->query->has('show-details')) {
            return $this->render('admin/item/show-details.html.twig', [
                'item' => $item,
            ]);
        }

        return [
            'item' => $item,
        ];
    }

    #[Route('/{itemId}/edit', name: 'app_admin_item_edit', requirements: ['itemId' => '\\d+'])]
    #[Template('admin/item/edit.html.twig')]
    public function edit(Request $request, int $itemId): Response|array
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
            if ($this->adminFormService->validateForm($form, $data)) {
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

        return [
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
        ];
    }

    #[Route('/{itemId}/delete', name: 'app_admin_item_delete', requirements: ['itemId' => '\\d+'], methods: ['GET', 'POST'])]
    public function delete(Request $request, int $itemId): Response
    {
        if ($request->isMethod('POST')) {
            // Handle direct delete from sidebar form
            $token = $request->request->get('_token');
            if ($this->isCsrfTokenValid('delete_item', $token)) {
                $this->api->getApiManager()->delete('items', $itemId);
                $this->addFlash('success', 'Item successfully deleted');
                return $this->redirectToRoute('app_admin_item_browse');
            }
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_admin_item_edit', ['itemId' => $itemId]);
        }

        // GET request - show delete confirmation in sidebar (without layout)
        $item = $this->api->read('items', $itemId)->getContent();
        return $this->render('admin/item/delete-confirm.html.twig', [
            'item' => $item,
        ]);
    }
}
