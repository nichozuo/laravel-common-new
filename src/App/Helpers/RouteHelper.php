<?php

namespace LaravelCommonNew\App\Helpers;

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionException;

class RouteHelper
{
    /**
     * @param $router
     * @return void
     * @throws ReflectionException
     */
    public static function Auto($router): void
    {
        $files = File::allFiles(app_path("Modules"));
        foreach ($files as $file) {
            $controller = ReflectHelper::ParseControllerFromFilepath($file->getPathname());
            self::Controller($router, $controller);
        }
    }

    /**
     * @param $router
     * @param string $moduleName
     * @return void
     * @throws ReflectionException
     */
    public static function Module($router, string $moduleName): void
    {
        self::Controller($router, "App\\Modules\\$moduleName");
    }

    /**
     * @throws ReflectionException
     */
    public static function Controller($router, string $className): void
    {
        $classRef = new ReflectionClass($className);
        $cAttr = AttrHelper::GetControllerAttr($classRef);
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
}
