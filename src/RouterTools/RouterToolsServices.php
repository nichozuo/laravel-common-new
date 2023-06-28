<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LaravelCommonNew\App\Middleware\JsonWrapperMiddleware;
use LaravelCommonNew\RouterTools\Models\ActionModel;
use LaravelCommonNew\RouterTools\Models\ControllerModel;
use LaravelCommonNew\RouterTools\Models\ParamModel;
use LaravelCommonNew\Utils\DocBlockReader;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class RouterToolsServices
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public static function Auto(): void
    {
        $controllers = self::GenRoutersModels();

        foreach ($controllers as $controller) {
            Route::prefix($controller->routerPrefix)
                ->name($controller->routerName)
                ->group(function ($router1) use ($controller) {
                    foreach ($controller->actions as $action) {
                        if (!$action->skipInRouter)
                            $router1->match($action->method, $action->uri, $action->action)
                                ->middleware($action->middlewares)
                                ->name("." . $action->uri);
                    }
                });
        }
    }

    /**
     * @return ControllerModel[]
     * @throws ReflectionException
     */
    public static function GenRoutersModels(): array
    {
        $appPath = app_path();
        $files = File::allFiles(app_path('Modules'));
        $controllers = [];
        foreach ($files as $file) {
            $pathName = $file->getPathname();
            $pathName = str_replace($appPath, '', $pathName);
            $pathName = str_replace('.php', '', $pathName);
            $pathName = "App" . str_replace(DIRECTORY_SEPARATOR, '\\', $pathName);
            $controllers[$pathName] = self::parseController($pathName);
        }
        return $controllers;
    }

    /**
     * @param string $pathName
     * @return ControllerModel
     * @throws ReflectionException
     */
    private static function parseController(string $pathName): ControllerModel
    {
        $controllerName = last(explode('\\', $pathName));
        $modulesName = str_replace('App\\Modules\\', '', $pathName);
        $modulesName = str_replace('\\' . $controllerName, '', $modulesName);

        $c = new ControllerModel();

        $classRef = new ReflectionClass($pathName);
        $intro = DocBlockReader::parse($classRef->getDocComment())['intro'] ?? '';

        $modules = explode('\\', $modulesName);
        $modules[] = $controllerName;
        $arr = array_map(function ($item) {
            $item = str_replace('Controller', '', $item);
            return Str::snake($item);
        }, $modules);

        $c->className = $pathName;
        $c->modulesName = $modulesName;
        $c->modules = $modules;
        $c->controllerName = $controllerName;
        $c->intro = $intro;
        $c->routerPrefix = implode('/', $arr);
        $c->routerName = implode('.', $arr);
        $c->actions = self::parseActions($classRef);
        return $c;
    }

    /**
     * @param ReflectionClass $classRef
     * @return ActionModel[]
     */
    private static function parseActions(ReflectionClass $classRef): array
    {
        $className = $classRef->getName();
        $actions = [];
        foreach ($classRef->getMethods() as $method) {
            // 过滤方法
            if ($method->class != $className || $method->getModifiers() !== 1 || $method->isConstructor())
                continue;

            $doc = DocBlockReader::parse($method->getDocComment());

            $action = new ActionModel();
            $action->intro = $doc['intro'] ?? '';
            $action->methodName = $method->name;
            $action->uri = Str::snake($method->name);
            $action->skipInRouter = isset($doc['skipInRouter']);
            $action->skipAuth = isset($doc['skipAuth']);
            $action->skipWrap = isset($doc['skipWrap']);
            $action->action = "$method->class@$method->name";

            // method
            if (isset($doc['method'])) {
                $methodName = explode('|', $doc['method']);
            } else {
                $methodName = ['POST'];
            }
            $action->method = $methodName;

            // middlewares
            $middlewares = [];
            if (!$action->skipAuth) $middlewares[] = 'auth:sanctum';
            if (!$action->skipWrap) $middlewares[] = JsonWrapperMiddleware::class;
            $action->middlewares = $middlewares;

            // params
            $action->params = self::getParameters($method);

            $actions[] = $action;
        }
        return $actions;
    }

    /**
     * @param ReflectionMethod $method
     * @return ParamModel[]
     */
    private static function getParameters(ReflectionMethod $method): array
    {
        // 获得方法的源代码
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $length = $endLine - $startLine;
        $source = file($method->getFileName());
        $lines = array_slice($source, $startLine, $length);

        // 解析每一行
        $strStart = ']);';
        $strEnd = '$params = $request->validate([';
        $start = $end = false;
        $arr1 = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t == $strStart) $end = true;
            if ($start && !$end)
                $arr1[] = $t;
            if ($t == $strEnd) $start = true;
        }

        // 解析参数
        $arr2 = [];
        foreach ($arr1 as $item) {
            if (Str::startsWith(trim($item), "//"))
                continue;
            $param = new ParamModel();
            $t1 = explode('\'', $item);
            if (count($t1) < 3) continue;
            $t2 = explode('|', $t1[3]);
            $t3 = explode('#', $t1[4]);
//            $t4 = [
//                'key' => str_replace('.*.', '.\*.', $t1[1]),
//                'required' => $t2[0] == 'nullable' ? '-' : 'Y',
//                'type' => $t2[1],
//                'comment' => (count($t3) > 1) ? trim($t3[1]) : '-'
//            ];
            $param->key = str_replace('.*.', '.\*.', $t1[1]);
            $param->required = $t2[0] != 'nullable';
            $param->type = $t2[1];
            $param->description = (count($t3) > 1) ? trim($t3[1]) : '-';
            $arr2[] = $param;
        }
        return $arr2;
    }
}