<?php

namespace src\core;


class Router
{
    // Store registered routes for the app
    private $routes;
    public function __construct()
    {
        $this->routes = [];
    }

    /**
     * Add a route to the app with certain method, path, handler (Class@method), and middlewares
     * @param string $method
     * @param string $path
     * @param callable Factory function that returns the function handler (associative array controller, method) (only 1)
     * @param callable Factory function that returns the middlewares (>= 0)
     */
    private function addRoute(string $method, string $path, callable $handlerFactory, array $middlewaresFactory = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handlerFactory' => $handlerFactory,
            'middlewaresFactory' => $middlewaresFactory,
        ];
    }

    /**
     * Add a GET route to the app
     */
    public function get(string $path, callable $handlerFactory, array $middlewaresFactory = [])
    {
        $this->addRoute('GET', $path, $handlerFactory, $middlewaresFactory);
    }

    /**
     * Add a POST route to the app
     */
    public function post(string $path, callable $handlerFactory, array $middlewaresFactory = [])
    {
        // $this->addRoute('POST', $path, $handlerFactory, array_merge($middlewaresFactory, [$this->csrfMiddlewareFactory()]));
        $this->addRoute('POST', $path, $handlerFactory, $middlewaresFactory);
    }

    /**
     * Add a PUT route to the app
     */
    public function put(string $path, callable $handlerFactory, array $middlewaresFactory = [])
    {
        // $this->addRoute('PUT', $path, $handlerFactory, array_merge($middlewaresFactory, [$this->csrfMiddlewareFactory()]));
        $this->addRoute('PUT', $path, $handlerFactory, $middlewaresFactory);
    }

    /**
     * Add a DELETE route to the app
     */
    public function delete(string $path, callable $handlerFactory, array $middlewaresFactory = [])
    {
        // $this->addRoute('DELETE', $path, $handlerFactory, array_merge($middlewaresFactory, [$this->csrfMiddlewareFactory()]));
        $this->addRoute('DELETE', $path, $handlerFactory, $middlewaresFactory);
    }

    // generate csrf token
    private function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    // validate csrf token
    private function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // CSRF middleware factory
    private function csrfMiddlewareFactory(): callable
    {
        return function () {
            return new class {
                public function handle(Request $req, Response $res): void
                {
                    $method = $_SERVER['REQUEST_METHOD'];
                    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
                        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
                        if (!$token || !Router::validateCsrfToken($token)) {
                            $res->renderError([
                                'statusCode' => 403,
                                'subHeading' => 'Invalid CSRF Token',
                                'message' => 'CSRF token validation failed',
                            ]);
                            exit;
                        }
                    }
                }
            };
        };
    }

    /**
     * Dispatch the request to the correct handler
     */
    public function dispatch(): void
    {
        $res = new Response();
        $reqMethod = $_SERVER['REQUEST_METHOD'];
        $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($this->matchPath($route['path'], $reqPath) && $route['method'] === $reqMethod) {
                // Initialize request & response
                $req = new Request($route['path']);

                // Call all middlewares
                $middlewareFactories = $route['middlewaresFactory'];
                foreach ($middlewareFactories as $mf) {
                    $mf()->handle($req, $res);
                }

                // Call the handler
                $handler = $route['handlerFactory']();
                $controller = $handler['controller'];
                $method = $handler['method'];
                $controller->$method($req, $res);

                return;
            }
        }

        // If not found render to 404 page
        $data = [
            'statusCode' => 404,
            'subHeading' => "Page Not Found",
            'message' => "Sorry, the page you are looking for doesnt exist",
        ];

        $res->renderError($data);
    }

    private function matchPath(string $router, string $uri): bool
    {
        // If root
        if ($router === '/') {
            // Seperate the query params if any
            $uri = explode('?', $uri)[0];
            return $uri === '/';
        }

        // Not root
        // Parse path parameters /[id]/ into regex
        // and match from the beginning to the end of the string

        // ignore trailing slashes
        $parsedUri = rtrim($uri, '/');

        // explode /
        $routerPaths = explode('/', $router);
        $uriPaths = explode('/', $parsedUri);

        // if the number of paths is different, return false
        if (count($routerPaths) !== count($uriPaths)) {
            return false;
        }

        // validate each path segment
        for ($i = 0; $i < count($routerPaths); $i++) {
            // if the path is equal, continue
            if ($routerPaths[$i] === $uriPaths[$i]) {
                continue;
            }

            // if the path is a parameter (e.g., [id])
            if ($routerPaths[$i][0] === '[' && $routerPaths[$i][strlen($routerPaths[$i]) - 1] === ']') {
                // validate parameter value to prevent path traversal
                $paramValue = $uriPaths[$i];
                // allow only alphanumeric, underscores, and hyphens
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $paramValue)) {
                    return false;
                }
                continue;
            }

            // if the path is not a parameter and not equal, return false
            return false;
        }

        return true;
    }
}
