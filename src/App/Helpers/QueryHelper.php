<?php

namespace LaravelCommonNew\App\Helpers;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryHelper
{
    public static function ifDownload(EloquentBuilder|QueryBuilder $query, array $params, string $key, Closure $closure)
    {
        $query->when(!isset($params['download_type']) || $params['download_type'] == 2, function ($q) use ($params, $key, $closure) {
            if (isset($params[$key])) {
                $closure($q, $params[$key]);
            }
            return $q;
        });
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param array $params
     * @param string $key
     * @param string|null $field
     * @return void
     */
    public static function ifWhere(EloquentBuilder|QueryBuilder $query, array $params, string $key, string $field = null): void
    {
        if (isset($params[$key])) {
            $query->where($field ?? $key, $params[$key]);
        }
    }
}
