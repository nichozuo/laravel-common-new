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

        $content = self::loadStub("BaseModel");
        $content = self::replaceAll([
            'useSoftDeletes' => $table->hasSoftDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' . PHP_EOL : '',
            'useRelations' => $table->hasRelations ? 'use Illuminate\Database\Eloquent\Relations;' . PHP_EOL . 'use App\Models;' . PHP_EOL : '',
            'properties' => implode(PHP_EOL, $table->properties),
            'methods' => '',
            'modelName' => $table->modelName,
            'useSoftDeletesTrait' => $table->hasSoftDeletes ? ', SoftDeletes' : '',
            'name' => $table->name,
            'comment' => $table->comment,
            'fillable' => implode(', ', $table->fillable),
            'relations' => $table->relationsStr,
        ], $content);
        self::saveFile(app_path("Models/Base/Base$table->modelName.php"), $content, $force);

        $content = self::loadStub("Model");
        $content = self::replaceAll([
            'modelName' => $table->modelName,
        ], $content);
        self::saveFile(app_path("Models/$table->modelName.php"), $content, $force);
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

        $content = self::loadStub("Controller");
        $content = self::replaceAll([
//            'useSoftDeletes' => $table->hasSoftDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' . PHP_EOL : '',
            'moduleName' => implode('\\', $moduleName),
            'modelName' => $table->modelName,
            'comment' => $table->comment,
            'validateString' => implode(PHP_EOL . "\t\t\t", $table->validateString),
        ], $content);
        $moduleName = implode('/', $moduleName);
        self::saveFile(app_path("Modules/$moduleName/{$table->modelName}Controller.php"), $content, $force);
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