<?php

namespace LaravelCommonNew\RouterTools\Models;

class MethodModel
{
    public string $name;
    public string $title;
    public ?string $intro;
    public string $method = 'POST';
    public bool $auth = true;
    public array $params = [];
}