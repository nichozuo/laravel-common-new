<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelCommonNew\RouterTools\Models\ControllerModel;
use LaravelCommonNew\RouterTools\Models\MethodModel;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;

class RouterToolsServices
{

    /**
     * @return mixed
     */
    public static function Remember(): mixed
    {
        return Cache::store('file')->rememberForever('DBModel', function () {
            return self::Gen();
        });
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public static function Gen(): void
    {
        $appPath = app_path();
        foreach (File::allFiles(app_path('Modules')) as $file) {
            dump($file->getPath());
            $controllerModel = self::parseControllerModel($file, $appPath);
        }
    }

    /**
     * @param SplFileInfo $file
     * @param string $appPath
     * @return ControllerModel
     * @throws ReflectionException
     */
    private static function parseControllerModel(SplFileInfo $file, string $appPath): ControllerModel
    {
        $model = new ControllerModel();

        // name
        $model->name = str_replace('Controller.php', '', $file->getFilename());

        // modules
        $modules = str_replace($appPath . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR, '', $file->getPath());
        $model->modules = explode(DIRECTORY_SEPARATOR, $modules);

        // namespace
        $model->namespace = implode('\\', ["App", "Modules", ...$model->modules, $model->name]) . 'Controller';

        // comment
        $classRef = new ReflectionClass($model->namespace);
        $model->comment = self::parseTitle($classRef->getDocComment());

        // methods
//        $model->methods = self::parseMethodModels($class, $model);
        foreach ($classRef->getMethods(ReflectionMethod::IS_PUBLIC) as $methodRef) {
            if ($methodRef->class != $classRef->getName() || $methodRef->name == '__construct')
                continue;
            $model->methods[$methodRef->getName()] = self::parseMethodModels($classRef, $methodRef);
        }

        dump($model);
        return $model;
    }

    /**
     * @param ReflectionClass $classRef
     * @param ReflectionMethod $methodRef
     * @return MethodModel
     */
    private static function parseMethodModels(ReflectionClass $classRef, ReflectionMethod $methodRef): MethodModel
    {
        $model = new MethodModel();

        // name
        $model->name = $methodRef->getName();

        // comment
        $comment = $methodRef->getDocComment();
        $model->title = self::parseTitle($comment);
        $args = self::parseArgs($comment);
        $model->intro = $args['intro'] ?? null;
        $model->method = $args['method'] ?? 'POST';
        $model->auth = $args['auth'] ?? true;

        // request params
        $model->params = self::parseMethodParams($methodRef);

        return $model;
    }

    /**
     * @param bool|string $comment
     * @return mixed
     */
    private static function parseTitle(bool|string $comment): mixed
    {
        $title = '';
        if (preg_match('/\/\*\*\s*\n\s*\*\s*(.*?)\s*\n/', $comment, $matches)) {
            $title = $matches[1];
        }
        return $title;
    }

    /**
     * @param bool|string $comment
     * @return array
     */
    private static function parseArgs(bool|string $comment): array
    {
        $params = array();
        preg_match_all('/@(\w+)\s+(\S+)/', $comment, $matches);

        for ($i = 0; $i < count($matches[1]); $i++) {
            $params[$matches[1][$i]] = $matches[2][$i];
        }
        return $params;
    }

    /**
     * @param ReflectionMethod $methodRef
     * @return array
     */
    private static function parseMethodParams(ReflectionMethod $methodRef): array
    {
        // code
        $startLine = $methodRef->getStartLine();
        $endLine = $methodRef->getEndLine();
        $length = $endLine - $startLine;
        $source = file($methodRef->getFileName());
        $codes = array_slice($source, $startLine, $length);

        // lines
        $strStart = ']);';
        $strEnd = '$params = $request->validate([';

        $start = $end = false;
        $lines = [];
        foreach ($codes as $code) {
            $t = trim($code);
            if ($t == $strStart) $end = true;
            if ($start && !$end)
                $lines[] = $t;
            if ($t == $strEnd) $start = true;
        }

        // params
        $params = [];
        foreach ($lines as $line) {
            if (Str::startsWith(trim($line), "//"))
                continue;
            $t1 = explode('\'', $line);
            if (count($t1) < 3) continue;
            $t2 = explode('|', $t1[3]);
            $t3 = explode('#', $t1[4]);
            $t4 = [
                'key' => str_replace('.*.', '.\*.', $t1[1]),
                'required' => $t2[0] == 'nullable' ? '-' : 'Y',
                'type' => $t2[1],
                'comment' => (count($t3) > 1) ? trim($t3[1]) : '-'
            ];
            $params[] = $t4;
        }

        return $params;
    }
}