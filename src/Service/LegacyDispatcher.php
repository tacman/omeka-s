<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\Http\PhpEnvironment\Request as LaminasRequest;
use Laminas\Http\PhpEnvironment\Response as LaminasResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Dispatches requests to the legacy Omeka/Laminas application.
 */
final class LegacyDispatcher
{
    public function __construct(
        private readonly LegacyOmekaApplication $legacyApp,
        private readonly LegacyViewRenderer $viewRenderer,
        private readonly Environment $twig,
        private readonly LegacyOmekaAuthService $authService,
    ) {
    }

    /**
     * Dispatch to a legacy controller action and return Symfony response.
     *
     * @param string $controller Controller name (e.g., 'item', 'item-set')
     * @param string $action Action name (e.g., 'edit', 'add')
     * @param array $params Route parameters (e.g., ['id' => 1])
     */
    public function dispatch(Request $request, string $controller, string $action, array $params = []): Response
    {
        // Ensure the legacy Omeka auth service has an identity before dispatching
        $this->authService->ensureIdentity();

        $app = $this->legacyApp->getApplication();
        $serviceManager = $app->getServiceManager();

        // Get the controller manager and load the controller
        $controllerManager = $serviceManager->get('ControllerManager');
        $controllerName = 'Omeka\\Controller\\Admin\\' . $this->dashToCamelCase($controller) . 'Controller';

        if (!$controllerManager->has($controllerName)) {
            return new Response(
                'Controller not found: ' . $controllerName,
                Response::HTTP_NOT_FOUND
            );
        }

        $controllerInstance = $controllerManager->get($controllerName);

        // Create a route match
        $routeMatch = new RouteMatch([
            'controller' => $controllerName,
            'action' => $action,
            '__NAMESPACE__' => 'Omeka\\Controller\\Admin',
            '__CONTROLLER__' => $controller,
        ] + $params);
        $routeMatch->setMatchedRouteName(isset($params['id']) ? 'admin/id' : 'admin/default');

        // Create Laminas request from Symfony request
        $laminasRequest = new LaminasRequest();
        $laminasRequest->setUri($request->getUri());
        $laminasRequest->setMethod($request->getMethod());

        if ($request->isMethod('POST')) {
            $laminasRequest->setPost(new \Laminas\Stdlib\Parameters($request->request->all()));
        }
        $laminasRequest->setQuery(new \Laminas\Stdlib\Parameters($request->query->all()));

        // Set RouteMatch on the application's MVC event and the Url view helper
        // so that templates can generate URLs for the current route.
        $app->getMvcEvent()->setRouteMatch($routeMatch);
        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        $viewHelperManager->get('url')->setRouteMatch($routeMatch);

        // Create MVC event for the controller
        $event = new MvcEvent();
        $event->setApplication($app);
        $event->setRequest($laminasRequest);
        $event->setResponse(new LaminasResponse());
        $event->setRouteMatch($routeMatch);

        $controllerInstance->setEvent($event);

        // Dispatch the action
        $actionMethod = $this->getMethodFromAction($action);

        $result = $controllerInstance->$actionMethod();

        // Handle the result
        if ($result instanceof LaminasResponse) {
            // Controller returned a response (redirect)
            return new Response(
                $result->getContent(),
                $result->getStatusCode(),
                $result->getHeaders()->toArray()
            );
        }

        if ($result instanceof ViewModel) {
            // Set template if not already set (Laminas MVC convention)
            if (!$result->getTemplate()) {
                $template = sprintf('omeka/admin/%s/%s', $controller, $action);
                $result->setTemplate($template);
            }

            // Render the legacy .phtml content
            $renderer = $this->viewRenderer->getRenderer();
            $content = $renderer->render($result);

            // Terminal views (sidebars, AJAX fragments) return bare HTML without layout
            if ($result->terminate()) {
                return new Response($content);
            }

            // Capture scripts/styles that .phtml templates registered via headScript()/headLink()
            $headScripts = $renderer->headScript() ? $renderer->headScript()->toString() : '';
            $headLinks = $renderer->headLink() ? $renderer->headLink()->toString() : '';

            // Wrap in Symfony's admin layout via Twig
            $title = ucwords(str_replace('-', ' ', $controller)) . ' - ' . ucfirst($action);
            $html = $this->twig->render('admin/legacy_wrapper.html.twig', [
                'title' => $title,
                'content' => $content,
                'legacy_head_scripts' => $headScripts,
                'legacy_head_links' => $headLinks,
            ]);

            return new Response($html);
        }

        return new Response('Unexpected result from controller', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function dashToCamelCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    private function getMethodFromAction(string $action): string
    {
        return lcfirst($this->dashToCamelCase($action)) . 'Action';
    }
}
