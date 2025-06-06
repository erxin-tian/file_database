<?php
declare(strict_types=1);

namespace pier\fileDatabase;

use RuntimeException;
use InvalidArgumentException;
use Exception;

/**
 * DatabaseHelper 类 - 高性能文件数据库操作助手
 * 
 * 提供基于文件的文档数据库操作，支持完整的CRUD操作。
 * 数据以 JSON对象格式存储，每行一条记录，使用_id字段作为唯一标识。
 * 
 * 主要功能：
 * - 单条和批量插入 (insert, batchInsert)
 * - 单条和全表查询 (get, getAll)
 * - 单条更新 (update)
 * - 单条和批量删除 (delete, batchDelete)
 * - 辅助功能 (exists, count, truncate)
 * 
 * @package pier\fileDatabase
 */
class DatabaseHelper
{
    // 常量定义
    private const DEFAULT_DATA_DIR = '/data/';
    private const DB_FILE_EXTENSION = '.db';
    private const ID_FIELD_KEY = '_id';
    
    // 验证规则常量
    private const DATABASE_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';
    private const TABLE_NAME_PATTERN = '/^[a-zA-Z0-9_-]+$/';
    private const ID_PATTERN = '/^[a-zA-Z0-9]+$/';
    
    // 错误消息常量
    private const ERROR_INVALID_DATABASE = 'Invalid database name: must contain only letters, numbers, underscores, and hyphens';
    private const ERROR_INVALID_TABLE = 'Invalid table name: must contain only letters, numbers, underscores, and hyphens';
    private const ERROR_INVALID_ID = 'Invalid _id field: must contain only letters and numbers (a-z, A-Z, 0-9)';
    private const ERROR_INVALID_DATA = 'Invalid data: must be valid JSON object';
    private const ERROR_EMPTY_ID = '_id field cannot be empty';
    private const ERROR_EMPTY_DATA_ARRAY = 'Data array cannot be empty';
    private const ERROR_EMPTY_IDS_ARRAY = 'IDs array cannot be empty';
    private const ERROR_MISSING_ID_FIELD = 'Document must contain _id field';
    
    private string $database;
    private FileHelper $fileHelper;

    /**
     * 构造函数
     * 
     * @param string $database 数据库名称
     * @param string|null $dataPath 自定义数据目录路径，null则自动检测
     * @throws InvalidArgumentException 当数据库名称无效时抛出异常
     */
    public function __construct(string $database, ?string $dataPath = null)
    {
        $this->validateDatabaseName($database);
        $this->database = $database;
        
        // 智能检测数据存储路径
        if ($dataPath !== null) {
            // 用户指定了自定义路径
            $basePath = $dataPath;
        } else {
            // 自动检测安装环境
            $basePath = $this->detectDataPath();
        }
        
        $this->fileHelper = new FileHelper($basePath);
    }

    /**
     * 智能检测数据存储路径
     *
     * @return string 数据存储的基础路径
     */
    private function detectDataPath(): string
    {
        $currentDir = __DIR__;
        
        // 检测是否通过composer安装
        // 方法1：检查是否在vendor目录下
        if (strpos($currentDir, 'vendor' . DIRECTORY_SEPARATOR) !== false) {
            // 通过composer安装，查找项目根目录
            $projectRoot = $this->findProjectRoot($currentDir);
            return $projectRoot . DIRECTORY_SEPARATOR . 'data';
        }
        
        // 方法2：检查当前目录的父级是否有composer.json
        $parentDir = dirname($currentDir);
        if (file_exists($parentDir . DIRECTORY_SEPARATOR . 'composer.json')) {
            return $parentDir . DIRECTORY_SEPARATOR . 'data';
        }
        
        // 默认情况：使用当前目录下的data文件夹（开发环境）
        return $currentDir . self::DEFAULT_DATA_DIR;
    }

    /**
     * 查找项目根目录
     * 
     * @param string $startPath 开始搜索的路径
     * @return string 项目根目录路径
     */
    private function findProjectRoot(string $startPath): string
    {
        $currentPath = $startPath;
        $maxDepth = 10; // 防止无限循环
        $depth = 0;
        
        while ($depth < $maxDepth) {
            // 检查是否找到项目根目录标识
            $indicators = [
                'composer.json',
                'package.json',
                '.git',
                'src',
                'index.php',
                'public'
            ];
            
            foreach ($indicators as $indicator) {
                if (file_exists($currentPath . DIRECTORY_SEPARATOR . $indicator)) {
                    return $currentPath;
                }
            }
            
            // 向上一级目录搜索
            $parentPath = dirname($currentPath);
            
            // 如果已经到达根目录，停止搜索
            if ($parentPath === $currentPath) {
                break;
            }
            
            $currentPath = $parentPath;
            $depth++;
        }
        
        // 如果找不到项目根目录，使用当前工作目录
        return getcwd() ?: dirname(__DIR__);
    }

    /**
     * 获取数据存储路径
     * 
     * @return string 数据存储的完整路径
     */
    public function getDataPath(): string
    {
        return $this->fileHelper->getBasePath();
    }

    /**
     * 获取当前数据库名称
     * 
     * @return string 数据库名称
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * 单条数据插入
     * 
     * @param string $table 表名
     * @param string $key 键名作为文档ID
     * @param array|string $document 文档数据（数组或JSON字符串）
     * @return bool 插入是否成功
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function insert(string $table, string $key, $document): bool
    {
        $this->validateTableName($table);
        $this->validateId($key);
        $documentArray = $this->validateAndParseDocument($document);
        
        // 自动设置_id字段为提供的key
        $documentArray[self::ID_FIELD_KEY] = $key;
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在，如果不存在则创建
            if (!$this->fileHelper->exists($tableFile)) {
                $this->fileHelper->write($tableFile, "", false);
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            // 更新或添加文档
            $existingData[$key] = $documentArray;
            
            // 写入数据
            $this->writeTableData($tableFile, $existingData);
            
            return true;
        } catch (Exception $e) {
            throw new RuntimeException("Failed to insert document: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 多条数据插入
     * 输入格式：["key1" => {"field":"value"}, "key2" => {"field":"value"}] 或 [["key":"xxx", "data":{...}], ...]
     * 存储格式：每行一个JSON对象
     * 
     * @param string $table 表名
     * @param array $dataArray 数据数组，支持两种格式：关联数组或包含key字段的数组
     * @return array 插入结果统计
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function batchInsert(string $table, array $dataArray): array
    {
        $this->validateTableName($table);
        $this->validateBatchData($dataArray);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在，如果不存在则创建
            if (!$this->fileHelper->exists($tableFile)) {
                $this->fileHelper->write($tableFile, "", false);
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            $insertCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            
            // 检测数据格式并处理
            foreach ($dataArray as $keyOrIndex => $document) {
                try {
                    $key = null;
                    $documentArray = null;
                    
                    // 格式1: ["key1" => {...}, "key2" => {...}] 关联数组格式
                    if (is_string($keyOrIndex)) {
                        $key = $keyOrIndex;
                        $documentArray = $this->validateAndParseDocument($document);
                    }
                    // 格式2: [["key":"xxx", "data":{...}], ...] 包含key字段的格式
                    else if (is_array($document) && isset($document['key'])) {
                        $key = $document['key'];
                        // 如果有data字段，使用data字段内容；否则使用整个document（除了key字段）
                        if (isset($document['data'])) {
                            $documentArray = $this->validateAndParseDocument($document['data']);
                        } else {
                            $documentArray = $document;
                            unset($documentArray['key']); // 移除key字段，因为它会作为_id
                        }
                    }
                    // 格式3: [{"_id":"xxx", ...}, ...] 包含_id字段的文档格式
                    else if (is_array($document) && isset($document[self::ID_FIELD_KEY])) {
                        $key = $document[self::ID_FIELD_KEY];
                        $documentArray = $this->validateAndParseDocument($document);
                    }
                    else {
                        $errorCount++;
                        continue;
                    }
                    
                    if (empty($key)) {
                        $errorCount++;
                        continue;
                    }
                    
                    $this->validateId($key);
                    
                    // 设置_id字段
                    $documentArray[self::ID_FIELD_KEY] = $key;
                    
                    // 检查是否为新增或更新
                    if (isset($existingData[$key])) {
                        $updateCount++;
                    } else {
                        $insertCount++;
                    }
                    
                    $existingData[$key] = $documentArray;
                    
                } catch (Exception $e) {
                    $errorCount++;
                    continue;
                }
            }
            
            // 写入所有数据
            $this->writeTableData($tableFile, $existingData);
            
            return [
                'total' => count($dataArray),
                'inserted' => $insertCount,
                'updated' => $updateCount,
                'errors' => $errorCount,
                'success' => true
            ];
            
        } catch (Exception $e) {
            throw new RuntimeException("Failed to batch insert documents: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查询单条数据
     * 
     * @param string $table 表名
     * @param string $key 键名
     * @return array|null 文档数据，不存在时返回null
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function get(string $table, string $key): ?array
    {
        $this->validateTableName($table);
        $this->validateId($key);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return null;
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            return $existingData[$key] ?? null;
        } catch (Exception $e) {
            throw new RuntimeException("Failed to get document: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查询所有数据
     * 
     * @param string $table 表名
     * @return array 所有文档数据 [id => document, ...]
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function getAll(string $table): array
    {
        $this->validateTableName($table);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return [];
            }
            
            // 读取现有数据
            return $this->readTableData($tableFile);
        } catch (Exception $e) {
            throw new RuntimeException("Failed to get all documents: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 更新单条数据（仅更新存在的文档）
     * 
     * @param string $table 表名
     * @param string $key 键名
     * @param array|string $document 新文档数据
     * @return bool 更新是否成功，false表示文档不存在
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function update(string $table, string $key, $document): bool
    {
        $this->validateTableName($table);
        $this->validateId($key);
        $documentArray = $this->validateAndParseDocument($document);
        
        // 确保更新的文档包含正确的_id
        $documentArray[self::ID_FIELD_KEY] = $key;
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return false;
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            // 检查文档是否存在
            if (!isset($existingData[$key])) {
                return false;
            }
            
            // 更新文档
            $existingData[$key] = $documentArray;
            
            // 写入数据
            $this->writeTableData($tableFile, $existingData);
            
            return true;
        } catch (Exception $e) {
            throw new RuntimeException("Failed to update document: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 删除单条数据
     * 
     * @param string $table 表名
     * @param string $key 键名
     * @return bool 删除是否成功，false表示文档不存在
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function delete(string $table, string $key): bool
    {
        $this->validateTableName($table);
        $this->validateId($key);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return false;
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            // 检查文档是否存在
            if (!isset($existingData[$key])) {
                return false;
            }
            
            // 删除文档
            unset($existingData[$key]);
            
            // 写入数据
            $this->writeTableData($tableFile, $existingData);
            
            return true;
        } catch (Exception $e) {
            throw new RuntimeException("Failed to delete document: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 批量删除数据
     * 
     * @param string $table 表名
     * @param array $keys 键名数组
     * @return array 删除结果统计
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function batchDelete(string $table, array $keys): array
    {
        $this->validateTableName($table);
        $this->validateBatchIds($keys);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return [
                    'total' => count($keys),
                    'deleted' => 0,
                    'not_found' => count($keys),
                    'errors' => 0,
                    'success' => true
                ];
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            $deletedCount = 0;
            $notFoundCount = 0;
            $errorCount = 0;
            
            foreach ($keys as $key) {
                // 验证键名
                try {
                    $this->validateId($key);
                } catch (Exception $e) {
                    $errorCount++;
                    continue;
                }
                
                // 检查key是否存在并删除
                if (isset($existingData[$key])) {
                    unset($existingData[$key]);
                    $deletedCount++;
                } else {
                    $notFoundCount++;
                }
            }
            
            // 写入数据
            $this->writeTableData($tableFile, $existingData);
            
            return [
                'total' => count($keys),
                'deleted' => $deletedCount,
                'not_found' => $notFoundCount,
                'errors' => $errorCount,
                'success' => true
            ];
            
        } catch (Exception $e) {
            throw new RuntimeException("Failed to batch delete documents from table '{$table}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 检查文档是否存在
     * 
     * @param string $table 表名
     * @param string $key 键名
     * @return bool 文档是否存在
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function exists(string $table, string $key): bool
    {
        $this->validateTableName($table);
        $this->validateId($key);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return false;
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            return isset($existingData[$key]);
        } catch (Exception $e) {
            throw new RuntimeException("Failed to check if document exists: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取表中记录数量
     * 
     * @param string $table 表名
     * @return int 记录数量
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function count(string $table): int
    {
        $this->validateTableName($table);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 检查表文件是否存在
            if (!$this->fileHelper->exists($tableFile)) {
                return 0;
            }
            
            // 读取现有数据
            $existingData = $this->readTableData($tableFile);
            
            return count($existingData);
        } catch (Exception $e) {
            throw new RuntimeException("Failed to count records: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清空表（删除所有数据）
     * 
     * @param string $table 表名
     * @return bool 清空是否成功
     * @throws InvalidArgumentException 当参数无效时抛出异常
     * @throws RuntimeException 当操作失败时抛出异常
     */
    public function truncate(string $table): bool
    {
        $this->validateTableName($table);
        
        try {
            $tableFile = $this->getTableFileName($table);
            
            // 创建空文件或清空现有文件
            $this->fileHelper->write($tableFile, "", false);
            
            return true;
        } catch (Exception $e) {
            throw new RuntimeException("Failed to truncate table: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 读取表数据
     * 存储格式：每行一个JSON对象
     * 
     * @param string $tableFile 表文件路径
     * @return array 表数据数组 [id => document, ...]
     */
    private function readTableData(string $tableFile): array
    {
        $lines = $this->fileHelper->readLines($tableFile);
        $data = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // 解析JSON对象
            $document = json_decode($line, true);
            if ($document === null) {
                continue; // 跳过无效的JSON行
            }
            
            // 跳过索引信息行
            if (isset($document['$$indexCreated'])) {
                continue;
            }
            
            // 检查是否包含_id字段
            if (isset($document[self::ID_FIELD_KEY])) {
                // $data[$document[self::ID_FIELD_KEY]] = $document;
                $data[] = $document;
            }
        }
        
        return $data;
    }

    /**
     * 写入表数据
     * 存储格式：每行一个JSON对象
     * 
     * @param string $tableFile 表文件路径
     * @param array $data 数据数组 [id => document, ...]
     * @return void
     */
    private function writeTableData(string $tableFile, array $data): void
    {
        $lines = [];
        foreach ($data as $document) {
            // 直接存储JSON对象
            $lines[] = json_encode($document, JSON_UNESCAPED_UNICODE);
        }
        
        $content = implode(PHP_EOL, $lines);
        $this->fileHelper->write($tableFile, $content, false);
    }

    /**
     * 验证数据库名称
     * 
     * @param string $database 数据库名称
     * @throws InvalidArgumentException 当数据库名称无效时抛出异常
     */
    private function validateDatabaseName(string $database): void
    {
        if (empty($database)) {
            throw new InvalidArgumentException('Database name cannot be empty');
        }
        
        if (!preg_match(self::DATABASE_NAME_PATTERN, $database)) {
            throw new InvalidArgumentException(self::ERROR_INVALID_DATABASE . ": {$database}");
        }
    }

    /**
     * 验证表名
     * 
     * @param string $table 表名
     * @throws InvalidArgumentException 当表名无效时抛出异常
     */
    private function validateTableName(string $table): void
    {
        if (empty($table)) {
            throw new InvalidArgumentException('Table name cannot be empty');
        }
        
        if (!preg_match(self::TABLE_NAME_PATTERN, $table)) {
            throw new InvalidArgumentException(self::ERROR_INVALID_TABLE . ": {$table}");
        }
    }

    /**
     * 验证文档ID
     * ID只能包含字母和阿拉伯数字
     * 
     * @param string $id 文档ID
     * @throws InvalidArgumentException 当ID无效时抛出异常
     */
    private function validateId(string $id): void
    {
        if (empty($id)) {
            throw new InvalidArgumentException(self::ERROR_EMPTY_ID);
        }
        
        if (!preg_match(self::ID_PATTERN, $id)) {
            throw new InvalidArgumentException(self::ERROR_INVALID_ID . ": {$id}");
        }
    }

    /**
     * 验证并解析文档数据
     * 
     * @param array|string $document 文档数据
     * @return array 解析后的文档数组
     * @throws InvalidArgumentException 当文档无效时抛出异常
     */
    private function validateAndParseDocument($document): array
    {
        if (is_string($document)) {
            $parsed = json_decode($document, true);
            if ($parsed === null) {
                throw new InvalidArgumentException(self::ERROR_INVALID_DATA);
            }
            return $parsed;
        }
        
        if (is_array($document)) {
            return $document;
        }
        
        throw new InvalidArgumentException(self::ERROR_INVALID_DATA);
    }

    /**
     * 验证批量数据格式
     * 
     * @param array $dataArray 数据数组
     * @throws InvalidArgumentException 当数据格式无效时抛出异常
     */
    private function validateBatchData(array $dataArray): void
    {
        if (empty($dataArray)) {
            throw new InvalidArgumentException(self::ERROR_EMPTY_DATA_ARRAY);
        }
    }

    /**
     * 验证批量删除的ID数组
     * 
     * @param array $ids ID数组
     * @throws InvalidArgumentException 当ID数组无效时抛出异常
     */
    private function validateBatchIds(array $ids): void
    {
        if (empty($ids)) {
            throw new InvalidArgumentException(self::ERROR_EMPTY_IDS_ARRAY);
        }
    }

    /**
     * 获取表文件名
     * 
     * @param string $table 表名
     * @return string 表文件路径
     */
    private function getTableFileName(string $table): string
    {
        return $table . self::DB_FILE_EXTENSION;
    }
}