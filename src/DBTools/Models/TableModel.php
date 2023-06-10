<?php

namespace LaravelCommonNew\DBTools\Models;


use Illuminate\Support\Str;

class TableModel
{
    public string $name;
    public string $comment;
    public array $columns = [];

    public string $modelName; // 模型名称
    public array $properties = []; // BaseModel文件中的property注释
    public array $keys = []; // 所有的字段名
    public array $fillable = []; // 对应model文件中的fillable
    public string $fillableString;
    public array $foreignKeys = []; // 外键
    public array $belongsTo = []; // 关系
    public array $hasMany = []; // 关系

    public array $validateString = []; // 验证字符串
    public array $insertString = []; // 插入符串

    /**
     * @param DBModel $dbModel
     * @return void
     */
    public function parse(DBModel $dbModel): void
    {
        $this->modelName = Str::of($this->name)->studly();

        // @property
        foreach ($this->columns as $column) {
            $this->keys[] = $column->name;

            $this->properties[] = "@property {$this->parseColumnType($column->type)} \$$column->name";
        }

        // $fillable
        $fillable = array_diff($this->keys, ['id', 'created_at', 'updated_at', 'deleted_at']);
        foreach ($fillable as &$string) {
            $string = '\'' . $string . '\'';
        }
        $this->fillable = $fillable;
        $this->fillableString = implode(', ', $fillable);


        foreach ($this->columns as $column) {
            if (in_array($column->name, ['id', 'created_at', 'updated_at', 'deleted_at']))
                continue;

            // validateString
            $this->validateString[] = "'$column->name' => '$column->nullableString|$column->typeString', # $column->comment";

            // insertString
            $this->insertString[] = "'$column->name' => '', # $column->comment";
        }
    }

    /**
     * @param string $type
     * @return string
     */
    private function parseColumnType(string $type): string
    {
        $type = strtolower($type);
        return Constants::ColumnType[$type] ?? 'unknown';
    }
}
