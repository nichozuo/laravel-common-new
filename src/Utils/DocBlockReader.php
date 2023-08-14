<?php

namespace LaravelCommonNew\Utils;

class DocBlockReader
{
    /**
     * @param $docblock
     * @return array
     */
    public static function parse($docblock): array
    {
        $result = [];
        if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $docblock, $matches)) {
            $result = array_combine($matches[1], $matches[2]);
        }
        return $result;
    }
}