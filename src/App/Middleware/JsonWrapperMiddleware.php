<?php

namespace LaravelCommonNew\App\Middleware;

use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JsonWrapperMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next): mixed
    {
        $response = $next($request);

        $base = ['code' => 0];

        // dontWrapJson
        $dontWrapJson = config('common.dontWrapJson', []);
        if (count($dontWrapJson)) {
            $pathInfo = $request->getPathInfo();
            if (in_array($pathInfo, $dontWrapJson)) {
                return $response;
            }
        }

        switch (get_class($response)) {
            case Exception::class:
                return response()->json([], 500);

            case JsonResponse::class:
                $data = $response->getData();
                $type = gettype($data);
                if ($type == 'object') {
                    // exception
                    if (property_exists($data, 'code') && property_exists($data, 'message') && $data->code !== 0)
                        return $response->setData($data);

                    // additions
                    if (property_exists($data, 'additions')) {
                        $base['additions'] = $data->additions;
                        unset($data->additions);
                    }

                    // pagination
                    if (property_exists($data, 'data') && property_exists($data, 'current_page')) {
                        $base['data'] = $data->data;
                        $base['meta'] = [
                            'total' => $data->total ?? 0,
                            'per_page' => (int)$data->per_page ?? 0,
                            'current_page' => $data->current_page ?? 0,
                            'last_page' => $data->last_page ?? 0
                        ];
                    } else {
                        $base['data'] = $data;
                    }
                } else {
                    if ($data != '' && $data != null) {
                        $base['data'] = $data;
                    }
                }
                return $response->setData($base);

            case BinaryFileResponse::class:
            case StreamedResponse::class:
                return $response;

            default:
                if ($response->getContent() == "") {
                    return response()->json($base);
                } else {
                    return $response;
                }
        }
    }
}
