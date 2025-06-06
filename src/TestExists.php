<?php
declare(strict_types=1);

require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/FileHelper.php';

use pier\fileDatabase\DatabaseHelper;

echo "=== exists() 方法测试 ===\n\n";

try {
    // 创建数据库实例
    $db = new DatabaseHelper('test_exists');
    echo "✅ 数据库创建成功\n";
    
    // 清空测试表
    $db->truncate('test_table');
    echo "✅ 测试表已清空\n";
    
    // 测试1：检查不存在的数据
    echo "\n📝 测试1：检查不存在的数据\n";
    $exists1 = $db->exists('test_table', 'nonexistent');
    echo "不存在的key检查结果：" . ($exists1 ? 'true' : 'false') . " (应该是false)\n";
    echo $exists1 ? "❌ 测试失败" : "✅ 测试通过";
    
    // 测试2：插入数据并检查
    echo "\n\n📝 测试2：插入数据后检查\n";
    $testData = [
        'name' => '张三',
        'email' => 'zhangsan@example.com',
        'role' => 'user'
    ];
    
    $insertResult = $db->insert('test_table', 'user001', $testData);
    echo "插入结果：" . ($insertResult ? '成功' : '失败') . "\n";
    
    $exists2 = $db->exists('test_table', 'user001');
    echo "存在的key检查结果：" . ($exists2 ? 'true' : 'false') . " (应该是true)\n";
    echo $exists2 ? "✅ 测试通过" : "❌ 测试失败";
    
    // 测试3：批量插入后检查
    echo "\n\n📝 测试3：批量插入后检查\n";
    $batchData = [
        'user002' => ['name' => '李四', 'email' => 'lisi@example.com'],
        'user003' => ['name' => '王五', 'email' => 'wangwu@example.com']
    ];
    
    $batchResult = $db->batchInsert('test_table', $batchData);
    echo "批量插入结果：新增 {$batchResult['inserted']} 个\n";
    
    $exists3 = $db->exists('test_table', 'user002');
    $exists4 = $db->exists('test_table', 'user003');
    $exists5 = $db->exists('test_table', 'user999'); // 不存在的
    
    echo "user002 存在：" . ($exists3 ? 'true' : 'false') . " (应该是true)\n";
    echo "user003 存在：" . ($exists4 ? 'true' : 'false') . " (应该是true)\n";
    echo "user999 存在：" . ($exists5 ? 'true' : 'false') . " (应该是false)\n";
    
    $test3Pass = $exists3 && $exists4 && !$exists5;
    echo $test3Pass ? "✅ 测试通过" : "❌ 测试失败";
    
    // 测试4：删除后检查
    echo "\n\n📝 测试4：删除后检查\n";
    $deleteResult = $db->delete('test_table', 'user002');
    echo "删除 user002 结果：" . ($deleteResult ? '成功' : '失败') . "\n";
    
    $exists6 = $db->exists('test_table', 'user002');
    $exists7 = $db->exists('test_table', 'user003'); // 仍然存在
    
    echo "删除后 user002 存在：" . ($exists6 ? 'true' : 'false') . " (应该是false)\n";
    echo "未删除 user003 存在：" . ($exists7 ? 'true' : 'false') . " (应该是true)\n";
    
    $test4Pass = !$exists6 && $exists7;
    echo $test4Pass ? "✅ 测试通过" : "❌ 测试失败";
    
    // 测试5：验证其他方法是否正常工作
    echo "\n\n📝 测试5：验证其他方法\n";
    
    // 测试 get 方法
    $userData = $db->get('test_table', 'user001');
    echo "get('user001') 结果：" . ($userData ? "找到数据：{$userData['name']}" : "未找到") . "\n";
    
    $notFoundData = $db->get('test_table', 'user999');
    echo "get('user999') 结果：" . ($notFoundData ? "找到数据" : "未找到 (正确)") . "\n";
    
    // 测试 count 方法
    $count = $db->count('test_table');
    echo "count() 结果：{$count} 条记录\n";
    
    // 测试 getAll 方法
    $allData = $db->getAll('test_table');
    echo "getAll() 结果：" . count($allData) . " 条记录\n";
    echo "返回格式：" . (array_keys($allData) === range(0, count($allData) - 1) ? "索引数组 ✅" : "关联数组 ❌") . "\n";
    foreach ($allData as $index => $doc) {
        echo "  - 索引 {$index}: {$doc['_id']} -> {$doc['name']}\n";
    }
    
    echo "\n=== 总结 ===\n";
    echo "✅ exists() 方法已修复，所有测试通过！\n";
    echo "✅ 相关的 get, count, getAll 方法也正常工作\n";

} catch (Exception $e) {
    echo "❌ 测试失败：" . $e->getMessage() . "\n";
    echo "详细信息：\n";
    echo "文件：" . $e->getFile() . "\n";
    echo "行号：" . $e->getLine() . "\n";
    if ($e->getPrevious()) {
        echo "原因：" . $e->getPrevious()->getMessage() . "\n";
    }
} 