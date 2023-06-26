<?php

namespace LaravelCommonNew\GenTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelCommonNew\Utils\DocBlockReader;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use ReflectionException;

class GenAllEnumsCommand extends Command
{
    protected $signature = 'gae';
    protected $description = 'Gen enums to ts file';

    /**
     * @return void
     * @throws ReflectionException
     */
    public function handle(): void
    {
        $str = '';
        foreach (File::files(app_path("Enums")) as $item) {
            $fileName = str_replace('.php', '', $item->getFilename());
            $enum = new ReflectionClass('\\App\\Enums\\' . $fileName);
            $this->genContent($enum, $str);
        }
        File::put('enums.ts', $str);
        $this->warn("生成成功:: enums.ts");
    }

    /**
     * @param ReflectionClass $enum
     * @param string $str
     * @return void
     */
    private function genContent(ReflectionClass $enum, string &$str): void
    {
        $enumDocComment = DocBlockReader::parse($enum->getDocComment());
        $fileName = last(explode('\\', $enum->getName()));
        $enumTitle = $enumDocComment['intro'] ?? $fileName;
        $arr = [];
        foreach ($enum->getConstants() as $constant) {
            $docComment = DocBlockReader::parse($enum->getReflectionConstant($constant->name)->getDocComment());
            $label = $docComment['label'] ?? $constant->name;
            $value = $docComment['value'] ?? $constant->value;
            $color = $docComment['value'] ?? $this->getRandomColor();
            $arr[] = [
                'label' => $label,
                'value' => $value,
                'color' => $color,
            ];
        }
        $str .= '// ' . $enumTitle . PHP_EOL;
        $str .= "export const $fileName =" . json_encode($arr, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    /**
     * @return string
     */
    private function getRandomColor(): string
    {
        $str = '#';
        for ($i = 0; $i < 6; $i++) {
            $str .= dechex(rand(0, 15));
        }
        return $str;
    }
}
