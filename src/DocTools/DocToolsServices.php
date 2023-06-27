<?php

namespace LaravelCommonNew\DocTools;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Info;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Server;
use cebe\openapi\Writer;
use Illuminate\Support\Facades\File;

class DocToolsServices
{
    /**
     * @return void
     * @throws TypeErrorException
     */
    public static function GenDoc(): void
    {
        // create base API Description
        $openapi = [
            'openapi' => '3.0.1',
            'info' => [
                'title' => config('app.name'),
                'version' => '0.0.x',
            ],
            'tags' => [
                ['name' => '接口声明', 'description' => '接口声明'],
                ['name' => '枚举类型', 'description' => '枚举类型'],
                ['name' => '数据字典', 'description' => '数据字典'],
                ['name' => '开发规范', 'description' => '开发规范'],
            ],
            'servers' => [
                [
                    "description" => "Development server (develop)",
                    "url" => "http://0.0.0.0:8000/api/"
                ],
                [
                    "description" => "Prod server (main)",
                    "url" => config('app.url') . "/api/"
                ]
            ],
            'paths' => [
                '/admin/auth/login' => [
                    'post' => [
                        "summary" => "管理员登录",
                        "x-apifox-folder" => "Admin/AuthController",
                        "description" => "管理员登录",
                        "tags" => [
                            "Admin/AuthController"
                        ],
                        "parameters" => [],
                        "requestBody" => [
                            "content" => [
                                "application/x-www-form-urlencoded" => [
                                    "schema" => [
                                        "type" => "object",
                                        "properties" => [
                                            "phone" => [
                                                "type" => "string",
                                                "enum" => ["apple", "banana", "orange"],
                                                "description" => "用户名",
                                                "example" => "13800138000"
                                            ],
                                            "password" => [
                                                "type" => "string",
                                                "description" => "密码",
                                                "example" => "123123"
                                            ]
                                        ],
                                        "required" => [
                                            "phone",
                                            "password"
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];
        File::put(storage_path('openapi.json'), json_encode($openapi, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}