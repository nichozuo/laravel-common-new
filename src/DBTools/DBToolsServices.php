<?php

namespace LaravelCommonNew\DBTools;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
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
     * @return DBModel
     */
    public static function Remember(): DBModel
    {
        return Cache::store('file')->rememberForever('DBModel', function () {
            return self::Gen();
        });
    }

    /**
     * @return void
     * @throws Exception
     */
    public static function CacheIt(): void
    {
        Cache::store('file')->put('DBModel', self::Gen());
    }

    /**
     * @return DBModel
     * @throws Exception
     */
    public static function Gen(): DBModel
    {
        $sm = DB::getDoctrineSchemaManager();
        $sm->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

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
                $columnModel = self::parseColumnModel($dbModel, $column);
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
    private static function parseColumnModel(DBModel $dbModel, Column $column): ColumnModel
    {
        $name = $column->getName();
        $comment = $column->getComment();

        $model = new ColumnModel();
        $model->name = $name;
        $model->type = $column->getType()->getName();
        $model->typeString = Constants::ColumnType[$model->type] ?? 'unknown';
        $model->length = $column->getLength();
        $model->precision = $column->getPrecision();
        $model->nullable = $column->getNotnull();
        $model->nullableString = $model->nullable ? 'required' : 'nullable';
        $model->comment = $comment;
        $model->default = $column->getDefault();

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
        if (Str::of($name)->contains('_id') && !$model->isForeignKey) {
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
}