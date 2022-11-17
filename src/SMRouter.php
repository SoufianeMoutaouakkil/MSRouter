<?php

declare(strict_types=1);

namespace SMDEV\SMRouter;

use SMDEV\SMRouter\Exception\SMInvalidHttpMethodException;
use SMDEV\SMRouter\Exception\SMNotFoundException;
use SMDEV\SMRouter\Exception\SMInvalidRoutesList;
use SMDEV\SMRouter\Exception\SMInvalidCallback;

class SMRouter
{
    private array $routes = [];
    private static $sInstance;

    /**
     * Singleton parts
     */
    public static function getInstance($routesList = [])
    {
        if (is_null(self::$sInstance)) {
            self::$sInstance = new SMRouter($routesList);
        }
        return self::$sInstance;
    }

    public static function unsetInstance()
    {
        if (!is_null(self::$sInstance)) {
            self::$sInstance = null;
        }
    }

    private function __construct(array $routesList = [])
    {
        if ($routesList !== []) {
            $this->addRoutes($routesList);
        }
    }

    public function addRoutes(array $routesList)
    {
        foreach ($routesList as $method => $routes) {
                if (!is_array($routes)) {
                    throw new SMInvalidRoutesList(
                        "Invalid routes list.".
                        "For each HTTP method, we need an array of 'path => [controllerClass, method]' !"
                    );
                }
                foreach ($routes as $path => $callback) {
                    $this->addRoute($method, $path, $callback);
                }
        }
    }

    /**
     * Adding routes part
     */
    public function get(string $path, $callback)
    {
        $this->addRoute("get", $path, $callback);
    }

    public function delete(string $path, $callback)
    {
        $this->addRoute("delete", $path, $callback);
    }

    public function post(string $path, $callback)
    {
        $this->addRoute("post", $path, $callback);
    }

    public function put(string $path, $callback)
    {
        $this->addRoute("put", $path, $callback);
    }

    /**
     * resolving part
     */
    public function resolve(string $method, string $url)
    {
        // prepare url
        $this->url = $this->prepareUrl($url);
        // prepare method
        $this->validateMethod($method);

        $this->method = $method;

        // search the callback
        $callback = $this->routes[$method][$url] ?? false;
        
        // if no callback Found, that means that this url is a path with params or not found
        if ($callback === false) {
            // try to find if this url match to a path with params
            $callback = $this->getCallback();

            if ($callback === false) {
                throw new SMNotFoundException();
            }
        } else {
            // in this case, no params must be found in the url
            $callback[] = [];
        }
        return new SMRoute($callback[0], $callback[1], $callback[2]);
    }

    /**
     * private adding routes part
     */
    private function allowedMethod()
    {
        return ["get", "post", "delete", "put"];
    }

    private function prepareUrl($url)
    {
        $url = trim($url);
        $url = $url !== "/" ? trim($url, "/") : $url;
        return strtolower($url);
    }

    private function validateCallback($callback)
    {
        if (!is_array($callback)) {
            throw new SMInvalidCallback(
                "Invalid Callback array. ".
                "The callback must be an array of the next form : '[controllerClass, method]'!"
            );
        } elseif (!class_exists($callback[0]) && property_exists($callback[0], $callback[1])) {
            throw new SMInvalidCallback(
                "Invalid Callback Class. ".
                "The first Element of the callback array must be a defined Class!"
            );
        } elseif (!method_exists($callback[0], $callback[1])) {
            throw new SMInvalidCallback(
                "Invalid Callback Method. ".
                "The second Element of the callback array must be a defined method in the Controller class!"
            );
        }
    }

    private function validateMethod($method)
    {
        if (!in_array($method, $this->allowedMethod())) {
            throw new SMInvalidHttpMethodException(
                "Your routes List contains this method {$method}, wich not allowed!"
            );
        }
    }

    private function addRoute(string $method, string $path, $callback)
    {
        $this->validateMethod($method);
        $this->validateCallback($callback);
        $path = $this->prepareUrl($path);
        $routesByMethod = $this->getRoutesByMethod($method);
        if (!in_array($path, $routesByMethod)) {
            $this->routes[$method][$path] = $callback;
        }
    }

    

    /**
     * private resolving part
     */
    private function getRoutesByMethod($method): array
    {
        return $this->routes[$method] ?? [];
    }

    private function getCallback()
    {
        $method = $this->method;
        $url = $this->url;

        // Get all routes for current request method
        $routes = $this->getRoutesByMethod($method);

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
                $params = array_combine($routeNames, $values);
                $callback[] = $params;
                return $callback;
            }
        }

        return false;
    }

}
