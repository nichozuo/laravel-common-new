<?php

namespace LaravelCommonNew\DBTools;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LaravelCommonNew\DBTools\Models\ColumnModel;
use LaravelCommonNew\DBTools\Models\Constants;
use LaravelCommonNew\DBTools\Models\DBModel;
use LaravelCommonNew\DBTools\Models\TableModel;

class DBToolsServices
{
    /**
     * @return void
     * @throws Exception
     */
    public static function CacheAll(): void
    {
        Cache::store('file')->put('_dev_DBModel', self::Gen());
    }

    /**
     * @return DBModel
     */
    public static function GetTables(): DBModel
    {
        return Cache::store('file')->rememberForever('_dev_DBModel', function () {
            return self::Gen();
        });
    }

    /**
     * @param string $tableName
     * @return TableModel
     * @throws \Exception
     */
    public static function GetTable(string $tableName): TableModel
    {
        $tables = Cache::store('file')->rememberForever('_dev_DBModel', function () {
            return self::Gen();
        });

        $table = $tables->tables[$tableName] ?? null;
        if (!$table)
            throw new \Exception("table $tableName not found");

        return $table;
    }

    /**
     * @return DBModel
     * @throws Exception
     */
    public static function Gen(): DBModel
    {
        $sm = DB::getDoctrineSchemaManager();
        $sm->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $types = self::GetTypes();

        // process db model
        $tables = $sm->listTables();
        $dbModel = new DBModel();
        foreach ($tables as $table) {
            $dbModel->tableKeys[] = $table->getName();
        }

        // process table model
        foreach ($tables as $table) {
            $tableModel = self::parseTableModel($table);
            // process column model
            foreach ($table->getColumns() as $column) {
                $columnModel = self::parseColumnModel($dbModel, $column, $types);
                $tableModel->columns[$column->getName()] = $columnModel;
                if ($columnModel->isForeignKey) {
                    $tableModel->foreignKeys[$columnModel->name] = $columnModel->foreignTable;
                }
            }
            $tableModel->parse($dbModel);
            $dbModel->tables[$table->getName()] = $tableModel;
        }

        // 处理BelongsTo关联关系
        foreach ($dbModel->tables as $tableModel) {
            // hasMany
            $tableModel->hasMany = self::parseHasMany($dbModel, $tableModel);
            // belongsTO
            $tableModel->belongsTo = self::parseBelongsTo($tableModel);
            // belongsToMany

            $tableModel->relationsStr = self::parseRelationsStr($tableModel);

            if (count($tableModel->hasMany) || count($tableModel->belongsTo))
                $tableModel->hasRelations = true;
        }
        return $dbModel;
    }

    /**
     * @param Table $table
     * @return TableModel
     */
    private static function parseTableModel(Table $table): TableModel
    {
        $model = new TableModel();
        $model->name = $table->getName();
        $model->comment = $table->getComment();
        return $model;
    }

    /**
     * @param DBModel $dbModel
     * @param Column $column
     * @return ColumnModel
     */
    private static function parseColumnModel(DBModel $dbModel, Column $column, array $types): ColumnModel
    {
        $name = $column->getName();
        $comment = $column->getComment();

        $model = new ColumnModel();
        $model->name = $name;
        $model->type = $types[$column->getType()::class] ?? 'unknown';
        $model->typeString = $types[$column->getType()::class] ?? 'unknown';
        $model->length = $column->getLength();
        $model->precision = $column->getPrecision();
        $model->scale = $column->getScale();
        $model->notNull = $column->getNotnull();
        $model->nullableString = $model->notNull ? 'required' : 'nullable';
        $model->comment = $comment;
        $model->default = $column->getDefault();
        $model->isPrimaryKey = $column->getName() == 'id';

        // foreign key
        // 备注中有：ref[表名]
        if (Str::of($comment)->contains("ref[")) {
            $foreignTableName = Str::of($comment)->between("ref[", "]");
            if (in_array($foreignTableName, $dbModel->tableKeys)) {
                $model->isForeignKey = true;
                $model->foreignTable = $foreignTableName;
            }
        }
        // 列名称：表名+_id
        if (Str::of($name)->contains('_id') && !$model->isForeignKey && $column->getType()->getName() == 'bigint') {
            $foreignTableName = Str::of($name)->before('_id');
            if (in_array($foreignTableName, $dbModel->tableKeys)) {
                $model->isForeignKey = true;
                $model->foreignTable = $foreignTableName;
            }
        }

        return $model;
    }

    /**
     * @param DBModel $dbModel
     * @param TableModel $tableModel
     * @return array
     */
    private static function parseHasMany(DBModel $dbModel, TableModel $tableModel): array
    {
        $hasMany = [];
        foreach ($dbModel->tables as $table) {
            // 排除自己
            if ($table->name == $tableModel->name)
                continue;
            // 是否有跟自己相关的外键
            foreach ($table->foreignKeys as $foreignKey => $foreignTableName) {
                if ($foreignTableName == $tableModel->name)
                    $hasMany[$table->name] = [
                        'related' => Str::of($table->name)->studly()->toString(),
                        'foreignKey' => $foreignKey,
                        'localKey' => 'id'
                    ];
            }
        }
        return $hasMany;
    }

    /**
     * @param TableModel $tableModel
     * @return array
     */
    private static function parseBelongsTo(TableModel $tableModel): array
    {
        $belongsTo = [];
        foreach ($tableModel->foreignKeys as $foreignKey => $foreignTableName) {
            $belongsTo[Str::of($foreignTableName)->singular()->toString()] = [
                'related' => Str::of($foreignTableName)->studly()->toString(),
                'foreignKey' => $foreignKey,
                'ownerKey' => 'id'
            ];
        }
        return $belongsTo;
    }

    /**
     * @param TableModel $tableModel
     * @return string
     */
    private static function parseRelationsStr(TableModel $tableModel): string
    {
        $str = "# relations" . PHP_EOL;

        // hasMany
        foreach ($tableModel->hasMany as $key => $value) {
            $str .= "    public function $key(): Relations\HasMany
    {
        return \$this->hasMany(Models\\{$value['related']}::class, '{$value['foreignKey']}', '{$value['localKey']}');
    }" . PHP_EOL . PHP_EOL;
        }

        // belongsTo
        foreach ($tableModel->belongsTo as $key => $value) {
            $str .= "    public function $key(): Relations\BelongsTo
    {
        return \$this->belongsTo(Models\\{$value['related']}::class, '{$value['foreignKey']}', '{$value['ownerKey']}');
    }" . PHP_EOL . PHP_EOL;
        }

        return $str;
    }

    /**
     * @return array
     */
    public static function GenDocTree(): array
    {
        $tree = [];
        foreach (self::GetTables()->tables as $table) {
            $tree[] = [
                'key' => $table->name,
                'title' => $table->name,
                'description' => $table->comment,
                'isLeaf' => true,
            ];
        }
        return $tree;
    }

    /**
     * @return array
     */
    public static function GenDocList(): array
    {
        $nodes = [];
        foreach (self::GetTables()->tables as $table) {
            $columns = [];
            foreach ($table->columns as $key => $column) {
                $columns[] = $column;
            }

            $nodes[$table->name] = [
                'key' => $table->name,
                'title' => $table->name,
                'description' => $table->comment,
                'columns' => $columns,
            ];
        }
        return $nodes;
    }

    /**
     * @return array
     */
    private static function GetTypes(): array
    {
        $types = [];
        foreach (Type::getTypesMap() as $key => $value) {
            $types[$value] = $key;
        }
        return $types;
    }
}