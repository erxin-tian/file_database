# PHP 文件数据库 (pier/file_database)

一个**高效、简洁**的PHP文件数据库系统，提供基于文件存储的NoSQL数据库功能。采用行存储格式，支持完整的CRUD操作。

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-^10.0-red.svg)](https://phpunit.de/)

## 🚀 主要特性

### 核心功能
- **完整的CRUD操作**：支持增删改查所有数据库操作
- **简洁的key-value存储**：基于文件的NoSQL数据库实现，无需索引文件
- **行存储格式**：每行一条记录，高效的数据存储和读取
- **单表设计**：文件名即表名（如 `users.db`），数据直接存储在 `data` 文件夹下
- **批量操作**：支持高效的批量插入和删除操作
- **严格验证**：键名只允许字母和数字，确保数据安全

### 设计特点
- **无索引文件**：简化设计，减少文件管理复杂度
- **原子操作**：文件锁机制确保数据一致性
- **严格类型检查**：完整的参数验证和错误处理
- **路径安全**：防止目录遍历攻击的安全机制

### 存储格式
数据以行存储格式保存，每行一条记录：
```
user001:{"data":"{\"name\":\"张三\",\"age\":25}"}
user002:{"data":"{\"name\":\"李四\",\"age\":30}"}
config001:{"data":"timeout=30;charset=utf8"}
```

**格式说明**：
- 格式：`键名:{"data":"用户数据"}`
- 支持任意类型的用户数据（JSON、文本、配置字符串等）
- 统一的包装格式，便于扩展和维护

## 📦 安装

### 系统要求
- PHP 8.0 或更高版本
- Composer（可选）

### 直接使用
```bash
git clone https://github.com/pier/file_database.git
cd file_database
composer install  # 如果需要运行测试
```

### 通过 Composer 安装
```bash
composer require pier/file_database
```

## 🎯 快速开始

### 基本用法

```php
<?php
require_once 'vendor/autoload.php';

use pier\fileDatabase\DatabaseHelper;

// 创建数据库实例
$db = new DatabaseHelper('my_database');

// 单条数据插入
$success = $db->insert('users', 'user001', '{"name":"张三","age":25}');

// 单条数据查询
$userData = $db->get('users', 'user001');
echo $userData; // 输出: {"name":"张三","age":25}

// 数据更新
$updated = $db->update('users', 'user001', '{"name":"张三","age":26}');

// 数据删除
$deleted = $db->delete('users', 'user001');
```

### 批量操作示例

```php
// 批量插入
$batchData = [
    ['key' => 'user002', 'data' => '{"name":"李四","age":30}'],
    ['key' => 'user003', 'data' => '{"name":"王五","age":28}'],
    ['key' => 'config001', 'data' => 'timeout=60;encoding=utf8']
];

$result = $db->batchInsert('users', $batchData);
echo "新增: {$result['inserted']}, 更新: {$result['updated']}\n";

// 批量删除
$keysToDelete = ['user002', 'user003'];
$deleteResult = $db->batchDelete('users', $keysToDelete);
echo "删除: {$deleteResult['deleted']} 条记录\n";
```

## 📚 完整API文档

### DatabaseHelper 类

#### 构造函数
```php
public function __construct(string $database)
```
- `$database`: 数据库名称（只能包含字母、数字、下划线、连字符）

---

### 📝 增加操作 (Create)

#### 单条数据插入
```php
public function insert(string $table, string $key, string $data): bool
```
- `$table`: 表名（文件名，如 `users` 对应 `users.db`）
- `$key`: 数据的唯一键（只能包含字母和数字a-z, A-Z, 0-9）
- `$data`: 要存储的数据（字符串格式，不能包含换行符）
- **返回**: 操作是否成功

**特性**：
- 如果key不存在，创建新记录
- 如果key已存在，更新现有记录
- 自动创建表文件（如果不存在）

#### 批量数据插入
```php
public function batchInsert(string $table, array $dataArray): array
```
- `$table`: 表名
- `$dataArray`: 数据数组，格式：`[["key":"xxx", "data":"xxx"], ...]`
- **返回**: 操作结果统计

**返回格式**：
```php
[
    'total' => 4,           // 总处理数量
    'inserted' => 3,        // 新增记录数
    'updated' => 1,         // 更新记录数
    'errors' => 0,          // 错误记录数
    'success' => true       // 整体操作是否成功
]
```

---

### 🔍 查询操作 (Read)

#### 单条数据查询
```php
public function get(string $table, string $key): ?string
```
- `$table`: 表名
- `$key`: 要查询的键名
- **返回**: 数据内容，不存在时返回 `null`

#### 查询所有数据
```php
public function getAll(string $table): array
```
- `$table`: 表名
- **返回**: 所有数据的关联数组 `[key => data, ...]`

#### 检查键是否存在
```php
public function exists(string $table, string $key): bool
```
- `$table`: 表名
- `$key`: 要检查的键名
- **返回**: 键是否存在

#### 获取记录数量
```php
public function count(string $table): int
```
- `$table`: 表名
- **返回**: 表中记录的总数

---

### ✏️ 更新操作 (Update)

#### 单条数据更新
```php
public function update(string $table, string $key, string $data): bool
```
- `$table`: 表名
- `$key`: 要更新的键名
- `$data`: 新的数据内容
- **返回**: 更新是否成功，`false` 表示键不存在

**注意**：只更新已存在的数据，如果键不存在则返回 `false`

---

### 🗑️ 删除操作 (Delete)

#### 单条数据删除
```php
public function delete(string $table, string $key): bool
```
- `$table`: 表名
- `$key`: 要删除的键名
- **返回**: 删除是否成功，`false` 表示键不存在

#### 批量删除
```php
public function batchDelete(string $table, array $keys): array
```
- `$table`: 表名
- `$keys`: 要删除的键名数组
- **返回**: 删除结果统计

**返回格式**：
```php
[
    'total' => 4,           // 总处理数量
    'deleted' => 3,         // 成功删除数
    'not_found' => 1,       // 未找到的数量
    'errors' => 0,          // 错误数量
    'success' => true       // 整体操作是否成功
]
```

#### 清空表
```php
public function truncate(string $table): bool
```
- `$table`: 表名
- **返回**: 清空是否成功

---

### 🔧 辅助方法

#### 获取数据库名称
```php
public function getDatabaseName(): string
```
- **返回**: 当前数据库名称

## 💡 完整使用示例

### CRUD操作示例

```php
<?php
require_once 'vendor/autoload.php';

use pier\fileDatabase\DatabaseHelper;

try {
    // 创建数据库实例
    $db = new DatabaseHelper('blog_system');
    
    // === 增加操作 ===
    
    // 插入用户数据
    $userResult = $db->insert('users', 'admin', json_encode([
        'username' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'administrator',
        'created_at' => date('Y-m-d H:i:s')
    ]));
    
    // 批量插入文章数据
    $articles = [
        ['key' => 'article001', 'data' => '{"title":"PHP教程","content":"学习PHP基础..."}'],
        ['key' => 'article002', 'data' => '{"title":"数据库设计","content":"数据库设计原则..."}'],
        ['key' => 'config001', 'data' => 'timeout=30;max_users=1000'] // 非JSON数据
    ];
    
    $batchResult = $db->batchInsert('articles', $articles);
    echo "批量插入：新增 {$batchResult['inserted']} 条记录\n";
    
    // === 查询操作 ===
    
    // 查询单条数据
    $adminData = $db->get('users', 'admin');
    echo "管理员信息: {$adminData}\n";
    
    // 查询所有文章
    $allArticles = $db->getAll('articles');
    echo "总共有 " . count($allArticles) . " 篇文章\n";
    
    // 检查键是否存在
    if ($db->exists('users', 'admin')) {
        echo "管理员账户存在\n";
    }
    
    // 获取记录数量
    $userCount = $db->count('users');
    echo "用户表中有 {$userCount} 条记录\n";
    
    // === 更新操作 ===
    
    // 更新用户信息
    $updateData = json_encode([
        'username' => 'admin',
        'email' => 'admin@newdomain.com',
        'role' => 'super_admin',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($db->update('users', 'admin', $updateData)) {
        echo "用户信息更新成功\n";
    }
    
    // === 删除操作 ===
    
    // 删除单条数据
    if ($db->delete('articles', 'article002')) {
        echo "文章删除成功\n";
    }
    
    // 批量删除
    $keysToDelete = ['article001', 'config001'];
    $deleteResult = $db->batchDelete('articles', $keysToDelete);
    echo "批量删除：删除了 {$deleteResult['deleted']} 条记录\n";
    
    // 清空表（谨慎使用）
    // $db->truncate('temp_table');
    
} catch (Exception $e) {
    echo "操作失败：" . $e->getMessage() . "\n";
}
```

### 数据存储示例

执行上述代码后，会在 `src/data/` 目录下生成文件：

**users.db 内容**：
```
admin:{"data":"{\"username\":\"admin\",\"email\":\"admin@newdomain.com\",\"role\":\"super_admin\",\"updated_at\":\"2024-01-15 10:30:00\"}"}
```

**articles.db 内容**：
```
(空文件，因为所有记录都被删除了)
```

## ⚠️ 重要说明

### 数据格式要求

#### 键名规则
- 只能包含字母和数字：`a-z`、`A-Z`、`0-9`
- 不能包含空格、下划线、连字符或特殊符号
- 有效示例：`user001`、`article123`、`config1`
- 无效示例：`user_001`、`user-001`、`user 001`

#### 数据内容要求
- 不能包含换行符（`\n` 或 `\r`）
- 支持任意格式：JSON字符串、普通文本、配置字符串等
- 推荐使用JSON格式便于后续处理

#### 批量操作格式
```php
// 正确格式
[
    ["key" => "user001", "data" => "用户数据"],
    ["key" => "user002", "data" => "用户数据"]
]

// 错误格式 - 缺少字段
[
    ["key" => "user001"],           // 缺少data字段
    ["data" => "用户数据"]          // 缺少key字段
]
```

### 错误处理
- 无效的键名会抛出 `InvalidArgumentException`
- 包含换行符的数据会抛出 `InvalidArgumentException`
- 文件操作失败会抛出 `RuntimeException`
- 批量操作中的错误记录会被跳过并计入错误统计

### 文件位置
- 所有数据文件存储在 `src/data/` 目录下
- 文件名格式：`{表名}.db`
- 目录会自动创建（如果不存在）

## 🧪 测试

### 运行基础测试
```bash
php src/Test.php
```

### 运行完整CRUD测试
```bash
php src/TestCRUD.php
```

### 运行PHPUnit测试套件
```bash
composer test
```

## 📁 项目结构

```
php_file_database/
├── src/                        # 源代码目录
│   ├── DatabaseHelper.php      # 数据库操作核心类
│   ├── FileHelper.php          # 文件操作助手类
│   ├── Test.php                # 基础测试示例
│   ├── TestCRUD.php            # CRUD操作测试
│   └── data/                   # 数据文件存储目录
│       ├── users.db            # 用户表数据文件
│       └── articles.db         # 文章表数据文件
├── tests/                      # 完整测试套件
├── vendor/                     # Composer依赖
├── composer.json               # Composer配置
├── phpunit.xml                 # PHPUnit配置
└── README.md                   # 项目说明文档
```

## 🔧 技术特性

### 性能优化
1. **行存储格式**：每行一条记录，高效的读写操作
2. **文件锁机制**：确保并发访问的数据一致性
3. **原地更新**：相同key的数据直接更新，无需重复检查
4. **批量优化**：批量操作一次性读取和写入，提升性能
5. **内存友好**：逐行处理，避免大文件内存溢出

### 安全特性
- **路径验证**：严格的路径安全检查，防止目录遍历攻击
- **参数验证**：所有输入参数的完整验证和清理
- **键名限制**：严格的键名格式要求，防止注入攻击
- **异常处理**：详细的错误信息和异常处理机制

### 数据一致性
- **文件锁定**：写操作使用排他锁，读操作使用共享锁
- **原子操作**：所有写操作都是原子性的
- **错误恢复**：操作失败时确保数据文件完整性

## 🆚 对比其他方案

| 特性 | pier/file_database | SQLite | JSON文件 | 数组序列化 |
|------|-------------------|--------|----------|-----------|
| 安装复杂度 | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| 查询性能 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐ |
| 数据安全 | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐ |
| 文件可读性 | ⭐⭐⭐⭐ | ⭐ | ⭐⭐⭐⭐⭐ | ⭐ |
| 并发支持 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐ | ⭐ |
| 内存使用 | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ | ⭐⭐ |

**适用场景**：
- ✅ 中小型项目的配置存储
- ✅ 简单的用户数据管理
- ✅ 日志和统计数据存储
- ✅ 临时数据缓存
- ❌ 大规模数据处理
- ❌ 复杂查询需求
- ❌ 高并发读写场景

## 🤝 贡献

欢迎贡献代码！请遵循以下步骤：

1. Fork 本项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

### 开发规范
- 遵循 PSR-4 自动加载规范
- 使用严格类型声明 `declare(strict_types=1)`
- 编写完整的PHPDoc注释
- 添加相应的单元测试

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情

## 📞 支持

如果您遇到问题或有建议，请：
- 创建 [Issue](https://github.com/pier/file_database/issues)
- 查看现有的 [文档和示例](https://github.com/pier/file_database/blob/main/README.md)
- 运行内置测试验证功能

## 🔄 更新日志

### v2.1.0 (当前版本)
- ✨ **完整CRUD支持**：新增查询、更新、删除操作
- ✅ **批量删除功能**：支持批量删除操作
- ✅ **辅助方法**：exists、count、truncate等实用功能
- ✅ **改进的错误处理**：更详细的异常信息
- ✅ **完善的文档**：详细的API文档和使用示例
- ✅ **严格键名验证**：只允许字母和数字的键名

### v2.0.0
- ✨ 完全重构：移除索引机制，采用简化的key-value存储
- ✅ 统一行存储格式：`key:{"data":"用户数据"}`
- ✅ 优化的单条和批量插入功能
- ✅ 简化的API设计
- ✅ 增强的错误处理和数据验证

### v1.0.0
- ✨ 初始版本发布
- ✅ 基础CRUD操作支持
- ✅ 索引机制实现
- ✅ 基础测试套件

---

感谢使用 pier/file_database！如果这个项目对您有帮助，请给我们一个 ⭐️
