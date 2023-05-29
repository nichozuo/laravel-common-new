<?php

namespace LaravelCommonNew\App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ActionAttribute
{
    // 设置的值
    public ?string $title;
    public ?string $desc;
    public ?array $methods;

    // 反射的值
    public ?string $name;               // 方法名
    public ?string $uri;                // uri
    public ?string $fullUri;            // 完整uri
    public ?array $params;              // 入参
    public ?string $response;           // 响应字符串
    public ?string $responseParams;     // 响应字符串

    /**
     * @param string|null $title
     * @param string|null $desc
     * @param string|null $methods
     */
    public function __construct(string $title = null, ?string $desc = null, ?string $methods = 'POST')
    {
        $this->title = $title;
        $this->desc = $desc;
        $this->methods = explode(',', $methods);

        $this->params = null;
        $this->response = json_encode(['code' => 0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->responseParams = null;
    }
}
