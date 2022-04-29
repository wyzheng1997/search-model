search-model使laravel ORM拥有快速处理请求搜索的能力。
---
## 安装
```bash
composer require wyzheng/search-model:dev-main
```

## 使用
目前支持的搜索类型有：`like`, `=`, `>`, `<`, `>=`, `<`, `!=`。
### 在模型加入SearchModel
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wyz\SearchModel\SearchModel;

class Article extends Model
{
    use HasFactory, SearchModel;
    
    public function author()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 常规使用
```php
// https://example.com/api/articles?title=test&category_id=5
$articles = Article::search([
    'title' => 'like', // 声明数据库title字段模糊搜索
    'category_id' => '=', // 声明数据库category_id字段精确搜索
])->get();
```

### 自定义请求参数
当数据库字段和请求字段不同时，可以显式声明请求字段
```php
// https://example.com/api/articles?text=test&cate_id=5
$articles = Article::search([
    'title' => ['like', 'text'], 
    'category_id' => ['=', 'cate_id'],
])->get();
```

### 跨表查询
当搜索的字段关联表字段时，可以使用`search_model`方法
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
可以通过自定义查询方法来实现更复杂的查询，目前版本未实现`between`查询，这里就自定义实现一个`between`查询
```php
// https://example.com/api/articles?title=test&time=2020-01-01,2021-01-02
$articles = Article::search([
    'title' => ['like', 'text'], 
    
    // $value = $request->input('time');
    'time' => fn ($query, $value) => $query->whereBetween('created_at', explode(',', $value)),
])->get();
```

### 懒加载
search方法支持传入第二个参数(array)，和原with使用方法一致，可以指定懒加载的字段
```php
$articles = Article::search([
    'title' => ['like', 'text'], 
], ['author' => function($author) {
    $author->select('id', 'name');
}])->get();
```

### 其他
必须使用Model::search()这种方法来使用，search()前不能有其他ORM语句
```php
// 错误示例
$articles = Article::orderBy('created_at')->search([...])->get();

// 正确示例
$articles = Article::search([...])->orderBy('created_at')->get();
```
