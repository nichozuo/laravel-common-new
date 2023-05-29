<?php

namespace LaravelCommonNew\App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ControllerAttribute
{
    // 设置的值
    public ?string $title;   // 标题
    public ?bool $auth;      // 是否鉴权

    // 反射的值
    public ?string $className;
    public ?array $modules;
    public ?string $routePrefix;
    public ?string $routeName;

    /**
     * @param string|null $title
     * @param bool|null $auth
     */
    public function __construct(?string $title = null, ?bool $auth = true)
    {
        $this->title = $title;
        $this->auth = $auth;
    }
}
