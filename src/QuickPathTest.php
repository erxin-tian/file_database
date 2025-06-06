<?php
declare(strict_types=1);

require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/FileHelper.php';

use pier\fileDatabase\DatabaseHelper;

echo "=== 路径检测快速测试 ===\n\n";

try {
    // 测试1：默认路径
    echo "📝 测试1：默认自动检测\n";
    $db1 = new DatabaseHelper('test_db');


    $data = $db1->getAll("test_table");
    print_r($data);

    

    // echo "✅ 默认路径：" . $db1->getDataPath() . "\n";
    
    // // 测试2：自定义路径
    // echo "\n📝 测试2：自定义路径\n";
    // $customPath = __DIR__ . '/custom_data';
    // $db2 = new DatabaseHelper('test_db', $customPath);
    // echo "✅ 自定义路径：" . $db2->getDataPath() . "\n";
    
    // // 测试3：项目根目录路径
    // echo "\n📝 测试3：项目根目录路径\n";
    // $projectRoot = dirname(__DIR__);
    // $rootDataPath = $projectRoot . '/project_data';
    // $db3 = new DatabaseHelper('test_db', $rootDataPath);
    // echo "✅ 项目根路径：" . $db3->getDataPath() . "\n";
    
    // // 测试4：验证写入
    // echo "\n📝 测试4：写入验证\n";
    // $testData = [
    //     "message" => "Hello from path test",
    //     "timestamp" => date('Y-m-d H:i:s')
    // ];
    
    // $db1->insert('test_table', 'msg001', $testData);
    // $retrieved = $db1->get('test_table', 'msg001');
    
    // if ($retrieved) {
    //     echo "✅ 数据写入和读取成功：{$retrieved['message']}\n";
    // } else {
    //     echo "❌ 数据写入失败\n";
    // }
    
    // echo "\n=== 使用说明 ===\n";
    // echo "composer安装后的推荐用法：\n";
    // echo "```php\n";
    // echo "// 自动检测（推荐）- 会自动将数据存储在项目根目录\n";
    // echo "\$db = new DatabaseHelper('my_app');\n\n";
    // echo "// 手动指定项目数据目录\n";
    // echo "\$db = new DatabaseHelper('my_app', './storage/database');\n\n";
    // echo "// 使用绝对路径\n";
    // echo "\$db = new DatabaseHelper('my_app', '/var/www/project/data');\n";
    // echo "```\n";

} catch (Exception $e) {
    echo "❌ 测试失败：" . $e->getMessage() . "\n";
    echo "详细信息：\n";
    echo "文件：" . $e->getFile() . "\n";
    echo "行号：" . $e->getLine() . "\n";
} 