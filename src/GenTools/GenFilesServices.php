<?php

namespace LaravelCommonNew\GenTools;

use Exception;
use Illuminate\Support\Facades\File;
use JetBrains\PhpStorm\NoReturn;
use LaravelCommonNew\DBTools\DBToolsServices;

class GenFilesServices
{

    /**
     * @param string $tableName
     * @param bool $force
     * @return void
     * @throws Exception
     */
    #[NoReturn]
    public static function GenModels(string $tableName, bool $force): void
    {
        $table = DBToolsServices::GetTable($tableName);

        // 生成BaseModel
        $content = self::loadStub($table->hasSoftDeletes ? "BaseModelSoftDelete" : "BaseModel");
        $content = self::replaceAll([
            'properties' => implode(PHP_EOL, $table->properties),
            'methods' => '',
            'modelName' => $table->modelName,
            'name' => $table->name,
            'comment' => $table->comment,
            'fillable' => implode(', ', $table->fillable),
            'relations' => $table->relationsStr,
        ], $content);
        self::saveFile(app_path("Models/Base/Base$table->modelName.php"), $content, $force);

        // 生成Model
        $content = self::loadStub("Model");
        $content = self::replaceAll([
            'modelName' => $table->modelName,
        ], $content);
        self::saveFile(app_path("Models/$table->modelName.php"), $content, false);
    }

    /**
     * @param array $moduleName
     * @param string $tableName
     * @param mixed $force
     * @return void
     * @throws Exception
     */
    public static function GenController(array $moduleName, string $tableName, mixed $force): void
    {
        $table = DBToolsServices::GetTable($tableName);

        $content = self::loadStub($table->hasSoftDeletes ? "ControllerSoftDelete" : "Controller");
        $content = self::replaceAll([
            'moduleName' => implode('\\', $moduleName),
            'modelName' => $table->modelName,
            'comment' => $table->comment,
            'validateString' => implode(PHP_EOL . "\t\t\t", $table->validateString),
        ], $content);
        $moduleName = implode('/', $moduleName);
        self::saveFile(app_path("Modules/$moduleName/{$table->modelName}Controller.php"), $content, $force);
    }

    /**
     * @param string $key
     * @param mixed $force
     * @return void
     */
    public static function GenEnum(string $key, mixed $force): void
    {
        $content = self::loadStub("Enum");
        $content = self::replaceAll([
            'EnumName' => $key,
        ], $content);
        self::saveFile(app_path("Enums/$key.php"), $content, $force);
    }

    /**
     * @param string $stubName
     * @return string
     */
    private static function loadStub(string $stubName): string
    {
        $path = resource_path("stubs/$stubName.stub");
        if (!File::exists($path))
            $path = __DIR__ . "/stubs/$stubName.stub";
        return File::get($path);
    }

    /**
     * @param array $array
     * @param $content
     * @return string
     */
    private static function replaceAll(array $array, $content): string
    {
        foreach (array_keys($array) as $key) {
            if (isset($array[$key])) {
                $content = str_replace("{\$$key}", $array[$key], $content);
            }
        }
        return $content;
    }

    /**
     * @param string $filePath
     * @param string $content
     * @param bool $force
     * @return void
     */
    private static function saveFile(string $filePath, string $content, bool $force): void
    {
        $exists = File::exists($filePath);
        if (!$exists || $force) {
            File::makeDirectory(File::dirname($filePath), 0755, true, true);
            File::put($filePath, $content);
            dump("Make file...$filePath");
        } else {
            dump("File exist");
        }
    }
}