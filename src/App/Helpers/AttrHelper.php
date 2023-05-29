<?php

namespace LaravelCommonNew\App\Helpers;

use Illuminate\Support\Str;
use LaravelCommonNew\App\Attributes\ActionAttribute;
use LaravelCommonNew\App\Attributes\ControllerAttribute;
use LaravelCommonNew\App\Attributes\EnumAttribute;
use ReflectionClass;
use ReflectionEnumBackedCase;
use ReflectionEnumPureCase;
use ReflectionMethod;

class AttrHelper
{
    /**
     * @param ReflectionClass $classRef
     * @return ControllerAttribute|null
     */
    public static function GetControllerAttr(ReflectionClass $classRef): ?ControllerAttribute
    {
        $attr = self::_getControllerAttr($classRef);

        // Fill properties
        $className = $classRef->name;

        $modules = str_replace("LaravelCommonNew\\", '', $className);
        $modules = str_replace("App\\Modules\\", '', $modules);
        $modules = explode('\\', $modules);
        $className1 = last($modules);
        $className1 = str_replace("Controller", '', $className1);
        array_pop($modules);
        $modules[] = $className1;
        $modules = array_map(function ($v) {
            return Str::snake($v);
        }, $modules);

        $routePrefix = "/" . implode('/', $modules);
        $routeName = implode('.', $modules) . '.';

        $attr->className = $className;
        $attr->modules = $modules;
        $attr->routePrefix = $routePrefix;
        $attr->routeName = $routeName;

        return $attr;
    }

    /**
     * @param ReflectionMethod $method
     * @param ControllerAttribute|null $cAttr
     * @return ActionAttribute|null
     */
    public static function GetActionAttr(ReflectionMethod $method, ?ControllerAttribute $cAttr = null): ?ActionAttribute
    {
        $attr = self::_getActionAttr($method);

        // Fill properties
        $attr->name = $method->getName();
        $attr->uri = Str::snake($method->getName());

        if ($cAttr)
            $attr->fullUri = '/api/' . implode('/', $cAttr->modules) . '/' . $attr->uri;

        $attr->params = ReflectHelper::ParseMethodParams($method);

        return $attr;
    }

    /**
     * @param ReflectionClass $classRef
     * @return ControllerAttribute|null
     */
    private static function _getControllerAttr(ReflectionClass $classRef): ?ControllerAttribute
    {
        $attr = $classRef->getAttributes(ControllerAttribute::class);
        if (!$attr) {
            return new ControllerAttribute();
        }
        return $attr[0]->newInstance();
    }

    /**
     * @param ReflectionMethod $method
     * @return ActionAttribute|null
     */
    private static function _getActionAttr(ReflectionMethod $method): ?ActionAttribute
    {
        $attr = $method->getAttributes(ActionAttribute::class);
        if (!$attr) {
            return new ActionAttribute();
        }
        return $attr[0]->newInstance();
    }

    /**
     * @param ReflectionEnumPureCase|ReflectionEnumBackedCase $case
     * @return ?EnumAttribute
     */
    public static function GetEnumAttr(ReflectionEnumPureCase|ReflectionEnumBackedCase $case): ?EnumAttribute
    {
        $attr = $case->getAttributes(EnumAttribute::class);
        if (!$attr)
            return null;
//            Err::Throw("Get enum attr error");

        $a = $attr[0]->newInstance();
        $a->label = $case->getValue()->value;
        $a->value = $case->getValue()->value;
        return $a;
    }
}
