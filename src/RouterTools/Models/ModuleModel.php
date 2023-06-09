<?php

namespace LaravelCommonNew\RouterTools\Models;

class ModuleModel
{
    public string $name;
    public string $namespace;

    /**
     * @var ControllerModel[]
     */
    public array $controllers;

    /**
     * @var ModuleModel[]
     */
    public array $modules;
}