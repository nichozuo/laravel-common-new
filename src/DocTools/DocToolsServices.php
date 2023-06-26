<?php

namespace LaravelCommonNew\DocTools;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\spec\Info;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\PathItem;
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
        $openapi = new OpenApi([
            'openapi' => '3.0.2',
            'info' => new Info([
                'title' => 'Test API',
                'version' => '1.0.0',
            ]),
            'paths' => [
                '/test' => new PathItem([
                    'description' => 'something'
                ]),
            ],
        ]);
        $json = Writer::writeToJson($openapi);
        File::put(storage_path('openapi.json'), $json);
    }
}