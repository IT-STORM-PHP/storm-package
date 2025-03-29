<?php
namespace StormBin\Package\Routes;

class Route {
    private static array $routes = [];
    private static array $middlewares = [];
    private static array $beforeMiddlewares = [];
    private static array $namedRoutes = []; // Stocker les routes nommées
    private static string $groupPrefix = ''; // Préfixe du groupe de routes actuel
    private static ?self $currentInstance = null; // Instance actuelle pour le chaînage
    private static ?string $lastAddedRouteUri = null; // Track the last added route URI

    public static function getRoutes(): array
    {
        return self::$routes;
    }

    public static function getRouteName(string $uri): ?string
    {
        foreach (self::$namedRoutes as $name => $routeUri) {
            if ($routeUri === $uri) {
                return $name;
            }
        }
        return null;
    }

    // Ajout de la méthode group
    public static function group(array $attributes, callable $callback) {
        if (isset($attributes['prefix'])) {
            self::$groupPrefix = rtrim($attributes['prefix'], '/');
        }

        // Appel de la fonction callback pour définir les routes
        $callback();

        // Réinitialisation du préfixe après le groupe
        self::$groupPrefix = '';
    }

    public static function add(string $method, string $uri, array|string|callable $action, string $name = '') {
        if (self::$groupPrefix) {
            $uri = self::$groupPrefix . $uri;
        }

        self::$routes[strtoupper($method)][$uri] = $action;

        // Track the last added route URI
        self::$lastAddedRouteUri = $uri;

        // If a name is given, associate it with the route
        if ($name) {
            self::$namedRoutes[$name] = $uri;
        }
    }

    public static function get(string $uri, array|string|callable $action, string $name = ''): self {
        self::add('GET', $uri, $action, $name);
        return self::$currentInstance = new self(); // Retourne une instance de Route
    }

    public static function post(string $uri, array|string|callable $action, string $name = ''): self {
        self::add('POST', $uri, $action, $name);
        return self::$currentInstance = new self(); // Retourne une instance de Route
    }

    public static function put(string $uri, array|string|callable $action, string $name = ''): self {
        self::add('PUT', $uri, $action, $name);
        return self::$currentInstance = new self(); // Retourne une instance de Route
    }

    public static function delete(string $uri, array|string|callable $action, string $name = ''): self {
        self::add('DELETE', $uri, $action, $name);
        return self::$currentInstance = new self(); // Retourne une instance de Route
    }

    public static function name(string $name): self {
        if (self::$lastAddedRouteUri) {
            self::$namedRoutes[$name] = self::$lastAddedRouteUri;
        }

        // Retourner l'instance actuelle pour le chaînage
        return self::$currentInstance;
    }

    public static function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $foundRoute = false;

        foreach (self::$beforeMiddlewares as $prefix => $callbacks) {
            if (self::matchPrefix($uri, $prefix)) {
                foreach ($callbacks as $callback) {
                    call_user_func($callback);
                }
            }
        }

        foreach (self::$routes as $routeMethod => $routes) {
            foreach ($routes as $route => $action) {
                $pattern = preg_replace('#\{(\w+)\}#', '([^/]+)', $route);
                if (preg_match("#^$pattern$#", $uri, $matches)) {
                    array_shift($matches);
                    if ($routeMethod === $method) {
                        return self::handleRoute($route, $action, $matches);
                    }
                    $foundRoute = true;
                }
            }
        }

        if ($foundRoute) {
            http_response_code(405);
            echo "405 - Méthode non autorisée";
            exit();
        }

        http_response_code(404);
        echo "404 - Page introuvable";
        exit();
    }

    private static function matchPrefix(string $uri, string $prefix): bool {
        return strpos($uri, $prefix) === 0;
    }

    private static function handleRoute($route, $action, $params) {
        // Exécuter les middlewares
        if (isset(self::$middlewares[$route])) {
            foreach (self::$middlewares[$route] as $middleware) {
                if (is_array($middleware) && count($middleware) === 2) {
                    [$class, $method] = $middleware;
                    if (class_exists($class) && method_exists($class, $method)) {
                        call_user_func([new $class, $method]);
                    }
                } else {
                    call_user_func($middleware);
                }
            }
        }

        // Convertir les paramètres en tableau associatif
        $paramNames = [];
        preg_match_all('#\{(\w+)\}#', $route, $paramNames);
        $paramNames = $paramNames[1]; // Récupérer les noms des paramètres

        $associativeParams = [];
        foreach ($paramNames as $index => $name) {
            $associativeParams[$name] = $params[$index] ?? null;
        }

        return self::execute($action, $associativeParams);
    }

    private static function execute($action, $params) {
        if (is_callable($action)) {
            // Utiliser la réflexion pour analyser la fonction de rappel
            $reflection = new \ReflectionFunction($action);
            $args = self::resolveParameters($reflection, $params);
            echo call_user_func_array($action, $args);
        } elseif (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            if (!class_exists($controller)) {
                http_response_code(500);
                echo "500 - Erreur interne: Contrôleur '$controller' introuvable.";
                exit();
            }

            $controllerInstance = new $controller();
            if (!method_exists($controllerInstance, $method)) {
                http_response_code(500);
                echo "500 - Erreur interne: Méthode '$method' non trouvée dans '$controller'.";
                exit();
            }

            // Utiliser la réflexion pour analyser la méthode du contrôleur
            $reflection = new \ReflectionMethod($controllerInstance, $method);
            $args = self::resolveParameters($reflection, $params);
            echo call_user_func_array([$controllerInstance, $method], $args);
        } else {
            http_response_code(500);
            echo "500 - Erreur interne: Action non valide.";
            exit();
        }
    }

    /**
     * Résoudre les paramètres pour une fonction ou une méthode.
     *
     * @param \ReflectionFunctionAbstract $reflection La réflexion de la fonction ou méthode
     * @param array $params Les paramètres de la route
     * @return array Les arguments résolus
     */
    private static function resolveParameters(\ReflectionFunctionAbstract $reflection, array $params): array {
        $args = [];

        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // Si le paramètre est de type Request, injecter une instance de Request
            if ($paramType && $paramType->getName() === 'StormBin\Package\Request\Request') {
                $args[] = new \StormBin\Package\Request\Request();
            } else {
                // Sinon, injecter les paramètres de la route par nom
                $args[] = $params[$paramName] ?? null;
            }
        }

        return $args;
    }

    // Méthode pour récupérer une route nommée
    public static function route(string $name, array $params = []): string {
        if (isset(self::$namedRoutes[$name])) {
            $uri = self::$namedRoutes[$name];

            // Remplacer les paramètres dans l'URL
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $uri = str_replace('{' . $key . '}', $value, $uri);
                }
            }

            return $uri;
        }

        throw new \Exception("Route nommée '$name' introuvable.");
    }
}