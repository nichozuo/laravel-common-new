<?php

namespace LaravelCommonNew\DocTools;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Info;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Paths;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Responses;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Server;
use cebe\openapi\spec\Tag;
use cebe\openapi\Writer;
use Doctrine\DBAL\Exception;
use LaravelCommonNew\DBTools\DBToolsServices;
use LaravelCommonNew\RouterTools\RouterToolsServices;
use ReflectionException;

class DocToolsServices
{
    /**
     * @throws Exception
     * @throws ReflectionException
     * @throws TypeErrorException
     */
    public static function GenOpenApiV3Doc(): string
    {
        list($tags, $paths) = self::getPathsAndTags();
        $schemas = self::getSchemas();

        $openapi = new OpenApi([
            'openapi' => '3.0.1',
            'info' => new Info([
                'title' => config('app.name'),
                'version' => '0.0.x',
            ]),
            'servers' => [
                new Server([
                    "description" => "Server Address",
                    "url" => config('app.url') . "/api/"
                ])
            ],
            'tags' => $tags,
            'paths' => $paths,
            'components' => [
                "responses" => new Responses([
                    'default' => new Response([
                        "description" => "default response",
                        "content" => [
                            "application/json" => new MediaType([
                                "schema" => new Schema([
                                    "type" => "object",
                                    "properties" => [
                                        "code" => new Schema([
                                            "type" => "integer",
                                            "description" => "code",
                                            "example" => 0,
                                        ]),
                                        "message" => new Schema([
                                            "type" => "string",
                                            "description" => "message",
                                            "example" => "ok",
                                        ]),
                                        "data" => new Schema([
                                            "type" => "object",
                                            "description" => "data"
                                        ])
                                    ]
                                ])
                            ])
                        ]
                    ])
                ]),
                "schemas" => $schemas,
            ]
        ]);
        return Writer::writeToJson($openapi);
//        File::put(storage_path('openapi.json'), );
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws TypeErrorException
     */
    private static function getPathsAndTags(): array
    {
        $controllers = RouterToolsServices::GenRoutersModels();
        $pathItems = [];
        $tags = [];
        $tagNames = [];
        foreach ($controllers as $controller) {
            foreach ($controller->actions as $action) {
                $name = implode('/', $controller->modules);

                // 处理tag
                if (!in_array($name, $tagNames)) {
                    $tags[] = new Tag([
                        "name" => $name,
                        "description" => $controller->intro
                    ]);
                    $tagNames[] = $name;
                }

                // 处理properties 和 required
                $properties = [];
                $required = [];

                foreach ($action->params as $param) {
                    $properties[$param->key] = new Schema([
                        'type' => $param->type,
                        'description' => $param->description,
                        "required" => $param->required,
                    ]);
                    if ($param->required) {
                        $required[] = $param->key;
                    }
                }

                // 处理pathItem
                $pathItems["/$controller->routerPrefix/$action->uri"] = new PathItem([
                    strtolower($action->method[0]) => new Operation([
                        "tags" => [$name],
                        "summary" => $action->methodName,
                        "description" => $action->intro,
//                        "parameters" => [], // 默认没有
                        "requestBody" => count($action->params) == 0 ? [] : new RequestBody([
                            "content" => [
                                'application/x-www-form-urlencoded' => new MediaType([
                                    "schema" => new Schema([
                                        "type" => "object",
                                        "properties" => $properties,
                                        "required" => $required,
                                    ])
                                ])
                            ]
                        ]),
                        "responses" => new Responses([
                            "default" => new Response([
                                '$ref' => "#/components/responses/default"
                            ])
                        ])
                    ])
                ]);
            }
        }

        $paths = new Paths($pathItems);
        return [$tags, $paths];
    }

    /**
     * @return array
     * @throws Exception
     * @throws TypeErrorException
     */
    private static function getSchemas(): array
    {
        $schemas = [];

        // database
        $db = DBToolsServices::Gen();
        foreach ($db->tables as $table) {
            $properties = [];
            $required = [];
            foreach ($table->columns as $column) {
                $properties[$column->name] = new Schema([
                    "type" => $column->type,
                    "description" => $column->comment,
                    "required" => $column->notNull,
                ]);
                if ($column->notNull) {
                    $required[] = $column->name;
                }
            }
            $schemas[$table->name] = new Schema([
                "type" => "object",
                "description" => $table->comment,
                "properties" => $properties,
                "required" => $required
            ]);
        }

        // enums

        return $schemas;
    }
}