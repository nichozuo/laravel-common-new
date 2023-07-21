<?php

namespace LaravelCommonNew\App\Base;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LaravelCommonNew\App\Exceptions\ErrConst;

/**
 * @method static ifWhereLike(array $params, string $key, ?string $field = null): Builder
 * @method static order(string $key = 'orderBy'): Builder
 * @method static page(): LengthAwarePaginator
 * @method static getById(int $id, bool $throw = false, bool $lock = false): Model|Builder|null
 * @method static unique(array $params, array $keys, string $label = null, string $field = 'id'): Builder
 *
 * @method static create(array $params)
 * @method static where(string $field, string $value)
 * @method static findOrFail(int $id)
 * @method static selectRaw(string $raw)
 * @method static whereIn(string $field, array $array)
 * @method static defaultOrder()
 */
class BaseModel extends Model
{
    /**
     * @param Builder $builder
     * @param array $params 请求参数
     * @param string $key 请求参数的key
     * @param string|null $field 字段名
     * @return Builder
     */
    public function scopeIfWhereLike(Builder $builder, array $params, string $key, ?string $field = null): Builder
    {
        return ($params[$key] ?? false) ? $builder->where($field ?? $key, 'like', "%$params[$key]%") : $builder;
    }

    /**
     * @param Builder $builder
     * @param string $key
     * @return Builder
     */
    public function scopeOrder(Builder $builder, string $key = 'orderBy'): Builder
    {
        $params = request()->validate([
            $key => 'nullable|array',
        ]);
        if ($params[$key] ?? false) {
            $orderBy = $params[$key];
            if (count($orderBy) == 2) {
                $field = $orderBy[0];
                $sort = $orderBy[1] == 'descend' ? 'desc' : 'asc';
                return $builder->orderBy($field, $sort);
            }
        }
        return $builder->orderByDesc('id');
    }

    /**
     * @param Builder $builder
     * @return LengthAwarePaginator
     * @throws Exception
     */
    public function scopePage(Builder $builder): LengthAwarePaginator
    {
        $perPage = request()->validate([
            'perPage' => 'nullable|integer',
        ])['perPage'] ?? 10;

        $allow = config('common.perPageAllow', [10, 20, 50, 100]);
        if (!in_array($perPage, $allow))
            throw new Exception(...ErrConst::PerPageIsNotAllow);

        return $builder->paginate($perPage);
    }

    /**
     * @param Builder $builder
     * @param int $id
     * @param bool $throw
     * @param bool $lock
     * @return Model|Builder|null
     */
    public function scopeGetById(Builder $builder, int $id, bool $throw = true, bool $lock = false): Model|Builder|null
    {
        $builder = $builder->where('id', $id);
        if ($lock) {
            $builder = $builder->lockForUpdate();
        }
        if ($throw) {
            return $builder->firstOrFail();
        }
        return $builder->first();
    }

    /**
     * @param Builder $builder
     * @param array $params
     * @param array $keys
     * @param string|null $label
     * @param string $field
     * @return Builder
     * @throws Exception
     */
    public function scopeUnique(Builder $builder, array $params, array $keys, string $label = null, string $field = 'id'): Builder
    {
        $data = Arr::only($params, $keys);
        $model = $builder->where($data)->first();
        if ($model && $label != null) {
            if (!isset($params[$field]) || $model->$field != $params[$field])
                throw new Exception("{$label}【{$params[$keys[0]]}】已存在，请重试");
        }
        return $builder;
    }
}