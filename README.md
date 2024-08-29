扩展laravel ORM，使其拥有快速处理查询请求的能力。
---
## 安装
```bash
composer require wyzheng/search-model
```

## 使用
目前支持的查询类型有：`like`, `=`, `>`, `<`, `>=`, `<`, `!=`， `in`, `between`。

### 常规使用
```php
// https://example.com/api/articles?title=test&category_id=5&created_at=2020-01-01,2021-01-02&user_id=1,2,3
$articles = Article::search([,
    'title' => 'like', // 声明数据库title字段模糊搜索
    'category_id' => '=', // 声明数据库category_id字段精确搜索
    'created_at' => 'between' // 支持get数组参数或开始和结束用逗号隔开的形式
    'user_id' => 'in' // 支持get数组参数或逗号隔开的形式
])->get();
```

### 自定义请求参数
当数据库字段和请求字段不同时，可以显式声明请求字段名
```php
// https://example.com/api/articles?text=test&cate_id=5
$articles = Article::search([
    'title' => ['like', 'text'], 
    'category_id' => ['=', 'cate_id'],
])->get();
```

### 跨表查询
当查询的字段是关联表的字段时，可以使用`.`的方式指定
```php
// https://example.com/api/articles?title=test&author_name=jack
$articles = Article::search([
    'title' => ['like', 'text'], 
    
    // 当没有显式声明请求字段时，会自动拼接author_name
    // 支持无限层级关联 author.company.name, 默认值 $request->input('author_company_name')
    'author.name' => '=', 
])->get();
 
```

### 自定义查询
可以通过自定义查询方法来实现更复杂的查询
```php
// https://example.com/api/articles?title=test&type=1,2
$articles = Article::search([
    'title' => ['like', 'text'], 
    
    // $value = $request->input('type');
    'type' => fn ($query, $value) => $query->whereNotIn('type', explode(',', $value)),
])->get();
```

### 预加载
search方法支持传入第二个参数(array)，和原with使用方法一致，可以指定预加载的字段
```php
$articles = Article::search([
    'title' => ['like', 'text'], 
], ['author' => function($author) {
    $author->select('id', 'name');
}])->get();
```

### 查询排序
本扩展包还简单实现的查询排序功能`sort`
```php
// https://example.com/api/articles?title=test&sort_by=asc(id),desc(author_level)
$articles = Article::search([
    'title' => ['like', 'text'], 
])->sort(['id', 'author_level' => function($query, $direction) {
    // $direction 取值 'asc' 或 'desc'
    $query->orderByRaw('.......')
}])->get();
```
