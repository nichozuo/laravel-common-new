<?php

namespace LaravelCommonNew\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use LaravelCommonNew\App\Exceptions\Err;
use LaravelCommonNew\App\Helpers\AttrHelper;
use LaravelCommonNew\App\Helpers\ColorsHelper;
use LaravelCommonNew\App\Helpers\ReflectHelper;
use ReflectionEnum;
use ReflectionException;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GenEnumsToJSCommand extends Command
{
    protected $signature = 'GenEnumsToJSCommand';
    protected $description = 'Command description';
    private array $colors = [];

    /**
     * @return int
     * @throws Err
     * @throws ReflectionException
     */
    public function handle(): int
    {
        $this->initColors();
        $str = '';
        foreach (File::files(app_path("Enums")) as $item) {
            $enum = ReflectHelper::GetEnumByPath($item->getFilename());
            $this->genFile($enum, $str);
        }
        Storage::put('index.ts', $str);
        $this->warn("生成成功:: storage/app/index.ts");
        return CommandAlias::SUCCESS;
    }

    /**
     * @param ReflectionEnum $enum
     * @param string $str
     * @return void
     */
    private function genFile(ReflectionEnum $enum, string &$str): void
    {
        $fileName = last(explode('\\', $enum->getName()));
        $arr = [];
        foreach ($enum->getCases() as $case) {
            $attr = AttrHelper::GetEnumAttr($case);
            if ($attr) {
                $color = ($attr->color) ?: $this->getRandomColor();
                $arr[] = ['label' => $attr->label, 'value' => $attr->value, 'color' => $color];
            } else {
                $value = $case->getValue()->value;
                $color = $this->getRandomColor();
                $arr[] = ['label' => $value, 'value' => $value, 'color' => $color];
            }
        }
        $str .= $enum->getDocComment() . PHP_EOL;
        $str .= "export const $fileName =" . json_encode($arr, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    private function initColors()
    {
        $this->colors = ColorsHelper::GetAllColors();
    }

    private function getRandomColor()
    {
        return $this->colors[array_rand($this->colors)];
    }
}
