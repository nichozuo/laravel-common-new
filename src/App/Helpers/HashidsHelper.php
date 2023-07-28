<?php

namespace LaravelCommonNew\App\Helpers;


use Vinkla\Hashids\Facades\Hashids;

class HashidsHelper
{
    /**
     * @param mixed $enum
     * @param int $id
     * @return string
     */
    public static function Encode(mixed $enum, int $id): string
    {
        return $enum->value . Hashids::connection($enum->name)->encode($id);
    }

    /**
     * @param mixed $enum
     * @param string $code
     * @return int
     */
    public static function Decode(mixed $enum, string $code): int
    {
        return Hashids::connection($enum->name)->decode(substr($code, strlen($enum->value)))[0];
    }
}