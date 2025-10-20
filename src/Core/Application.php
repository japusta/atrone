<?php

namespace App\Core;

use App\Application\Auth\AuthService;
use App\Core\Config\Config;
use App\Core\Database\DatabaseConnection;
use App\Core\Database\PdoDatabaseConnection;
use App\Core\Exception\ApiException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Modules\ModuleRegistry;
use App\Core\Security\TokenGenerator;
use App\Core\Session\SessionManager;
use App\Core\Support\Clock;
use App\Core\Support\Paginator;
use App\Core\Support\PhoneFormatter;
use App\Core\Support\Sanitizer;
use App\Core\Template\PageView;
use App\Core\Template\TemplateRenderer;
use Modules\Plots\Application\PlotService;
use Modules\Plots\Domain\PlotRepository;
use Modules\Users\Application\UserService;
use Modules\Users\Domain\UserRepository;

final class Application
{
    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
        $this->registerServices();
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        $response = $this->handleHttpRequest($request);
        $response->send();
    }

    public function handleHttpRequest(Request $request): Response
    {
        /** @var SessionManager $session */
        $session = $this->container->get(SessionManager::class);
        $session->bootstrapFromCookies($_COOKIE);

        if ($request->getPath() === 'logout') {
            $session->logout();
            return new Response('', 302, ['Location' => '/']);
        }

        /** @var TemplateRenderer $renderer */
        $renderer = $this->container->get(TemplateRenderer::class);

        if (!$session->isAuthenticated()) {
            $view = new PageView('index', 'login');
            $view = $view->withVariables([
                'global' => ['path' => 'login'],
                'offset' => 0,
                'search' => '',
            ]);

            return $view->render($renderer);
        }

        /** @var ModuleRegistry $modules */
        $modules = $this->container->get(ModuleRegistry::class);
        $path = $request->getPath();

        if (!$modules->hasPage($path)) {
            $path = 'plots';
        }

        $result = $modules->handlePage($path, $request);
        if (!$result instanceof PageView) {
            throw new \RuntimeException('Page controller must return a PageView instance.');
        }

        $result = $result->withVariables([
            'global' => ['path' => $path],
        ]);

        return $result->render($renderer);
    }

    public function handleAjaxRequest(array $location, array $payload): JsonResponse
    {
        /** @var SessionManager $session */
        $session = $this->container->get(SessionManager::class);
        $session->bootstrapFromCookies($_COOKIE);

        $department = $location['dpt'] ?? '';
        $action = $location['act'] ?? null;

        if (!$session->isAuthenticated() && $department !== 'auth') {
            return new JsonResponse(['error_msg' => 'Unauthorized']);
        }

        if ($department === 'auth') {
            return $this->handleAuthAjax($action, $payload);
        }

        if ($department === 'search') {
            return $this->handleSearchAjax($action, $payload);
        }

        /** @var ModuleRegistry $modules */
        $modules = $this->container->get(ModuleRegistry::class);
        if ($modules->hasApi($department)) {
            $result = $modules->handleApi($department, $action, $payload);
            return new JsonResponse($result ?? []);
        }

        return new JsonResponse([]);
    }

    private function handleAuthAjax(?string $action, array $payload): JsonResponse
    {
        /** @var TemplateRenderer $renderer */
        $renderer = $this->container->get(TemplateRenderer::class);
        /** @var AuthService $authService */
        $authService = $this->container->get(AuthService::class);

        try {
            if ($action === 'send') {
                $result = $authService->requestCode($payload['phone'] ?? '');
                $html = $renderer->render('login_confirm', $result);

                return new JsonResponse(['html' => $html]);
            }

            if ($action === 'confirm') {
                $result = $authService->confirmCode($payload['phone'] ?? '', $payload['code'] ?? '');
                return new JsonResponse($result);
            }
        } catch (ApiException $exception) {
            return new JsonResponse($exception->toArray());
        }

        return new JsonResponse([]);
    }

    private function handleSearchAjax(?string $action, array $payload): JsonResponse
    {
        $query = (string) ($payload['search'] ?? '');
        $offset = isset($payload['offset']) && is_numeric($payload['offset']) ? (int) $payload['offset'] : 0;
        $type = $payload['type'] ?? $action ?? '';

        if ($type === 'users') {
            /** @var UserService $userService */
            $userService = $this->container->get(UserService::class);
            $data = $userService->getPaginatedList($query, $offset);
            /** @var TemplateRenderer $renderer */
            $renderer = $this->container->get(TemplateRenderer::class);
            $html = $renderer->render('users_table', $data);

            return new JsonResponse([
                'html' => $html,
                'paginator' => $data['paginator'],
            ]);
        }

        if ($type === 'plots') {
            /** @var PlotService $plotService */
            $plotService = $this->container->get(PlotService::class);
            $data = $plotService->getPaginatedList($query, $offset);
            /** @var TemplateRenderer $renderer */
            $renderer = $this->container->get(TemplateRenderer::class);
            $html = $renderer->render('plots_table', $data);

            return new JsonResponse([
                'html' => $html,
                'paginator' => $data['paginator'],
            ]);
        }

        return new JsonResponse([]);
    }

    private function registerServices(): void
    {
        $this->container->set(Config::class, static fn () => new Config());

        $this->container->set(DatabaseConnection::class, function (Container $container) {
            $config = $container->get(Config::class);

            return new PdoDatabaseConnection([
                'dsn' => $config->getDsn(),
                'user' => $config->getDbUser(),
                'password' => $config->getDbPassword(),
            ]);
        });

        $this->container->set(Sanitizer::class, static fn () => new Sanitizer());
        $this->container->set(PhoneFormatter::class, static fn () => new PhoneFormatter());
        $this->container->set(Paginator::class, static fn () => new Paginator());
        $this->container->set(Clock::class, static fn () => new Clock());
        $this->container->set(TokenGenerator::class, static fn () => new TokenGenerator());

        $this->container->set(TemplateRenderer::class, static fn () => new TemplateRenderer(__DIR__.'/../../partials'));

        $this->container->set(SessionManager::class, function (Container $container) {
            return new SessionManager(
                $container->get(DatabaseConnection::class),
                $container->get(Config::class),
                $container->get(TokenGenerator::class),
                $container->get(Sanitizer::class),
                $container->get(Clock::class)
            );
        });

        $this->container->set(UserRepository::class, function (Container $container) {
            return new UserRepository($container->get(DatabaseConnection::class));
        });

        $this->container->set(UserService::class, function (Container $container) {
            return new UserService(
                $container->get(UserRepository::class),
                $container->get(Sanitizer::class),
                $container->get(PhoneFormatter::class),
                $container->get(Paginator::class)
            );
        });

        $this->container->set(PlotRepository::class, function (Container $container) {
            return new PlotRepository($container->get(DatabaseConnection::class));
        });

        $this->container->set(PlotService::class, function (Container $container) {
            return new PlotService(
                $container->get(PlotRepository::class),
                $container->get(UserService::class),
                $container->get(Paginator::class),
                $container->get(Clock::class),
                $container->get(Sanitizer::class)
            );
        });

        $this->container->set(AuthService::class, function (Container $container) {
            return new AuthService(
                $container->get(UserService::class),
                $container->get(SessionManager::class),
                $container->get(DatabaseConnection::class),
                $container->get(Sanitizer::class),
                $container->get(Config::class),
                $container->get(Clock::class)
            );
        });

        $this->container->set(ModuleRegistry::class, fn (Container $container) => new ModuleRegistry($container));

        $this->container->set(\Modules\Plots\Controllers\PageController::class, fn (Container $container) => new \Modules\Plots\Controllers\PageController(
            $container->get(PlotService::class)
        ));

        $this->container->set(\Modules\Plots\Controllers\AjaxController::class, fn (Container $container) => new \Modules\Plots\Controllers\AjaxController(
            $container->get(PlotService::class),
            $container->get(TemplateRenderer::class)
        ));

        $this->container->set(\Modules\Users\Controllers\PageController::class, fn (Container $container) => new \Modules\Users\Controllers\PageController(
            $container->get(UserService::class)
        ));

        $this->container->set(\Modules\Users\Controllers\AjaxController::class, fn (Container $container) => new \Modules\Users\Controllers\AjaxController(
            $container->get(UserService::class),
            $container->get(TemplateRenderer::class)
        ));
    }
}
