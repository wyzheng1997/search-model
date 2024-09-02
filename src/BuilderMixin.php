<?php

namespace Ugly\SearchModel;

use Illuminate\Database\Eloquent\Builder;

class BuilderMixin
{
    public function search(): \Closure
    {
        /**
         * @param  array  $options  查询配置
         *                          1、['name' => 'like'] PS: name like '%xxx%'
         *                          2、['category_id' => ['=', 'cate_id']]   PS: category_id = request('cate_id')
         *                          3、['type' => fn($query, $input) => $query->where('type', $input)] PS: 自定义查询
         *                          5、['admin.name' => 'like'] PS: whereHas('admin', fn($q) => $->where('name', 'like', '%xxx%')) PS: 关联查询
         * @param  array  $relations  关联查询
         */
        return function (array $options = [], array $relations = []) {
            /** @var Builder $builder */
            $builder = $this;
            if (blank($options)) {
                return $builder;
            }
            if (! blank($relations)) {
                $builder->with($relations);
            }

            return $builder->where(function ($query) use ($options) {
                // 获取请求参数
                foreach ($options as $field => $operator) {
                    $fieldInfo = pathinfo($field);
                    $isWhereHas = isset($fieldInfo['extension']);

                    // whereHas 默认key PS: 'admin.name' 转换成 'admin_name'
                    if ($isWhereHas) {
                        $field = str_replace('.', '_', $field);
                    }

                    // 自定义 key 优先级最高
                    $field = is_array($operator) ? $operator[1] : $field;

                    // 获取请求参数
                    $input = request($field);

                    // 跳过空值
                    if (blank($input)) {
                        continue;
                    }

                    // 自定义查询
                    if ($operator instanceof \Closure) {
                        $query->where(fn ($q) => call_user_func($operator, $q, $input));

                        continue; // 进入下一个查询参数
                    }

                    // 操作符号
                    $operator = is_array($operator) ? $operator[0] : $operator; // =, >, >=, <,<=, like, in, between
                    switch ($operator) {
                        case 'in':
                        case 'between':
                            $method = 'where'.ucfirst($operator);
                            $args = [is_array($input) ? $input : explode(',', $input)];
                            break;
                        case 'like':
                            $method = 'where';
                            $args = ['like', '%'.$input.'%'];
                            break;
                        default:
                            if (Builder::hasGlobalMacro($operator)) { // 优先调用全局宏
                                $method = $operator;
                                $args = [$input];
                            } else {
                                $method = 'where';
                                $args = [$operator, $input];
                            }
                            break;
                    }

                    if ($isWhereHas) {
                        $query->whereHas($fieldInfo['filename'], fn ($q) => $q->$method($fieldInfo['extension'], ...$args));
                    } else {
                        $query->$method($fieldInfo['filename'], ...$args);
                    }
                }
            });
        };
    }

    public function sort(): \Closure
    {
        /**
         * 排序.
         *
         * @param  array  $options  排序配置, 支持闭包
         *                          [
         *                          'name',
         *                          'price' => fn($query, $direction) => $query->orderBy('total_price', $direction)
         *                          ]
         */
        return function (array $options = []) {
            /** @var Builder $builder */
            $builder = $this;
            $sort_by = (string) request('sort_by');
            if (! blank($sort_by)) {
                //请求参数格式：sort_by=asc(last_modified),desc(email)
                preg_match_all('/(asc|desc)\((.*?)\)/', $sort_by, $matches);
                foreach ($matches[0] as $index => $match) {
                    $direction = $matches[1][$index];
                    $field = $matches[2][$index];
                    if (in_array($field, $options)) {
                        $builder->orderBy($field, $direction);
                    } elseif (data_get($options, $field) instanceof \Closure) {
                        call_user_func($options[$field], $builder, $direction);
                    }
                }
            }

            return $builder;
        };
    }

    public function whereBetweenDate(): \Closure
    {
        return function ($column, $input) {
            /** @var Builder $builder */
            $builder = $this;
            [$startDate,$endDate] = is_array($input) ? $input : explode(',', $input);

            return $builder->whereDate($column, '>=', $startDate)->whereDate($column, '<=', $endDate);
        };
    }
}
