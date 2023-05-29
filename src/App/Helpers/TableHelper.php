<?php


namespace LaravelCommonNew\App\Helpers;


use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

/**
 *
 */
class TableHelper
{
    const dbTypeToPHPType = [
        'float' => 'numeric',
        'double' => 'numeric',
        'decimal' => 'numeric',
        'bigint' => 'integer',
        'int' => 'integer',
        'integer' => 'integer',
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'date' => 'date',
        'datetime' => 'date',
        'timestamp' => 'date',
        'boolean' => 'boolean',
        'string' => 'string',
        'text' => 'string',
        'varchar' => 'string',
        'enum' => 'string',
        'array' => 'array',
        'json' => 'json',
        'geometry' => 'geometry',
    ];

    /**
     * @return AbstractSchemaManager
     */
    private static function SM(): AbstractSchemaManager
    {
        return DB::connection()->getDoctrineSchemaManager();
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public static function ReCache(): void
    {
        Cache::store('file')->delete('list_tables');
        self::GetTables();
    }

    /**
     * @return Table[]
     */
    public static function GetTables(): array
    {
        return Cache::store('file')->rememberForever('list_tables', function () {
            DbalHelper::register();
            $tables = self::SM()->ListTables();
            $a = [];
            foreach ($tables as $table) {
                $a[$table->getName()] = $table;
            }
            return $a;
        });
    }

    /**
     * @param string $tableName
     * @return Table|null
     */
    public static function GetTable(string $tableName): ?Table
    {
        return self::GetTables()[$tableName] ?? null;
    }

    /**
     * @param Table|null $table
     * @return array|null
     */
    public static function GetTableColumns(?Table $table): ?array
    {
        if (!$table)
            return null;

        $columns = $table->getColumns();
        $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($skipColumns as $column) {
            unset($columns[$column]);
        }
        return $columns;
    }

    /**
     * @param Column $column
     * @return string
     */
    public static function GetColumnRequired(Column $column): string
    {
        return ($column->getNotNull()) ? 'required' : 'nullable';
    }

    /**
     * @param Column $column
     * @return string|null
     */
    public static function GetColumnType(Column $column): ?string
    {
        $type = $column->getType()->getName();
        $columnTypes = self::dbTypeToPHPType;
        return $columnTypes[$type] ?? null;
    }

    /**
     * @param array $columns
     * @return bool
     */
    public static function GetColumnsHasSoftDelete(array $columns): bool
    {
        return in_array('deleted_at', array_keys($columns));
    }
}
