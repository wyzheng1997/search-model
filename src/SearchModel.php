<?php

namespace Wyz\SearchModel;

use Illuminate\Database\Eloquent\Builder;

trait SearchModel
{
    /**
     * 扩展laravel的ORM，使其拥有简单的搜索能力.
     */
    public static function search(array $search = [], array $relations = []): Builder
    {
        return self::with($relations)->where(function ($query) use ($search) {
            $ipt = request();
            foreach ($search as $key => $val) {
                $keysInfo = pathinfo($key);
                $isWhereHas = isset($keysInfo['extension']);
                if ($isWhereHas) { // whereHas 默认key PS: 'admin.name' 转换成 'admin_name'
                    $key = str_replace('.', '_', $key);
                }
                $key = is_array($val) ? $val[1] : $key; // 自定义 key

                // 获取输入，空值跳过
                $search_input = $ipt->input($key);
                if (blank($search_input)) {
                    continue;
                }

                // 自定义where查询
                if ($val instanceof \Closure) {
                    call_user_func($val, $query, $search_input);
                    continue; // 进入下一个查询参数
                }

                // 操作符号
                $operator = is_array($val) ? $val[0] : $val; // =, >, >=, <,<=, like
                if ('like' === $val) {
                    $search_input = "%{$search_input}%";
                }

                // 执行查询
                if ($isWhereHas) {
                    $query->whereHas($keysInfo['filename'], function ($q) use ($keysInfo, $operator, $search_input) {
                        $q->where($keysInfo['extension'], $operator, $search_input);
                    });
                } else {
                    $query->where($keysInfo['filename'], $operator, $search_input);
                }
            }
        });
    }
}
