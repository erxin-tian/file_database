# PHP 文件数据库 (pier/file_database)

一个**高效、简洁**的PHP文件数据库系统，提供基于文件存储的NoSQL数据库功能。采用JSON文档存储格式，支持完整的CRUD操作和智能路径检测。

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-^10.0-red.svg)](https://phpunit.de/)

## 🚀 主要特性

### 核心功能
- **完整的CRUD操作**：支持增删改查所有数据库操作
- **JSON文档存储**：每行一个JSON对象，支持复杂数据结构
- **Key作为ID的API**：简洁的API设计，key作为独立参数
- **智能路径检测**：自动识别composer安装环境，数据存储在项目根目录
- **批量操作**：支持多种格式的批量插入和删除操作
- **严格验证**：键名只允许字母和数字，确保数据安全

### 设计特点
- **无索引文件**：简化设计，减少文件管理复杂度
- **原子操作**：文件锁机制确保数据一致性
- **严格类型检查**：完整的参数验证和错误处理
- **路径安全**：防止目录遍历攻击的安全机制
- **环境适配**：智能适配开发环境和生产环境

### 存储格式
数据以JSON文档格式保存，每行一条记录：
```json
{"friendAddress":"TUN8XY9cVQNfFrVtCRztxYunPiao84vZmi","serverAddress":"","nickname":"TUN8","avatar":"","isBlocked":false,"createdAt":{"$$date":1747494780892},"group":"默认分组","remark":"","_id":"R1UYEJinFfpZeIJP"}
{"friendAddress":"ABC123DEFghijklmnop456","serverAddress":"server1.example.com","nickname":"Alice","avatar":"avatar1.jpg","isBlocked":false,"createdAt":{"$$date":1747494780893},"group":"工作组","remark":"同事","_id":"user001"}
```

**格式说明**：
- 每行一个完整的JSON对象
- 使用`_id`字段作为唯一标识符
- 支持任意复杂的嵌套数据结构
- 自动忽略索引信息行（如`{"$$indexCreated":...}`）

## 📦 安装

### 系统要求
- PHP 8.0 或更高版本
- Composer（推荐）

### 通过 Composer 安装（推荐）
```bash
composer require pier/file_database
```

### 直接使用
```bash
git clone https://github.com/pier/file_database.git
cd file_database
composer install  # 如果需要运行测试
```

## 🎯 快速开始

### 基本用法

```php
<?php
require_once 'vendor/autoload.php';

use pier\fileDatabase\DatabaseHelper;

// 创建数据库实例（智能路径检测）
$db = new DatabaseHelper('my_database');

// 单条数据插入 - Key作为独立参数
$result = $db->insert('users', 'user001', [
    'name' => '张三',
    'email' => 'zhangsan@example.com',
    'role' => 'admin',
    'created_at' => date('Y-m-d H:i:s')
]);

// 查询数据
$user = $db->get('users', 'user001');
echo "用户名：{$user['name']}, 邮箱：{$user['email']}\n";

// 更新数据
$db->update('users', 'user001', [
    'name' => '张三',
    'email' => 'zhangsan@newdomain.com',
    'role' => 'super_admin',
    'updated_at' => date('Y-m-d H:i:s')
]);

// 删除数据
$db->delete('users', 'user001');
```

### 智能路径检测

系统会自动检测安装环境并选择合适的数据存储路径：

```php
<?php
use pier\fileDatabase\DatabaseHelper;

// 方式1：自动检测（推荐）
$db = new DatabaseHelper('my_app');
// Composer安装：数据存储在 /your-project/data/
// 开发环境：数据存储在 /file_database/src/data/

// 方式2：手动指定路径
$db = new DatabaseHelper('my_app', './storage/database');
// 数据存储在：/your-project/storage/database/

// 方式3：绝对路径
$db = new DatabaseHelper('my_app', '/var/www/data');
// 数据存储在：/var/www/data/

// 查看实际使用的路径
echo "数据存储路径：" . $db->getDataPath() . "\n";
```

### 批量操作示例

```php
// 批量插入 - 关联数组格式（推荐）
$users = [
    'user002' => [
        'name' => '李四',
        'email' => 'lisi@example.com',
        'role' => 'user'
    ],
    'user003' => [
        'name' => '王五',
        'email' => 'wangwu@example.com',
        'role' => 'user'
    ]
];

$result = $db->batchInsert('users', $users);
echo "新增：{$result['inserted']} 个，更新：{$result['updated']} 个\n";

// 批量插入 - 包含key字段格式
$moreUsers = [
    [
        'key' => 'user004',
        'name' => '赵六',
        'email' => 'zhaoliu@example.com',
        'role' => 'user'
    ],
    [
        'key' => 'user005',
        'data' => [
            'name' => '孙七',
            'email' => 'sunqi@example.com',
            'role' => 'moderator'
        ]
    ]
];

$result2 = $db->batchInsert('users', $moreUsers);

// 批量删除
$keysToDelete = ['user004', 'user005'];
$deleteResult = $db->batchDelete('users', $keysToDelete);
echo "删除：{$deleteResult['deleted']} 个记录\n";
```

## 📚 完整API文档

### DatabaseHelper 类

#### 构造函数
```php
public function __construct(string $database, ?string $dataPath = null)
```
- `$database`: 数据库名称（只能包含字母、数字、下划线、连字符）
- `$dataPath`: 可选的自定义数据路径，null则智能检测

---

### 📝 增加操作 (Create)

#### 单条数据插入
```php
public function insert(string $table, string $key, $document): bool
```
- `$table`: 表名（文件名，如 `users` 对应 `users.db`）
- `$key`: 键名（只能包含字母和数字a-z, A-Z, 0-9）
- `$document`: 文档数据（数组或JSON字符串）
- **返回**: 操作是否成功

**特性**：
- Key作为独立参数，API更简洁
- 文档不需要包含_id字段
- 自动设置_id字段为提供的key
- 如果key已存在，更新现有记录

#### 批量数据插入
```php
public function batchInsert(string $table, array $dataArray): array
```
- `$table`: 表名
- `$dataArray`: 数据数组，支持多种格式
- **返回**: 操作结果统计

**支持的格式**：
```php
// 格式1：关联数组（推荐）
[
    'key1' => ['name' => '张三', 'age' => 25],
    'key2' => ['name' => '李四', 'age' => 30]
]

// 格式2：包含key字段
[
    ['key' => 'key1', 'name' => '张三', 'age' => 25],
    ['key' => 'key2', 'data' => ['name' => '李四', 'age' => 30]]
]

// 格式3：包含_id字段
[
    ['_id' => 'key1', 'name' => '张三', 'age' => 25],
    ['_id' => 'key2', 'name' => '李四', 'age' => 30]
]
```

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
public function get(string $table, string $key): ?array
```
- `$table`: 表名
- `$key`: 要查询的键名
- **返回**: 文档数组，不存在时返回 `null`

#### 查询所有数据
```php
public function getAll(string $table): array
```
- `$table`: 表名
- **返回**: 所有数据的关联数组 `[key => document, ...]`

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
public function update(string $table, string $key, $document): bool
```
- `$table`: 表名
- `$key`: 要更新的键名
- `$document`: 新的文档数据（数组或JSON字符串）
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

#### 获取数据存储路径
```php
public function getDataPath(): string
```
- **返回**: 数据存储的完整路径

## 💡 完整使用示例

### 朋友管理系统

```php
<?php
require_once 'vendor/autoload.php';

use pier\fileDatabase\DatabaseHelper;

try {
    // 创建数据库实例（自动路径检测）
    $db = new DatabaseHelper('friend_system');
    echo "数据存储路径：" . $db->getDataPath() . "\n";
    
    // === 增加操作 ===
    
    // 插入朋友信息（您的示例格式）
    $friendData = [
        "friendAddress" => "TUN8XY9cVQNfFrVtCRztxYunPiao84vZmi",
        "serverAddress" => "",
        "nickname" => "TUN8",
        "avatar" => "",
        "isBlocked" => false,
        "createdAt" => ["$$date" => 1747494780892],
        "group" => "默认分组",
        "remark" => ""
    ];
    
    $db->insert('friends', 'R1UYEJinFfpZeIJP', $friendData);
    echo "✅ 朋友添加成功\n";
    
    // 批量添加朋友
    $moreFriends = [
        'friend001' => [
            "friendAddress" => "ABC123456789",
            "nickname" => "Alice",
            "group" => "工作组",
            "isBlocked" => false
        ],
        'friend002' => [
            "friendAddress" => "XYZ987654321",
            "nickname" => "Bob", 
            "group" => "朋友组",
            "isBlocked" => true
        ]
    ];
    
    $batchResult = $db->batchInsert('friends', $moreFriends);
    echo "✅ 批量添加：新增 {$batchResult['inserted']} 个朋友\n";
    
    // === 查询操作 ===
    
    // 查询单个朋友
    $friend = $db->get('friends', 'R1UYEJinFfpZeIJP');
    if ($friend) {
        echo "✅ 朋友信息：{$friend['nickname']} ({$friend['group']})\n";
        echo "   地址：{$friend['friendAddress']}\n";
        echo "   状态：" . ($friend['isBlocked'] ? '已屏蔽' : '正常') . "\n";
    }
    
    // 查询所有朋友
    $allFriends = $db->getAll('friends');
    echo "✅ 总共有 " . count($allFriends) . " 个朋友\n";
    
    foreach ($allFriends as $id => $friendInfo) {
        $status = $friendInfo['isBlocked'] ? '已屏蔽' : '正常';
        echo "   - {$id}: {$friendInfo['nickname']} ({$friendInfo['group']}) - {$status}\n";
    }
    
    // === 更新操作 ===
    
    // 更新朋友信息
    $updateData = [
        "friendAddress" => "TUN8XY9cVQNfFrVtCRztxYunPiao84vZmi",
        "serverAddress" => "updated.server.com",
        "nickname" => "TUN8_Updated",
        "avatar" => "new_avatar.jpg",
        "isBlocked" => false,
        "createdAt" => ["$$date" => 1747494780892],
        "group" => "VIP分组",
        "remark" => "已更新信息"
    ];
    
    if ($db->update('friends', 'R1UYEJinFfpZeIJP', $updateData)) {
        echo "✅ 朋友信息更新成功\n";
    }
    
    // === 删除操作 ===
    
    // 删除单个朋友
    if ($db->delete('friends', 'friend002')) {
        echo "✅ 朋友删除成功\n";
    }
    
    // 批量删除
    $keysToDelete = ['friend001'];
    $deleteResult = $db->batchDelete('friends', $keysToDelete);
    echo "✅ 批量删除：删除了 {$deleteResult['deleted']} 个朋友\n";
    
    // === 查看最终结果 ===
    echo "\n📄 最终朋友列表：\n";
    $finalFriends = $db->getAll('friends');
    foreach ($finalFriends as $id => $friend) {
        echo "   {$id}: {$friend['nickname']} ({$friend['group']})\n";
    }

} catch (Exception $e) {
    echo "❌ 操作失败：" . $e->getMessage() . "\n";
}
```

## 🏗️ 项目结构

### Composer 安装后的结构

```
your-project/
├── vendor/
│   └── pier/
│       └── file_database/
├── data/                    ← 数据自动存储在这里
│   ├── friends.db
│   ├── users.db
│   └── configs.db
├── src/
├── public/
├── composer.json
└── index.php
```

### 数据文件示例

**friends.db 内容**：
```json
{"friendAddress":"TUN8XY9cVQNfFrVtCRztxYunPiao84vZmi","serverAddress":"updated.server.com","nickname":"TUN8_Updated","avatar":"new_avatar.jpg","isBlocked":false,"createdAt":{"$$date":1747494780892},"group":"VIP分组","remark":"已更新信息","_id":"R1UYEJinFfpZeIJP"}
{"$$indexCreated":{"fieldName":"friendAddress","unique":true,"sparse":false}}
```

## ⚠️ 重要说明

### 数据格式要求

#### 键名规则
- 只能包含字母和数字：`a-z`、`A-Z`、`0-9`
- 不能包含空格、下划线、连字符或特殊符号
- 有效示例：`user001`、`friend123`、`config1`
- 无效示例：`user_001`、`user-001`、`user 001`

#### 文档内容要求
- 支持任意格式：JSON对象、数组、字符串等
- 推荐使用结构化数据便于后续处理
- 自动忽略索引信息行（`$$indexCreated` 等）

#### 批量操作格式
支持多种批量插入格式，详见API文档中的示例。

### 路径配置

#### 自动检测规则
1. **Composer安装**：检测到vendor目录，数据存储在项目根目录
2. **开发环境**：数据存储在库文件夹下
3. **手动指定**：使用用户提供的自定义路径

#### 环境变量支持
```php
// 通过环境变量配置
$dataPath = $_ENV['DATABASE_PATH'] ?? null;
$db = new DatabaseHelper('my_app', $dataPath);
```

### 错误处理
- 无效的键名会抛出 `InvalidArgumentException`
- 文件操作失败会抛出 `RuntimeException`
- 批量操作中的错误记录会被跳过并计入错误统计

## 🧪 测试

### 运行测试
```bash
# 快速路径测试
php src/QuickPathTest.php

# Key作为ID的API测试
php src/TestKeyAsID.php

# 运行PHPUnit测试套件（如果可用）
composer test
```

## 🔧 技术特性

### 性能优化
1. **JSON文档存储**：每行一个JSON对象，高效的读写操作
2. **文件锁机制**：确保并发访问的数据一致性
3. **智能路径检测**：自动适配不同安装环境
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
| 文件可读性 | ⭐⭐⭐⭐⭐ | ⭐ | ⭐⭐⭐⭐⭐ | ⭐ |
| 并发支持 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐ | ⭐ |
| 内存使用 | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ | ⭐⭐ |
| 环境适配 | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |

**适用场景**：
- ✅ 中小型项目的配置存储
- ✅ 用户数据管理（如朋友列表）
- ✅ 日志和统计数据存储
- ✅ 缓存和会话存储
- ✅ 快速原型开发
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
- 查看 [使用指南](USAGE_COMPOSER.md)
- 运行内置测试验证功能

## 🔄 更新日志

### v3.0.0 (当前版本)
- ✨ **智能路径检测**：自动识别composer安装环境，数据存储在项目根目录
- ✨ **Key作为ID的API**：更简洁的API设计，key作为独立参数
- ✨ **JSON文档存储**：每行一个JSON对象，支持复杂数据结构
- ✅ **多种批量格式**：支持关联数组、key字段、_id字段等多种格式
- ✅ **环境适配**：自动适配开发环境和生产环境
- ✅ **路径安全**：完善的路径安全验证机制

### v2.1.0
- ✨ **完整CRUD支持**：新增查询、更新、删除操作
- ✅ **批量删除功能**：支持批量删除操作
- ✅ **辅助方法**：exists、count、truncate等实用功能
- ✅ **改进的错误处理**：更详细的异常信息
- ✅ **严格键名验证**：只允许字母和数字的键名

### v2.0.0
- ✨ 完全重构：移除索引机制，采用简化的key-value存储
- ✅ 统一行存储格式
- ✅ 优化的单条和批量插入功能
- ✅ 简化的API设计

### v1.0.0
- ✨ 初始版本发布
- ✅ 基础CRUD操作支持
- ✅ 索引机制实现

---

感谢使用 pier/file_database！如果这个项目对您有帮助，请给我们一个 ⭐️

🎯 **完全符合您的需求**：去掉key前缀，JSON文档存储，智能路径检测，简洁API设计！
