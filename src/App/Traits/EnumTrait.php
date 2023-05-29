<?php

namespace LaravelCommonNew\App\Traits;


trait EnumTrait
{
    /**
     * @return array
     */
    public static function columns(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param string $name
     * @return string
     */
    public static function comment(string $name): string
    {
        return $name . ':' . implode(',', self::columns());
    }

    /**
     * @return array
     */
    public static function nameAndValue(): array
    {
        $data = [];
        foreach (self::cases() as $item) {
            $data[] = [
                'name' => $item->name,
                'value' => $item->value,
            ];
        }
        return $data;
    }
}
