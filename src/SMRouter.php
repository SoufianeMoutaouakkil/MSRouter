<?php

declare(strict_types=1);

namespace SMRouter;

use SMRouter\Exception\SMInvalidRouteMethodException;
use SMRouter\Exception\SMNotFoundException;

class SMRouter
{
    private array $routes = [];
    private static $sInstance;

    public static function getInstance($routesList = [])
    {
        if (is_null(self::$sInstance)) {
            self::$sInstance = new SMRouter($routesList);
        }
        return self::$sInstance;
    }

    private function __construct(array $routesList = [])
    {
        if ($routesList !== []) {
            $this->addRoutesList($routesList);
        }
    }

    public function addRoutesList(array $routesList)
    {
        foreach ($routesList as $method => $routes) {
            // validate the $method
            if (in_array($method, $this->allowedMethod())) {
                // validate that the routes is array
                if (!is_array($routes)) {
                    throw new SMInvalidRoutesList(
                        "Invalid routes list.".
                        "For each HTTP method, we need an array of 'path => [controllerClass, method]' !"
                    );
                }
                foreach ($routes as $path => $callback) {
                    $this->validateCallback($callback);
                    $path = trim($path, '/');
                    if (!in_array($path, $this->$routes[$method])) {
                        $this->$routes[$method][$path] = $callback;
                    }
                }
            } else {
                throw new SMInvalidRouteMethodException(
                    "Your routes List contains this method {$method}, wich not allowed!"
                );
            }
        }
        
    }

    /**
     * New
     */
    private function allowedMethod()
    {
        return ["get", "post", "delete", "put"];
    }

    private function validateCallback($callback)
    {
        $error = true;
        if (is_array($callback) && class_exists($callback[0])) {
            $error = false;
        }
        if ($error) {
            throw new SMInvalidRoutesList(
                "Invalid Callback.".
                "The callback must be an array of the next form : '[controllerClass, method]'!"
            );
        }
    }
    /**
     * New
     */
    public function addRoute(string $method, string $path, $callback)
    {
        $path = trim($path, '/');
        $this->validateCallback($callback);

        $this->routes[$method][$path] = $callback;
    }
    /**
     * New
     */
    public function get(string $path, $callback)
    {
        $this->addRoute("get", $path, $callback);
    }
    /**
     * New
     */
    public function delete(string $path, $callback)
    {
        $this->addRoute("delete", $path, $callback);
    }
    /**
     * New
     */
    public function post(string $path, $callback)
    {
        $this->addRoute("post", $path, $callback);
    }
    /**
     * New
     */
    public function put(string $path, $callback)
    {
        $this->addRoute("put", $path, $callback);
    }



    /**
     * New
     */
    public function getRoutesByMethod($method): array
    {
        return $this->routes[$method] ?? [];
    }

    private function getCallback()
    {
        $method = $this->method;
        $url = $this->url;

        // Get all routes for current request method
        $routes = $this->getRoutesByMethod($method);

        $routeParams = false;

        // Start iterating registed routes
        foreach ($routes as $route => $callback) {
            // Trim slashes
            $routeNames = [];

            if (!$route) {
                continue;
            }

            // Find all route names from route and save in $routeNames
            if (preg_match_all('/\{(\w+)(:[^}]+)?}/', $route, $matches)) {
                $routeNames = $matches[1];
            }

            // Convert route name into regex pattern
            $routeRegex =
                "@^" .
                preg_replace_callback(
                    '/\{\w+(:([^}]+))?}/',
                    fn($m) => isset($m[2]) ? "({$m[2]})" : '(\w+)',
                    $route
                )
                . "$@";

            // Test and match current route against $routeRegex
            if (preg_match_all($routeRegex, $url, $valueMatches)) {
                $values = [];
                for ($i = 1; $i < count($valueMatches); $i++) {
                    $values[] = $valueMatches[$i][0];
                }
                $callback[] = array_combine($routeNames, $values);
                return $callback;
            }
        }

        return false;
    }

    public function resolve(string $url, string $method)
    {
        // prepare url
        $this->url = trim($url, '/');
        // prepare method
        if (!in_array($method, $this->allowedMethod())) {
            throw new SMInvalidRouteMethodException(
                "Your routes List contains this method {$method}, wich not allowed!"
            );
        }

        // search the callback
        $callback = $this->routes[$method][$url] ?? false;
        
        // if no callback Found, that means that this url is a path with params or not found
        if ($callback === false) {
            // try to find if this url match to a path with params
            $callback = $this->getCallback();

            if ($callback === false) {
                throw new SMNotFoundException();
            }
        }
        return new Route($callback[0], $callback[1], $callback[2]);
    }
}
