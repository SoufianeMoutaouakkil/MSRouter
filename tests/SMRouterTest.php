<?php
declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;

use SMDEV\SMRouter\SMRouter;
use SMDEV\SMRouter\SMRoute;
use SMDEV\SMRouter\Exception\SMNotFoundException;
use SMDEV\SMRouter\Exception\SMInvalidRoutesList;
use SMDEV\SMRouter\Exception\SMInvalidCallback;
use SMDEV\SMRouter\Exception\SMInvalidHttpMethodException;

use Test\testFiles\TestController;

class SMRouterTest extends TestCase
{

    private $configDir;
    private $router;

    public function __construct()
    {
        parent::__construct();
        $this->configDir = (\dirname(__FILE__) . "/testFiles/");
    }
    
    private function resetInstance()
    {
        SMRouter::unsetInstance();
        $this->router = SMRouter::getInstance();
    }
    
    private function resetConfig()
    {
        $config = include $this->configDir . "routesTest.php";
        $this->resetInstance();
        $this->router->addRoutes($config);
    }
    
    public function testInstantiateError()
    {
        $this->expectException(\Error::class);
        $router = new SMRouter;
        unset($router);
    }
    
    /**
     * test Exceptions
     */
    public function testSMNotFoundException()
    {
        $this->expectException(SMNotFoundException::class);
        $this->resetInstance();

        $this->router->resolve("get", "notFoundPath");
    }

    public function testInvalidRoutesList()
    {
        $this->expectException(SMInvalidRoutesList::class);
        $this->resetInstance();

        $this->router->addRoutes(["get" => "invalid routes list"]);
    }

    public function testInvalidCallbackArray()
    {
        $this->expectException(SMInvalidCallback::class);
        $this->resetInstance();

        $this->router->addRoutes([
            "get" => [
                    "path1" => "invalid callbackArray"
                ]
            ]);
    }

    public function testInvalidCallbackClass()
    {
        $this->expectException(SMInvalidCallback::class);
        $this->resetInstance();

        $this->router->addRoutes([
            "get" => [
                    "path1" => ["invalid class", ""]
                ]
            ]);
    }

    public function testInvalidCallbackMethod()
    {
        $this->expectException(SMInvalidCallback::class);
        $this->resetInstance();
        $this->router->addRoutes([
            "get" => [
                    "path1" => [TestController::class, ""]
                ]
            ]);
    }

    /**
     * test resolving routes
     */
    public function testResolveConfiguratedRoutes()
    {
        $this->resetConfig();
        
        // test routes without params
        $this->testResolveRoutesWithoutParams();

        // test routes with params
        $this->testResolveRoutesWithParams();
    }

    public function testResolveAddedRoutes()
    {
        $this->resetInstance();
        
        // add routes manuely
        $this->router->get("/path1", [TestController::class, "getMethod1"]);
        $this->router->get("/", [TestController::class, "home"]);
        $this->router->post("/path1", [TestController::class, "postMethod1"]);
        $this->router->get("paramsPath/{paramName:\d}", [TestController::class, "digitParam"]);
        $this->router->get("paramsPath/{paramName}", [TestController::class, "param"]);

        // test routes without params
        $this->testResolveRoutesWithoutParams();

        // test routes with params
        $this->testResolveRoutesWithParams();
    }

    private function testResolveRoutesWithoutParams()
    {
        // test a normal get route
        $route = $this->router->resolve("get", "path1");
        $this->assertInstanceOf(SMRoute::class, $route);
        $this->assertFalse($route->isApi);
        $this->assertEqualsIgnoringCase(TestController::class, $route->controller);
        $this->assertEquals("getMethod1", $route->action);

        // test a normal post route
        $route = $this->router->resolve("post", "path1");
        $this->assertInstanceOf(SMRoute::class, $route);
        $this->assertEqualsIgnoringCase(TestController::class, $route->controller);
        $this->assertEquals("postMethod1", $route->action);
        
        // test sensitive case of resolving route
        $routeWithDifferentCase = $this->router->resolve("get", "Path1");
        $this->assertInstanceOf(SMRoute::class, $routeWithDifferentCase);
        
        // test slashes at the begining and at the end of urls
        $routeWithDifferentCase = $this->router->resolve("get", "/Path1");
        $this->assertInstanceOf(SMRoute::class, $routeWithDifferentCase);
        $routeWithDifferentCase = $this->router->resolve("get", "Path1/");
        $this->assertInstanceOf(SMRoute::class, $routeWithDifferentCase);
        $routeWithDifferentCase = $this->router->resolve("get", "/Path1/");
        $this->assertInstanceOf(SMRoute::class, $routeWithDifferentCase);
        
        // test resolving Home Page
        $routeHome = $this->router->resolve("get", "/");
        $this->assertInstanceOf(SMRoute::class, $routeHome);
        $this->assertEqualsIgnoringCase(TestController::class, $routeHome->controller);
        $this->assertEquals("home", $routeHome->action);
    }

    private function testResolveRoutesWithParams()
    {
        // test a route with param without preg validation
        $route = $this->router->resolve("get", "paramsPath/paramvalue");
        $this->assertInstanceOf(SMRoute::class, $route);
        $this->assertEquals("param", $route->action);
        $this->assertIsArray($route->params);
        $this->assertArrayHasKey("paramname", $route->params);
        $this->assertEquals("paramvalue", $route->params["paramname"]);
        
        // test a route with param with preg validation
        $route = $this->router->resolve("get", "paramsPath/5");
        $this->assertInstanceOf(SMRoute::class, $route);
        $this->assertEquals("digitParam", $route->action);
        $this->assertIsArray($route->params);
        $this->assertArrayHasKey("paramname", $route->params);
        $this->assertEquals(5, $route->params["paramname"]);
    }
}
