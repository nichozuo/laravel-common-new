<?php

namespace LaravelCommonNew\DBTools;

use Illuminate\Support\Facades\File;
use LaravelCommonNew\DBTools\Models\EnumConstModel;
use LaravelCommonNew\DBTools\Models\EnumModel;
use LaravelCommonNew\Utils\DocBlockReader;
use ReflectionClass;
use ReflectionException;

class EnumToolsServices
{
    /**
     * @return EnumModel[]
     * @throws ReflectionException
     */
    public static function GetEnums(): array
    {
        $enums = [];
        foreach (File::files(app_path("Enums")) as $item) {
            $enumName = str_replace('.php', '', $item->getFilename());
            $enumRef = new ReflectionClass('\\App\\Enums\\' . $enumName);
            $enumDoc = DocBlockReader::parse($enumRef->getDocComment());

            // 获取常量
            $consts = [];
            foreach ($enumRef->getConstants() as $constRef) {
                $constDoc = DocBlockReader::parse($enumRef->getReflectionConstant($constRef->name)->getDocComment());
                $const = new EnumConstModel();
                $const->label = $constDoc['label'] ?? $constRef->name;
                $const->value = $constDoc['value'] ?? $constRef->value;
                $const->color = $constDoc['color'] ?? self::getRandomColor();
                $consts[] = $const;
            }

            $enum = new EnumModel();
            $enum->name = $enumName;
            $enum->intro = $enumDoc['intro'] ?? '';
            $enum->consts = $consts;

            $enums[] = $enum;
        }
        return $enums;
    }

    /**
     * @return string
     */
    private static function getRandomColor(): string
    {
        $str = '#';
        for ($i = 0; $i < 6; $i++) {
            $str .= dechex(rand(0, 15));
        }
        return $str;
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public static function ToString(): string
    {
        $enums = self::GetEnums();
        $str = '';
        foreach ($enums as $enum) {
            $consts = [];
            foreach ($enum->consts as $const) {
                $consts[] = [
                    'label' => $const->label,
                    'value' => $const->value,
                    'color' => $const->color,
                ];
            }
            $str .= '// ' . $enum->intro . PHP_EOL;
            $str .= "export const $enum->name =" . json_encode($consts, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
        return $str;
    }
}