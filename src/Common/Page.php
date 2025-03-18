<?php

namespace Finalx\Webman\Common;


use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JustSteveKing\StatusCode\Http;
use support\Db;
use Webman\Exception\NotFoundException;


class Page
{
    const DEFALUT_PAGE_DTO = ['page' => 1, 'pageSize' => 10, 'where' => null, 'orderBy' => null];
    /**
     * 构建分页查询结果
     *
     * @param array $params 包含分页、模型名称、查询条件和排序条件的数组
     *                      - 'pageDto': 包含 'page' 和 'pageSize' 的数组
     *                      - 'modelName': 数据表的名称
     *                      - 'where': 查询条件，可以是数组、闭包函数或其他类型
     *                      - 'orderBy': 排序条件，格式为 ['column' => 'direction']，例如 ['name' => 'asc']
     * @return array 包含分页结果的数组
     */
    static public function builder(array $params)
    {

        $model = $params['model'] ?? null;
        $tableName = $params['tableName'] ?? '';
        $where = $params['where'] ?? null;
        $orderBy = $params['orderBy'] ?? null;
        $select = $params['select'] ?? null;
        $cover = $params['cover'] ?? false;
        $format = $params['format'] ?? null;
        $with = $params['with'] ?? null;
        $pageDto = $params['pageDto'] ?? static::DEFALUT_PAGE_DTO;

        ['page' => $page, 'pageSize' => $pageSize, 'where' => $inputWhere, 'orderBy' => $inputOrderBy] = array_merge(static::DEFALUT_PAGE_DTO, $pageDto);
        if (!$tableName && !$model) throw new NotFoundException('找不到该表', Http::NOT_FOUND->value);

        $query = $model ? (new $model)->query() : Db::table($tableName);


        // 用户输入
        if ($inputOrderBy && !$cover) static::orderByHandler($query, $inputOrderBy);
        if ($inputWhere && is_array($inputWhere) && !$cover) static::formatInutWhere($query, $inputWhere);

        // 传入
        if ($with) $query->with($with);
        if ($select && is_array($select)) $query->select($select);
        if ($where) static::whereHandler($query, $where);
        if ($orderBy && is_array($orderBy)) static::orderByHandler($query, $orderBy);


        // 缺省判断
        if (Schema::hasColumn($tableName, 'status') && !$cover) $query->where('status', true);
        if (Schema::hasColumn($tableName, 'deleted_at') && !$cover) $query->where('deleted_at', null);
        if (Schema::hasColumn($tableName, 'created_at') && !$cover) $query->orderBy('created_at', 'DESC');

        $res = $query->paginate($pageSize, ['*'], 'page', $page);

        return [
            'items' => is_callable($format) ? $format($res->items()) : $res->items(),
            'totalCount' => $res->total(),
            'hasNextPage' => $res->hasMorePages()
        ];
    }

    static private function orderByHandler($query, array $orderBy)
    {
        foreach ($orderBy as $column => $direction) {
            $snakeKey = Str::snake($column);
            $query->orderBy($snakeKey, $direction);
        }
    }

    static private function formatInutWhere($query, $inputWhere)
    {
        foreach ($inputWhere as $key => $value) {
            $snakeKey = Str::snake($key);
            is_array($value) ? (count($value) === 1 ? $query->where($snakeKey, 'LIKE', "%{$value[0]}%") : $query->whereIn($snakeKey, $value)) : $query->where($snakeKey, $value);
        }
    }

    static private function whereHandler($query, $where)
    {
        if (is_callable($where)) $where($query);
        else if ($where) $query->where($where);
    }
}
