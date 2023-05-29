<?php

namespace LaravelCommonNew\App\Helpers;

use LaravelCommonNew\App\Consts\Colors;
use ReflectionClass;

class ColorsHelper
{
    /**
     * @return array
     */
    public static function GetAllColors(): array
    {
        $ref = new ReflectionClass(Colors::class);
        return array_values($ref->getConstants());
    }
}
