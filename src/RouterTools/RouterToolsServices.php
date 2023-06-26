<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelCommonNew\RouterTools\Models\ControllerModel;
use LaravelCommonNew\RouterTools\Models\MethodModel;
use PhpDocReader\PhpDocReader;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Symfony\Component\Finder\SplFileInfo;

class RouterToolsServices
{
    /**
     * @param $router
     * @return void
     */
    public static function AutoGenRouters($router): void
    {
        $appPath = app_path();
        $files = File::allFiles(app_path('Modules'));
        foreach ($files as $file) {
            $modulesName = str_replace($appPath . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR, '', $file->getPath());
            $controllerName = str_replace('.php', '', $file->getFilename());
            $className = str_replace(DIRECTORY_SEPARATOR, '\\', "App\\Modules\\$modulesName\\$controllerName");
            self::parseController($router, $className);
        }
    }

    /**
     * @param $router
     * @param string $className
     * @return void
     * @throws ReflectionException
     */
    private static function parseController($router, string $className): void
    {
        $controllerRef = new ReflectionClass($className);
        $reader = new PhpDocReader();
        $parameter = new ReflectionParameter([$className, 'list']);
        dd($parameter);

        $cAttr = AttrHelper::GetControllerAttr($controllerRef);
        if ($cAttr->auth == 1)
            $router = $router->middleware(['auth:sanctum']);
        $router->prefix($cAttr->routePrefix)->name($cAttr->routeName . ".")->group(function ($router1) use ($classRef, $className) {
            foreach ($classRef->getMethods() as $method) {
                if ($method->class != $className || $method->getModifiers() !== 1 || $method->isConstructor())
                    continue;
                $attr = AttrHelper::GetActionAttr($method);
                $router1->match($attr->methods, $attr->uri, "$method->class@$method->name")->name($attr->uri);
            }
        });
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