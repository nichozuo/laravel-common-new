<?php

namespace LaravelCommonNew\App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ExceptionRender
{
    /**
     * @param Throwable $e
     * @return JsonResponse
     */
    public static function Render(Throwable $e): JsonResponse
    {
        $class = get_class($e);
        $request = request();
        $isDebug = config('app.debug');

        $debugInfo = [
            'request' => [
                'client' => $request->getClientIps(),
                'method' => $request->getMethod(),
                'uri' => $request->getPathInfo(),
                'params' => $request->all(),
            ],
            'exception' => [
                'class' => $class,
                'trace' => self::getTrace($e)
            ]
        ];

        $skipLog = self::getSkipLog($request->getPathInfo());
        if (!$skipLog)
            Log::error($e->getMessage(), $debugInfo);

        $code = $e->getCode() == 0 ? 999 : $e->getCode();
        $message = $e->getMessage();
        $status = 500;

        switch ($class) {
            case AuthenticationException::class:
                $code = 10000;
                $status = 401;
                break;
            case MethodNotAllowedHttpException::class:
                $status = 405;
                break;
            case NotFoundHttpException::class:
                $status = 404;
                break;
            case ValidationException::class:
                $keys = implode(",", array_keys($e->errors()));
                $message = "您提交的信息不完整：请查看【{$keys}】字段";
                $status = 400;
                break;
            case Err::class:
            default:
                break;
        }

        return response()->json([
            'code' => $code,
            'message' => $message,
            'debug' => $isDebug ? $debugInfo : null
        ], $status);
    }

    /**
     * @param Throwable $e
     * @return array
     */
    private static function getTrace(Throwable $e): array
    {
        $arr = $e->getTrace();
        $file = array_column($arr, 'file');
        $line = array_column($arr, 'line');
        $trace = [];
        for ($i = 0; $i < count($file); $i++) {
            if (!strpos($file[$i], '/vendor/'))
                $trace[] = [
                    $i => "$file[$i]($line[$i])"
                ];
        }
        return $trace;
    }

    /**
     * @param string $pathInfo
     * @return bool
     */
    private static function getSkipLog(string $pathInfo): bool
    {
        $skipLogPathInfo = config('common.skipLogPathInfo');
        if (!$skipLogPathInfo)
            return false;

        return in_array($pathInfo, $skipLogPathInfo);
    }
}
