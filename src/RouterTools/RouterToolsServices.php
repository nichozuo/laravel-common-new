<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LaravelCommonNew\App\Middleware\JsonWrapperMiddleware;
use LaravelCommonNew\RouterTools\Models\ActionModel;
use LaravelCommonNew\RouterTools\Models\ControllerModel;
use LaravelCommonNew\Utils\DocBlockReader;
use ReflectionClass;
use ReflectionException;

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

            $action = new ActionModel();

            $doc = DocBlockReader::parse($method->getDocComment());

            $action->intro = $doc['intro'] ?? '';
            $action->methodName = $method->name;
            $action->uri = Str::snake($method->name);
            $action->method = $doc['method'] ?? 'POST';

            $action->skipInRouter = isset($doc['skipInRouter']);
            $action->skipAuth = isset($doc['skipAuth']);
            $action->skipWrap = isset($doc['skipWrap']);

            $middlewares = [];
            if (!$action->skipAuth) $middlewares[] = 'auth:sanctum';
            if (!$action->skipWrap) $middlewares[] = JsonWrapperMiddleware::class;
            $action->middlewares = $middlewares;

            $action->action = "$method->class@$method->name";

            $actions[] = $action;

//            // method
//            if (isset($methodDocComment['method'])) {
//                $methodName = explode('|', $methodDocComment['method']);
//            } else {
//                $methodName = ['POST', 'GET'];
//            }

            // action
//            $router1->match($methodName, $uri, $action)->middleware($middlewares)->name($uri);
        }
        return $actions;
    }
}