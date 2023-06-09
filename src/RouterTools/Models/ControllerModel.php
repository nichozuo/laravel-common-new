<?php

namespace LaravelCommonNew\RouterTools\Models;

class ControllerModel
{
    public string $name;
    public array $modules;

    public string $namespace;
    public string $comment;

    /**
     * @var MethodModel[]
     */
    public array $methods;
}