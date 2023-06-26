<?php

namespace LaravelCommonNew\RouterTools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelCommonNew\App\Middleware\JsonWrapperMiddleware;
use LaravelCommonNew\RouterTools\Models\ControllerModel;
use LaravelCommonNew\RouterTools\Models\MethodModel;
use LaravelCommonNew\Utils\DocBlockReader;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;

class RouterToolsServices
{
    /**
     * @param $router
     * @return void
     * @throws ReflectionException
     */
    public static function AutoGenRouters($router): void
    {
        $appPath = app_path();
        $files = File::allFiles(app_path('Modules'));
        foreach ($files as $file) {
            $modulesName = str_replace($appPath . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR, '', $file->getPath());
            $controllerName = str_replace('.php', '', $file->getFilename());
            $className = str_replace(DIRECTORY_SEPARATOR, '\\', "App\\Modules\\$modulesName\\$controllerName");
            $prefix = self::getPrefix($modulesName);
            self::parseController($router, $prefix, $className);
        }
    }

    /**
     * @param $router
     * @param array $prefix
     * @param string $className
     * @return void
     * @throws ReflectionException
     */
    private static function parseController($router, array $prefix, string $className): void
    {
        $classRef = new ReflectionClass($className);
//        $classDocComment = DocBlockReader::parse($classRef->getDocComment());

        $controllerName = Str::snake(str_replace('Controller', '', $classRef->getShortName()));
        $prefix[] = $controllerName;
        $router->prefix(implode('/', $prefix))->name(implode('.', $prefix) . '.')->group(function ($router1) use ($classRef, $className) {
            foreach ($classRef->getMethods() as $method) {
                // 过滤方法
                if ($method->class != $className || $method->getModifiers() !== 1 || $method->isConstructor())
                    continue;

                $methodDocComment = DocBlockReader::parse($method->getDocComment());

                // 是否忽略在路由中
                if (isset($methodDocComment['skipRouter']))
                    continue;

                $middlewares = [];

                // 是否忽略授权
                if (!isset($methodDocComment['skipAuth'])) {
                    $middlewares[] = 'auth:sanctum';
                }

                // 是否忽略WrapJson
                if (!isset($methodDocComment['skipWrap'])) {
                    $middlewares[] = JsonWrapperMiddleware::class;
                }

                // method
                if (isset($methodDocComment['method'])) {
                    $methodName = explode('|', $methodDocComment['method']);
                } else {
                    $methodName = ['POST','GET'];
                }

                // uri
                $uri = Str::snake($method->name);

                // action
                $action = "$method->class@$method->name";
                $router1->match($methodName, $uri, $action)->middleware($middlewares)->name($uri);
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

    /**
     * @param string $className
     * @return array
     */
    private static function getPrefix(string $className): array
    {
        $arr = explode(DIRECTORY_SEPARATOR, $className);
        $result = [];
        foreach ($arr as $item) {
            $result[] = Str::snake($item);
        }
        return $result;
    }
}