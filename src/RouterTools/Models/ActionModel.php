<?php

namespace LaravelCommonNew\RouterTools\Models;

class ActionModel
{
    public string $intro;
    public string $methodName;
    public string $uri;
    public string $method;
    public string $action;
    public array $middlewares;

    public bool $skipInRouter;
    public bool $skipWrap;
    public bool $skipAuth;
}