<?php

namespace LaravelCommonNew\GenTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
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
            $title = $this->parseTitle($enum->getDocComment());
            $this->genContent($enum, $str);
        }
        File::put('enums.ts', $str);
        $this->warn("生成成功:: enums.ts");
    }

    /**
     * @param bool|string $comment
     * @return mixed
     */
    private  function parseTitle(bool|string $comment): mixed
    {
        $title = '';
        if (preg_match('/\/\*\*\s*\n\s*\*\s*(.*?)\s*\n/', $comment, $matches)) {
            $title = $matches[1];
        }
        return $title;
    }

    private function genContent(ReflectionClass $enum, string &$str)
    {
        $fileName = last(explode('\\', $enum->getName()));
        $arr = [];
        foreach ($enum->getConstants() as $case) {
            $const = $case->get($case);
            dd($const->getDocComment());
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
}
