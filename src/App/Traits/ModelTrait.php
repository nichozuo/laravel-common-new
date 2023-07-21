<?php

namespace LaravelCommonNew\App\Traits;


use Closure;
use DateTimeInterface;
use LaravelCommonNew\App\Exceptions\ErrConst;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @method static ifWhereLike(array $params, string $key, string $field = '')
 * @method static unique(array $params, string[] $array, string $string)
 * @method static create(array $params)
 * @method static idp(array $params)
 */
trait ModelTrait
{
    /**
     * @param Builder $builder
     * @param array $params
     * @param string $key
     * @param array $fields
     * @return Builder
     */
    public function scopeIfWhereKeyword(Builder $builder, array $params, string $key, array $fields): Builder
    {
        $value = $params[$key] ?? null;
        if ($value) {
            return $builder->where(function ($q) use ($value, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%$value%");
                }
            });
        } else {
            return $builder;
        }
    }

    /**
     * @param int $id
     * @param bool $throw
     * @return static
     * @throws ErrConst
     */
    public static function GetById(int $id, bool $throw = true): static
    {
        $model = self::find($id);
        if (!$model && $throw) {
            $m = new static();
            $msg = $m->comment;
            ErrConst::Throw("[$msg]数据不存在");
        }
        return $model;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeDescID(Builder $query): Builder
    {
        return $query->orderByDesc('id');
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $relationName
     * @param Closure $closure
     * @return Builder
     */
    public function scopeIfWhereHas(Builder $query, array $params, string $key, string $relationName, Closure $closure): Builder
    {
        return $query->when(isset($params[$key]), function ($query) use ($relationName, $closure) {
            return $query->whereHas($relationName, $closure);
        });
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $relationName
     * @param string $field
     * @return Builder
     */
    public function scopeIfWhereHasWhereLike(Builder $query, array $params, string $key, string $relationName, string $field): Builder
    {
        return $query->when(isset($params[$key]), function ($q1) use ($params, $key, $relationName, $field) {
            return $q1->whereHas($relationName, function ($q2) use ($params, $key, $relationName, $field) {
                return $q2->where($field, 'like', "%{$params[$key]}%");
            });
        });
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $field
     * @return Builder
     */
    public function scopeIfWhereAtNotNull(Builder $query, array $params, string $key, string $field = ''): Builder
    {
        if (isset($params[$key])) {
            $field = ($field == '') ? $key : $field;
            return $params[$key] ? $query->whereNotNull($field) : $query->whereNull($field);
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $field
     * @return Builder
     */
    public function scopeIfWhere(Builder $query, array $params, string $key, string $field = ''): Builder
    {
        if (isset($params[$key])) {
            $field = ($field == '') ? $key : $field;
            return $query->where($field, $params[$key]);
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $field
     * @param array|null $values
     * @return Builder
     */
    public function scopeIfWhereIn(Builder $query, array $params, string $key, string $field = '', ?array $values = null): Builder
    {
        if (isset($params[$key])) {
            if ($values === null)
                $values = $params[$key];
            $field = ($field == '') ? $key : $field;
            return $query->whereIn($field, $values);
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $field
     * @param string $type
     * @param string $op1
     * @param string $op2
     * @return Builder
     * @throws ErrConst
     */
    public function scopeIfRange(Builder $query, array $params, string $key, string $field = '', string $type = 'datetime', string $op1 = '<', string $op2 = '>='): Builder
    {
        if (isset($params[$key])) {
            $field = ($field == '') ? $key : $field;
            $a = $params[$key];

            if (is_array($a) && count($a) != 2)
                throw ErrConst::Throw('The params of IfRange need be a array');

            // 数据类型
            if ($type == 'date') {
                $a[0] = $a[0] == "" ? "" : Carbon::parse($a[0])->toDateString();
                $a[1] = $a[1] == "" ? "" : Carbon::parse($a[1])->toDateString();
            } elseif ($type == 'datetime') {
                $a[0] = $a[0] == "" ? "" : Carbon::parse($a[0])->startOfDay()->toDateTimeString();
                $a[1] = $a[1] == "" ? "" : Carbon::parse($a[1])->endOfDay()->toDateTimeString();
            } elseif ($type == 'date_or_time') {
                $a[0] = $a[0] == "" ? "" : Carbon::parse($a[0])->toDateTimeString();
                $a[1] = $a[1] == "" ? "" : Carbon::parse(date('Y-m-d 23:59:59', strtotime($a[1])))->toDateTimeString();
            } else {
                $a[0] = $a[0] == "" ? "" : floatval($a[0]);
                $a[1] = $a[1] == "" ? "" : floatval($a[1]);
            }

            // 判断逻辑
            if ($a[0] == "" && $a[1] == "")
                return $query;
            else if ($a[0] == "")
                return $query->where($field, $op1, $a[1]);
            else if ($a[1] == "")
                return $query->where($field, $op2, $a[0]);
            else
                return $query->whereBetween($field, $a);
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param string $key
     * @return Builder
     */
    public function scopeOrder(Builder $query, string $key = 'orderBy'): Builder
    {
        $params = request()->only($key);
        if (isset($params[$key])) {
            $orderBy = $params[$key];
            if (count($orderBy) == 2) {
                if ($orderBy[1] == 'descend') {
                    return $query->orderBy($orderBy[0], 'desc');
                } elseif ($orderBy[1] == 'ascend') {
                    return $query->orderBy($orderBy[0]);
                }
            }
        }
        return $query->orderByDesc('id');
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param array $keys
     * @param string $field
     * @param string|null $label
     * @param bool $softDelete
     * @return Builder
     * @throws ErrConst
     */
    public function scopeUnique(Builder $query, array $params, array $keys, string $label = null, bool $softDelete = false, string $field = 'id'): Builder
    {
        $data = Arr::only($params, $keys);
        if ($softDelete)
            $model = $query->withTrashed()->where($data)->first();
        else
            $model = $query->where($data)->first();
        if ($model && $label != null) {
            if (!isset($params[$field]) || $model->$field != $params[$field])
                throw ErrConst::Throw("{$label}【{$params[$keys[0]]}】已存在，请重试");
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $params
     * @param string $key
     * @param string $field
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function scopeIdp(Builder $query, array $params, string $key = 'id', string $field = 'id'): Model|Collection|Builder|array|null
    {
        return $query->findOrFail($params[$key]);
    }

    /**
     * @param Builder $query
     * @param string $selectRaw
     * @return Builder
     */
    public function scopeWithSoftDeleted(Builder $query, string $selectRaw): Builder
    {
        $arr = explode(':', $selectRaw);
        return $query->with([$arr[0] => function ($q) use ($arr) {
            $q->withTrashed()->selectRaw($arr[1]);
        }]);
    }

    /**
     * @param $keys
     * @param $params
     * @param null $errMessage
     * @return bool
     * @throws ErrConst
     */
    public static function CheckUnique($keys, $params, $errMessage = null): bool
    {
        $where = Arr::only($params, $keys);
        $model = self::where($where)->first();
        if (!$model) {
            return true;
        } else {
            if ($errMessage != null)
                throw ErrConst::Throw($errMessage);
            return false;
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws ErrConst
     */
    public static function findOrError($id): mixed
    {
        $model = self::find($id);
        if (!$model)
            throw ErrConst::Throw("没有此【" . self::$name . "】记录");
        return $model;
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return string
     */
    public function serializeDate(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param Builder $query
     * @param array $pop 需要排除的字段数组
     * @param array $push 需要增加的字段数组
     * @return Builder
     */
    public function scopeUnSelect(Builder $query, array $pop = [], array $push = []): Builder
    {
        $fields = array_merge(['id'], $this->fillable, $push);
        $fields = array_diff($fields, $pop);
        return $query->select($fields);
    }

    /**
     * @param Builder $query
     * @param string $popStr 需要排除的字段字符串，不要空格，逗号隔开
     * @param string $pushStr 需要增加的字段字符串，不要空格，逗号隔开
     * @return Builder
     */
    public function scopeUnSelectRaw(Builder $query, string $popStr = '', string $pushStr = ''): Builder
    {
        $pop = explode(',', $popStr);
        $push = explode(',', $pushStr);
        $fields = array_merge(['id'], $this->fillable, $push);
        $fields = array_diff($fields, $pop);
        return $query->select($fields);
    }

    /**
     * @param $query
     * @param $params
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function scopeDownload($query, $params, $page, $pageSize): mixed
    {
        $type = $params['download_type'];
        if ($type == 1) {
            return $query->forPage($page, $pageSize);
        } else {
            return $query;
        }
    }
}

