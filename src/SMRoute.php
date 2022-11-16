<?php

declare(strict_types=1);

namespace SMRouter;

class SMRoute
{
    private $params = [];
    private $controller;
    private $action;

    public function __construct(string $controller, string $action, array $params = [])
    {
        $this->params = $params;
        $this->controller = $controller;
        $this->action = $action;
    }
}
