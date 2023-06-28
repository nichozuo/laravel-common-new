<?php

namespace LaravelCommonNew\DocTools;

use cebe\openapi\exceptions\TypeErrorException;
use Doctrine\DBAL\Exception;
use Illuminate\Routing\Controller;
use ReflectionException;

class DocController extends Controller
{
    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws TypeErrorException
     */
    public function getOpenApi(): mixed
    {
        return json_decode(DocToolsServices::GenOpenApiV3Doc());
    }
}