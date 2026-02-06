<?php

declare(strict_types=1);

namespace App\Service;

use Laminas\Http\PhpEnvironment\Request as LaminasRequest;
use Laminas\Http\PhpEnvironment\Response as LaminasResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Dispatch to a legacy controller action and return Symfony response.
     *
     * @param string $controller Controller name (e.g., 'item', 'item-set', 'index')
     * @param string $action Action name (e.g., 'edit', 'add')
     * @param array $params Route parameters (e.g., ['id' => 1])
     * @param string $namespace Controller namespace segment (e.g., 'Admin', 'SiteAdmin')
     */
    public function dispatch(Request $request, string $controller, string $action, array $params = [], string $namespace = 'Admin'): Response
    {
        // Ensure the legacy Omeka auth service has an identity before dispatching
        $this->authService->ensureIdentity();

        $app = $this->legacyApp->getApplication();
        $serviceManager = $app->getServiceManager();

        // Verify auth identity is set
        $auth = $serviceManager->get('Omeka\AuthenticationService');
        $hasIdentity = $auth->hasIdentity();
        $identityEmail = $hasIdentity ? $auth->getIdentity()->getEmail() : '(none)';
        $this->logger->info('[LegacyDispatcher] {method} {controller}/{action} — auth: {hasIdentity} ({email})', [
            'method' => $request->getMethod(),
            'controller' => $controller,
            'action' => $action,
            'hasIdentity' => $hasIdentity ? 'yes' : 'no',
            'email' => $identityEmail,
        ]);

        // Get the controller manager and load the controller.
        // Omeka registers controllers without the "Controller" suffix, e.g.
        // 'Omeka\Controller\Admin\Item' not 'Omeka\Controller\Admin\ItemController'
        $controllerManager = $serviceManager->get('ControllerManager');
        $controllerName = 'Omeka\\Controller\\' . $namespace . '\\' . $this->dashToCamelCase($controller);

        if (!$controllerManager->has($controllerName)) {
            $this->logger->error('[LegacyDispatcher] Controller not found: {name}', ['name' => $controllerName]);
            return new Response(
                'Controller not found: ' . $controllerName,
                Response::HTTP_NOT_FOUND
            );
        }

        $controllerInstance = $controllerManager->get($controllerName);

        // Create a route match with all the parameters the legacy system expects.
        // __ADMIN__ is checked by Omeka\Mvc\Status::isAdminRequest()
        // __NAMESPACE__ and __CONTROLLER__ are used by the Laminas URL helper
        $routeMatch = new RouteMatch([
            'controller' => $controllerName,
            'action' => $action,
            '__NAMESPACE__' => 'Omeka\\Controller\\' . $namespace,
            '__CONTROLLER__' => $controller,
            '__ADMIN__' => true,
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

        // Set RouteMatch and Router on the application's MVC event and the Url
        // view helper so that templates and controller plugins can generate URLs.
        $router = $serviceManager->get('Router');
        $app->getMvcEvent()->setRouteMatch($routeMatch);
        $app->getMvcEvent()->setRouter($router);
        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        $viewHelperManager->get('url')->setRouteMatch($routeMatch);
        $viewHelperManager->get('url')->setRouter($router);

        // Ensure Omeka\Status recognises this as an admin request. The Status
        // service caches isAdminRequest, so we must force it.
        $status = $serviceManager->get('Omeka\Status');
        if (method_exists($status, 'setIsAdminRequest')) {
            $status->setIsAdminRequest(true);
        } else {
            // Force the cached value via reflection if no setter exists
            $ref = new \ReflectionProperty($status, 'isAdminRequest');
            $ref->setValue($status, true);
        }

        // Create MVC event for the controller
        $event = new MvcEvent();
        $event->setApplication($app);
        $event->setRequest($laminasRequest);
        $event->setResponse(new LaminasResponse());
        $event->setRouteMatch($routeMatch);
        // The Url controller plugin and redirect plugin require a router on the event.
        $event->setRouter($serviceManager->get('Router'));

        $controllerInstance->setEvent($event);

        // CRITICAL: Set the request and response directly on the controller.
        // The controller's getRequest() returns $this->request, which is normally
        // set by dispatch(). Since we call the action method directly (bypassing
        // dispatch() to avoid triggering EVENT_DISPATCH listeners), we must set
        // the protected properties explicitly.
        $setProps = \Closure::bind(function ($request, $response) {
            $this->request = $request;
            $this->response = $response;
        }, $controllerInstance, \Laminas\Mvc\Controller\AbstractController::class);
        $setProps($laminasRequest, $event->getResponse());

        // Ensure the Laminas session is properly started so CSRF tokens persist.
        // The Laminas SessionManager uses a different session name and DB-backed
        // save handler. We must ensure it is active before form operations.
        $this->ensureLaminasSession($serviceManager);

        // Dispatch the action
        $actionMethod = $this->getMethodFromAction($action);

        if ($request->isMethod('POST')) {
            $postKeys = array_keys($request->request->all());
            $this->logger->info('[LegacyDispatcher] POST {controller}/{action} — post keys: [{keys}], laminas method: {laminasMethod}', [
                'controller' => $controller,
                'action' => $action,
                'keys' => implode(', ', $postKeys),
                'laminasMethod' => $laminasRequest->getMethod(),
            ]);

            // Check if the Laminas request correctly sees this as POST
            $this->logger->debug('[LegacyDispatcher] Laminas request isPost: {isPost}, post count: {postCount}', [
                'isPost' => $laminasRequest->isPost() ? 'yes' : 'no',
                'postCount' => count($laminasRequest->getPost()),
            ]);
        }

        try {
            $result = $controllerInstance->$actionMethod();
        } catch (\Throwable $e) {
            $this->logger->error('[LegacyDispatcher] Exception in {controller}/{action}: {message}', [
                'controller' => $controller,
                'action' => $action,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Log result type
        $resultType = match (true) {
            $result instanceof LaminasResponse => sprintf('LaminasResponse(%d, headers: %s)', $result->getStatusCode(), implode(', ', array_keys($result->getHeaders()->toArray()))),
            $result instanceof ViewModel => sprintf('ViewModel(template: %s, terminate: %s, vars: [%s])', $result->getTemplate() ?: '(default)', $result->terminate() ? 'yes' : 'no', implode(', ', array_keys($result->getVariables()->getArrayCopy()))),
            default => get_debug_type($result),
        };
        $this->logger->info('[LegacyDispatcher] {method} {controller}/{action} result: {resultType}', [
            'method' => $request->getMethod(),
            'controller' => $controller,
            'action' => $action,
            'resultType' => $resultType,
        ]);

        // If a form re-rendered after POST, extract and log validation errors
        if ($request->isMethod('POST') && $result instanceof ViewModel) {
            $vars = $result->getVariables();
            $form = $vars['form'] ?? null;
            if ($form instanceof \Laminas\Form\Form && $form->hasValidated() && !$form->isValid()) {
                $messages = $form->getMessages();
                $this->logger->warning('[LegacyDispatcher] Form validation FAILED for {controller}/{action}: {errors}', [
                    'controller' => $controller,
                    'action' => $action,
                    'errors' => json_encode($messages, JSON_PRETTY_PRINT),
                ]);
            } elseif ($form instanceof \Laminas\Form\Form) {
                // Form exists but may not have been validated (e.g. isPost check failed)
                $this->logger->info('[LegacyDispatcher] Form re-rendered for {controller}/{action} — hasValidated: {validated}', [
                    'controller' => $controller,
                    'action' => $action,
                    'validated' => $form->hasValidated() ? 'yes' : 'no',
                ]);
            }
        }

        // Handle the result
        if ($result instanceof LaminasResponse) {
            $statusCode = $result->getStatusCode();
            $headers = $result->getHeaders()->toArray();
            $this->logger->info('[LegacyDispatcher] Returning LaminasResponse: status={status}, location={location}', [
                'status' => $statusCode,
                'location' => $headers['Location'] ?? '(none)',
            ]);
            return new Response(
                $result->getContent(),
                $statusCode,
                $headers
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

            // Render any flash messages from the legacy Omeka messenger.
            // In stock Omeka, the layout template calls $this->messages().
            // Since we use our own Twig layout, we render them here.
            $messagesHtml = $renderer->messages();

            if ($messagesHtml) {
                $this->logger->info('[LegacyDispatcher] Flash messages present for {controller}/{action}', [
                    'controller' => $controller,
                    'action' => $action,
                ]);
            }

            // Terminal views (sidebars, AJAX fragments) return bare HTML without layout
            if ($result->terminate()) {
                return new Response($messagesHtml . $content);
            }

            // Capture scripts/styles that .phtml templates registered via headScript()/headLink()
            $headScripts = $renderer->headScript() ? $renderer->headScript()->toString() : '';
            $headLinks = $renderer->headLink() ? $renderer->headLink()->toString() : '';

            // Wrap in Symfony's admin layout via Twig
            $title = ucwords(str_replace('-', ' ', $controller)) . ' - ' . ucfirst($action);
            $html = $this->twig->render('admin/legacy_wrapper.html.twig', [
                'title' => $title,
                'content' => $messagesHtml . $content,
                'legacy_head_scripts' => $headScripts,
                'legacy_head_links' => $headLinks,
            ]);

            return new Response($html);
        }

        $this->logger->error('[LegacyDispatcher] Unexpected result type from {controller}/{action}: {type}', [
            'controller' => $controller,
            'action' => $action,
            'type' => get_debug_type($result),
        ]);
        return new Response('Unexpected result from controller', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Ensure the Laminas session is properly started.
     *
     * The Laminas session uses a different session name (md5(OMEKA_PATH)) and
     * may use a DB save handler. Since Symfony also manages sessions, we need
     * to ensure both can coexist. The Laminas CSRF validator stores tokens in
     * the Laminas session, so it must be active for form submissions to work.
     */
    private function ensureLaminasSession(\Laminas\ServiceManager\ServiceManager $serviceManager): void
    {
        try {
            $sessionManager = \Laminas\Session\Container::getDefaultManager();
            if ($sessionManager && !$sessionManager->isValid()) {
                $this->logger->warning('[LegacyDispatcher] Laminas session is not valid, attempting to start');
                $sessionManager->start();
            }
            if ($sessionManager) {
                $this->logger->debug('[LegacyDispatcher] Laminas session: id={id}, name={name}, isValid={valid}', [
                    'id' => $sessionManager->getId() ?: '(none)',
                    'name' => $sessionManager->getName(),
                    'valid' => $sessionManager->isValid() ? 'yes' : 'no',
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[LegacyDispatcher] Laminas session check failed: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
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
