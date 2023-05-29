<?php

namespace LaravelCommonNew\App\Helpers;

use Illuminate\Support\Str;
use ReflectionEnum;
use ReflectionException;
use ReflectionMethod;

class ReflectHelper
{
    /**
     * @param string $fileName
     * @return ReflectionEnum
     * @throws ReflectionException
     */
    public static function GetEnumByPath(string $fileName): ReflectionEnum
    {
        $fileName = str_replace('.php', '', $fileName);
        return new ReflectionEnum('\\App\\Enums\\' . $fileName);
    }

    /**
     * @param string $pathname
     * @return string
     */
    public static function ParseControllerFromFilepath(string $pathname): string
    {
        $pathname = str_replace(app_path(), '', $pathname);
        $pathname = str_replace('.php', '', $pathname);
        $pathname = str_replace(DIRECTORY_SEPARATOR, '\\', $pathname);
        return "App$pathname";
    }

    /**
     * @param ReflectionMethod $method
     * @return array
     */
    public static function ParseMethodParams(ReflectionMethod $method): array
    {
        $lines = self::getMethodContent($method);
        $data = self::getMethodValidateContent($lines);
        return self::getMethodValidateParams($data);
    }

    /**
     * @param ReflectionMethod $method
     * @return array|false
     */
    private static function getMethodContent(ReflectionMethod $method): bool|array
    {
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $length = $endLine - $startLine;
        $source = file($method->getFileName());
        return array_slice($source, $startLine, $length);
    }

    /**
     * @param array $lines
     * @return array
     */
    private static function getMethodValidateContent(array $lines): array
    {
        $strStart = ']);';
        $strEnd = '$params = $request->validate([';

        $start = $end = false;
        $arr = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t == $strStart) $end = true;
            if ($start && !$end)
                $arr[] = $t;
            if ($t == $strEnd) $start = true;
        }
        return $arr;
    }

    /**
     * @param array $data
     * @return array
     */
    private static function getMethodValidateParams(array $data): array
    {
        $arr = [];
        foreach ($data as $item) {
            if (Str::startsWith(trim($item), "//"))
                continue;
            $t1 = explode('\'', $item);
            if (count($t1) < 3) continue;
            $t2 = explode('|', $t1[3]);
            $t3 = explode('#', $t1[4]);
            $t4 = [
                'key' => str_replace('.*.', '.\*.', $t1[1]),
                'required' => $t2[0] == 'nullable' ? '-' : 'Y',
                'type' => $t2[1],
                'comment' => (count($t3) > 1) ? trim($t3[1]) : '-'
            ];
            $arr[] = $t4;
        }
        return $arr;
    }
}
