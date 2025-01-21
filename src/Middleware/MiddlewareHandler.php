<?php

// src/Middleware/MiddlewareHandler.php
namespace App\Middleware;

class MiddlewareHandler
{
    private array $middlewares = [];

    public function __construct()
    {
        $this->registerMiddlewares();
    }

    private function registerMiddlewares(): void
    {
        $this->middlewares = [
            'auth' => new AuthMiddleware(),
            'admin' => new AdminMiddleware(),
            'createur' => new CreateurMiddleware()
        ];
    }

    public function handle(array $middlewareNames): bool
    {
        foreach ($middlewareNames as $name) {
            if (isset($this->middlewares[$name])) {
                if (!$this->middlewares[$name]->handle()) {
                    return false;
                }
            }
        }
        return true;
    }
}