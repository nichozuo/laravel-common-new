<?php

namespace LaravelCommonNew\RouterTools\Models;

class ControllerModel
{
    public string $className;
    public string $modulesName;
    public array $modules;
    public string $controllerName;
    public string $intro;
    public string $routerPrefix;
    public string $routerName;
    /**
     * @var ActionModel[]
     */
    public array $actions;
}