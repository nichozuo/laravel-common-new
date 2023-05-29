<?php

namespace LaravelCommonNew\App\Helpers;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class GenHelper
{
    /**
     * @param Table|null $table
     * @return string
     */
    public static function GenTableString(?Table $table): string
    {
        return "protected \$table = '{$table->getName()}';";
    }

    /**
     * @param Table|null $table
     * @return string
     */
    public static function GenTableCommentString(?Table $table): string
    {
        return "protected string \$comment = '{$table->getComment()}';";
    }

    /**
     * @param array $columns
     * @return string
     */
    public static function GenTableFillableString(array $columns): string
    {
        $columns = array_keys($columns);
        $fillable = implode("', '", $columns);
        return "protected \$fillable = ['$fillable'];" . PHP_EOL;
    }

    /**
     * @param Column[] $columns
     * @param string $tab
     * @return string
     */
    public static function GenColumnsRequestValidateString(?array $columns, string $tab = ''): string
    {
        $t1 = '';
        if (empty($columns)) return $t1;

        foreach ($columns as $item) {
            $name = $item->getName();
            $required = TableHelper::GetColumnRequired($item);
            $type = TableHelper::GetColumnType($item);
            $comment = $item->getComment();
            $t1 .= "$tab'$name' => '$required|$type', # $comment";
            if ($item != end($columns))
                $t1 .= PHP_EOL;
        }
        return $t1;
    }

    /**
     * @param array $columns
     * @return string
     */
    public static function GenColumnsInsertString(array $columns): string
    {
        $t1 = '';
        foreach ($columns as $item) {
            $name = $item->getName();
            $comment = $item->getComment();
            $t1 .= "'$name' => '', # $comment" . PHP_EOL;
        }
        return $t1;
    }

    /**
     * @param Table $table
     * @return string
     */
    public static function GenColumnsPropertiesString(Table $table): string
    {
        $t1 = '';
        foreach ($table->getColumns() as $item) {
            $name = $item->getName();
            $type = TableHelper::GetColumnType($item);
            $t1 .= " * @property $type \$$name" . PHP_EOL;
        }
        return $t1;
    }

    /**
     * @return string
     */
    public static function GenTableMethodsString(): string
    {
        return ' * @method static ifWhere(array $params, string $string)
 * @method static ifWhereLike(array $params, string $string)
 * @method static ifWhereIn(array $params, string $string)
 * @method static ifRange(array $params, string $string)
 * @method static create(array $array)
 * @method static unique(array $params, array $array, string $string)
 * @method static idp(array $params)
 * @method static findOrFail(int $id)
 * @method static selectRaw(string $string)
 * @method static withTrashed()
 ';
    }

    /**
     * @param Table $table
     * @return string
     */
    public static function GenTableRelations(Table $table): string
    {
        $t = '';
        // BelongsTo
        foreach ($table->getColumns() as $column) {
            $columnName = $column->getName();
            if (Str::endsWith($columnName, 's_id')) {
                $t1 = str_replace('_id', '', $columnName);
                $name = Str::singular($t1);

                $comment = $column->getComment();
                if ($comment && strpos($comment, 'ef[')) {
                    $comment = substr($comment, strpos($comment, 'ef[') + 3);
                    $t1 = substr($comment, 0, strpos($comment, ']'));
                }
                $modelName = Str::of($t1)->studly();

                $t .= <<<t
    public function $name(): Relations\BelongsTo
    {
        return \$this->belongsTo(Models\\$modelName::class, '$columnName', 'id');
    }

t;
            }
        }

        // HasMany
        $tables = TableHelper::GetTables();
        $foreignKey = $table->getName() . '_id';
        foreach ($tables as $table) {
            foreach ($table->getColumns() as $column) {
                if ($column->getName() == $foreignKey) {
                    $name = $table->getName();
                    $modelName = Str::of($name)->studly();
                    $t .= <<<t
    public function $name(): Relations\HasMany
    {
        return \$this->hasMany(Models\\$modelName::class, '$foreignKey', 'id');
    }

t;
                }
            }
        }
        return $t;
    }

    /**
     * @param string $nameSpace
     * @param string $controllerFilePath
     * @return string
     * @throws ReflectionException
     * @throws Exception
     */
    public static function GenTestContent(string $nameSpace, string $controllerFilePath): string
    {
        $ref = new ReflectionClass($nameSpace);
        $content = '';
        foreach ($ref->getMethods() as $method) {
            if ($method->class != $nameSpace || $method->getModifiers() != 1 || $method->isConstructor())
                continue;
            $attr = AttrHelper::GetActionAttr($method);
//            $methodName = $method->getName();
            $methodName1 = Str::studly($attr->name);
//            $attr = AttrHelper::GetActionAttr($method);
////            $data = ReflectHelper::GetMethodAnnotation($nameSpace, $methodName);
//            $intro = $attr->name ?? '';

//            $params = ReflectHelper::GetMethodParamsArray($controllerFilePath, $nameSpace, $methodName);
            $paramsContent = [];
            foreach ($attr->params as $item) {
                $paramsContent[] = "            '{$item['key']}' => '', # {$item['comment']}";
            }
            $paramsContent = implode(PHP_EOL, $paramsContent);
            $content .= <<<content
    /**
     * @intro $attr->title
     */
    public function test$methodName1()
    {
        \$this->go(__METHOD__, [
$paramsContent
        ]);
    }
content. PHP_EOL;

        }
        return $content;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return string
     * @throws ReflectionException
     */
    public static function GenApiMD(string $className, string $methodName): string
    {
        $classRef = new ReflectionClass($className);
        $cAttr = AttrHelper::GetControllerAttr($classRef);
        $methodRef = $classRef->getMethod($methodName);
        $mAttr = AttrHelper::GetActionAttr($methodRef, $cAttr);
        $content = StubHelper::GetStub('api.md');
        $data = [
            'title' => $mAttr->title ?? $mAttr->uri,
            'desc' => $mAttr->desc,
            'fullUri' => $mAttr->fullUri,
            'methods' => implode(',', $mAttr->methods),
            'params' => self::GenTableFromArray($mAttr->params),
            'response' => $mAttr->response
        ];
        return StubHelper::ReplaceAll($data, $content);
    }

    /**
     * @param array|null $params
     * @return string
     */
    private static function GenTableFromArray(?array $params): string
    {
        if (!$params || count($params) == 0)
            return '- NULL' . PHP_EOL;

        $keys = array_keys($params[0]);
        $split = [];
        for ($i = 0; $i < count($keys); $i++) {
            $split[] = '----';
        }
        $lines = [];
        $lines[] = '|' . implode('|', $keys) . '|';
        $lines[] = '|' . implode('|', $split) . '|';

        foreach ($params as $item) {
            $lines[] = '|' . implode('|', $item) . '|';
        }
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param string $filePath
     * @param $className
     * @param $methodName
     * @return string
     * @throws ReflectionException
     */
    private static function getParams(string $filePath, $className, $methodName): string
    {
        $arr = ReflectHelper::GetMethodParamsArray($filePath, $className, $methodName);
        if (count($arr)) {
            $before = '|Params|Require|Type|Comment|' . PHP_EOL . '|----|----|----|----|' . PHP_EOL;
        } else {
            $before = '- NULL' . PHP_EOL;
        }
        $arr1 = [];
        foreach ($arr as $item) {
            $arr1[] = implode('|', $item);
        }
        return $before . implode(PHP_EOL, $arr1);
    }

    /**
     * @param array $data
     * @return string
     */
    private static function getResponseParams(array $data): string
    {
        $t1 = '';
        if (!isset($data['responseParams']))
            return $t1;
        $before = '### Response Params' . PHP_EOL . '|Params|Type|Comment|' . PHP_EOL . '|----|----|----|' . PHP_EOL;
        if (!is_array($data['responseParams'])) {
            $item = $data['responseParams'];
            $item = str_replace('nullable|', '- |', $item);
            $item = str_replace('required|', 'Y |', $item);
            $t1 .= '|' . str_replace(',', '|', $item) . '|' . PHP_EOL;
            return $before . $t1;
        }
        foreach ($data['responseParams'] as $item) {
            $item = str_replace('nullable|', '- |', $item);
            $item = str_replace('required|', 'Y |', $item);
            $t1 .= '|' . str_replace(',', '|', $item) . '|' . PHP_EOL;
        }
        return $t1;
    }

    /**
     * @param Table $table
     * @return string
     */
    public static function GenDatabaseMD(Table $table): string
    {
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[] = [
                'filed' => $column->getName(),
                'type' => $column->getType()->getName(),
                'length' => $column->getLength() == 0 ? '' : $column->getLength(),
                'precision' => $column->getPrecision() == 10 ? '' : $column->getPrecision(),
                'scale' => $column->getScale() == 0 ? '' : $column->getScale(),
                'not_null' => $column->getNotNull() ? 'Y' : '',
                'default' => $column->getDefault(),
                'comment' => $column->getComment()
            ];
        }

        $data = [
            'name' => $table->getName(),
            'comment' => $table->getComment(),
            'columns' => self::GenTableFromArray($columns)
        ];
        $content = StubHelper::GetStub('db.md');
        return StubHelper::ReplaceAll($data, $content);
    }
}
