<?php

namespace LaravelCommonNew\DBTools\Models;

class ColumnModel
{
    public string $name;
    public string $type;
    public string $typeString;
    public ?int $length;
    public int $precision;
    public bool $nullable;
    public string $nullableString;
    public ?string $comment;
    public ?string $default;
    public bool $isPrimaryKey = false;
    public bool $isForeignKey = false;
    public ?string $foreignTable;
}
