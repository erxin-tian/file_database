<?php
declare(strict_types=1);

namespace pier\fileDatabase;

use RuntimeException;
use InvalidArgumentException;
use Exception;

/**
 * FileHelper 类 - 简化的文件操作助手
 * 
 * 提供安全、高效的基础文件操作接口，专注于文件读写功能。
 * 所有操作都在指定的基础目录下进行，包含完善的路径安全检查。
 * 
 * @package pier\fileDatabase
 */
class FileHelper
{
    private string $basePath;
    
    /**
     * 构造函数
     * 
     * @param string $basePath 基础目录路径
     * @throws RuntimeException 当目录创建失败时抛出异常
     */
    public function __construct(string $basePath)
    {
        $realPath = realpath($basePath) ?: $basePath;
        if (!is_dir($realPath) && !mkdir($realPath, 0755, true)) {
            throw new RuntimeException("Failed to create directory: {$realPath}");
        }
        $this->basePath = rtrim($realPath, DIRECTORY_SEPARATOR);
    }

    /**
     * 解析并生成绝对路径，确保路径安全，防止目录遍历攻击
     *
     * @param string $relativePath 传入的相对路径
     * @return string 返回经过安全过滤的绝对路径
     * @throws InvalidArgumentException 如果检测到非法路径访问，则抛出异常
     */
    public function resolvePath(string $relativePath): string
    {
        if (empty($relativePath)) {
            throw new InvalidArgumentException("Path cannot be empty");
        }

        // 规范化路径分隔符
        $cleanPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);
        
        // 检查和清理危险的路径元素
        $pathParts = explode(DIRECTORY_SEPARATOR, $cleanPath);
        $safeParts = [];
        
        foreach ($pathParts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new InvalidArgumentException("Directory traversal not allowed: {$relativePath}");
            }
            // 检查非法字符
            if (preg_match('/[<>:"|?*\x00-\x1f]/', $part)) {
                throw new InvalidArgumentException("Illegal characters in path: {$relativePath}");
            }
            $safeParts[] = $part;
        }

        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safeParts);
        
        // 确保最终路径在基础目录内
        $realFullPath = realpath(dirname($fullPath)) ?: dirname($fullPath);
        if (strpos($realFullPath, $this->basePath) !== 0) {
            throw new InvalidArgumentException("Path outside base directory: {$relativePath}");
        }

        return $fullPath;
    }

    /**
     * 写入内容到文件
     * 
     * @param string $relativePath 相对路径
     * @param string $content 要写入的内容
     * @param bool $append 是否追加模式，默认为true
     * @param bool $exclusive 是否使用独占锁，默认为true
     * @return int 写入后的行号（追加模式）或0（覆盖模式）
     * @throws RuntimeException 当文件操作失败时抛出异常
     */
    public function write(string $relativePath, string $content, bool $append = true, bool $exclusive = true): int
    {
        $path = $this->resolvePath($relativePath);
        $this->ensureDirectoryExists(dirname($path));

        $mode = $append ? 'a+' : 'w';
        $handle = fopen($path, $mode);
        
        if ($handle === false) {
            throw new RuntimeException("Failed to open file: {$path}");
        }

        try {
            $lockType = $exclusive ? LOCK_EX : LOCK_SH;
            if (!flock($handle, $lockType)) {
                throw new RuntimeException("Failed to lock file: {$path}");
            }

            $lineNumber = 0;
            if ($append && filesize($path) > 0) {
                $lineNumber = $this->getFileLineCount($handle);
            }

            if (fwrite($handle, $content . ($append ? PHP_EOL : '')) === false) {
                throw new RuntimeException("Failed to write to file: {$path}");
            }

            fflush($handle);
            return $append ? $lineNumber : 0;
            
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * 读取文件内容
     * 
     * @param string $relativePath 相对路径
     * @return string 文件内容
     * @throws RuntimeException 当文件操作失败时抛出异常
     */
    public function read(string $relativePath): string
    {
        $path = $this->resolvePath($relativePath);
        
        if (!file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: {$path}");
        }

        return $content;
    }

    /**
     * 读取文件的所有行
     * 
     * @param string $relativePath 相对路径
     * @return array 文件行数组
     */
    public function readLines(string $relativePath): array
    {
        $content = $this->read($relativePath);
        if (empty($content)) {
            return [];
        }
        
        return array_filter(explode(PHP_EOL, $content), function($line) {
            return $line !== '';
        });
    }

    /**
     * 检查文件是否存在
     * 
     * @param string $relativePath 相对路径
     * @return bool 文件是否存在
     */
    public function exists(string $relativePath): bool
    {
        try {
            $path = $this->resolvePath($relativePath);
            return file_exists($path);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 删除文件
     * 
     * @param string $relativePath 相对路径
     * @return bool 删除是否成功
     */
    public function delete(string $relativePath): bool
    {
        try {
            $path = $this->resolvePath($relativePath);
            if (file_exists($path)) {
                return unlink($path);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取文件大小
     * 
     * @param string $relativePath 相对路径
     * @return int 文件大小（字节）
     */
    public function getFileSize(string $relativePath): int
    {
        try {
            $path = $this->resolvePath($relativePath);
            return file_exists($path) ? filesize($path) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * 获取基础路径
     * 
     * @return string 基础路径
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * 获取文件行数
     * 
     * @param resource $handle 文件句柄
     * @return int 行数
     */
    private function getFileLineCount($handle): int
    {
        if (!is_resource($handle)) {
            return 0;
        }

        // 保存当前位置
        $currentPos = ftell($handle);
        
        // 移动到文件开始
        fseek($handle, 0);
        
        $lineCount = 0;
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line !== false && trim($line) !== '') {
                $lineCount++;
            }
        }
        
        // 恢复原来的位置
        fseek($handle, $currentPos);
        
        return $lineCount;
    }

    /**
     * 确保目录存在
     * 
     * @param string $directory 目录路径
     * @throws RuntimeException 当目录创建失败时抛出异常
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException("Failed to create directory: {$directory}");
            }
        }
    }
}