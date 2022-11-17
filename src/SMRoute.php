<?php

declare(strict_types=1);

namespace SMRouter;

class SMRoute
{
    private $params = [];
    private $controller;
    private $action;
    private $isApi;

    public function __construct(string $controller, string $action, array $params = [], $isApi = false)
    {
        $this->params = $params;
        $this->controller = $controller;
        $this->action = $action;
        $this->isApi = $isApi;
    }

    public function __get($prop)
    {
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
    }

}
