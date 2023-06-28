<?php

namespace LaravelCommonNew\DocTools;

use Illuminate\Support\Facades\File;
use LaravelCommonNew\RouterTools\RouterToolsServices;
use ReflectionException;

class DocToolsServices
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public static function GenDoc(): void
    {
        $paths = self::getPaths();

        $openapi = [
            'openapi' => '3.0.1',
            'info' => [
                'title' => config('app.name'),
                'version' => '0.0.x',
            ],
            'tags' => [],
            'servers' => [
                [
                    "description" => "Server Address",
                    "url" => config('app.url') . "/api/"
                ]
            ],
            'paths' => $paths,
            'components' => [
                "responses" => [
                    "default" => [
                        "description" => "default response",
                        "content" => [
                            "application/json" => [
                                "schema" => [
                                    "type" => "object",
                                    "properties" => [
                                        "code" => [
                                            "type" => "integer",
                                            "description" => "code",
                                            "example" => 0,
                                        ],
                                        "message" => [
                                            "type" => "string",
                                            "description" => "message",
                                            "example" => "ok",
                                        ],
                                        "data" => [
                                            "type" => "object",
                                            "description" => "data"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];


        File::put(storage_path('openapi.json'), json_encode($openapi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private static function getPaths(): array
    {
        $controllers = RouterToolsServices::GenRoutersModels();
        $paths = [];
        foreach ($controllers as $controller) {
            foreach ($controller->actions as $action) {
                // 生成参数
                if ($action->params) {

                    $properties = [];
                    $required = [];

                    foreach ($action->params as $param) {
                        $properties[$param->key] = [
                            'type' => $param->type,
                            'description' => $param->description
                        ];
                        if ($param->required) {
                            $required[] = $param->key;
                        }
                    }

                    $requestBody = [
                        'content' => [
                            'application/x-www-form-urlencoded' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => $properties,
                                    'required' => $required,
                                ]
                            ]
                        ]
                    ];
                } else {
                    $requestBody = [];
                }


                $data = [
                    strtolower($action->method[0]) => [
                        "summary" => $action->intro,
                        "x-apifox-folder" => implode('/', $controller->modules),
                        "description" => $action->intro,
                        "tags" => [
                            implode('/', $controller->modules)
                        ],
                        "parameters" => [],
                        "requestBody" => $requestBody,
                        "responses" => [
                            "default" => [
                                "\$ref" => "#/components/responses/default"
                            ]
                        ]
                    ]
                ];
                $paths["/$controller->routerPrefix/$action->uri"] = $data;
            }
        }
        return $paths;
    }
}