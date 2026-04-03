<?php
/**
 * HuBBS - 自动更新系统
 * 基于 Gitee Release 检测版本，支持本地上传更新
 */

class Updater {
    // Gitee 仓库信息
    private $repoOwner = 'youruihu';
    private $repoName = 'hubbs';
    private $apiBase = 'https://gitee.com/api/v5/repos';
    
    // 更新相关目录
    private $updateDir;
    private $backupDir;
    
    // 最后HTTP错误
    private $lastHttpError = null;
    
    // 排除列表（更新时不覆盖）
    private $excludePaths = [
        // 用户数据目录
        'data/',
        'uploads/',
        
        // 安装相关
        'install/',
        'install.lock',
        
        // 版本控制
        '.git/',
        '.gitignore',
        '.gitattributes',
        
        // 服务器环境文件
        '.user.ini',
        '.htaccess',
        '.well-known/',
        
        // 运行时目录
        'cache/',
        'logs/',
        'tmp/',
        
        // IDE和编辑器配置
        '.idea/',
        '.vscode/',
        '.DS_Store',
        '.editorconfig',
        
        // Composer依赖
        'vendor/',
        'composer.json',
        'composer.lock',
        
        // Node.js依赖
        'node_modules/',
        'package.json',
        'package-lock.json',
        'yarn.lock',
        
        // 测试相关
        'tests/',
        '.phpunit.cache/',
        'phpunit.xml',
        'phpunit.xml.dist',
        
        // 调试和测试文件
        'debug_header.php',
        'debug_url.php',
        'test.php',
        'test_download.php',
        
        // 环境配置
        '.env',
        '.env.example',
        '.env.local',
        
        // 其他
        'LICENSE',
        'CONTRIBUTING.md',
        'CHANGELOG.md',
    ];
    
    public function __construct() {
        $this->updateDir = HUBBS_ROOT . 'data/updates/';
        $this->backupDir = HUBBS_ROOT . 'data/backups/';
        
        // 确保目录存在
        if (!is_dir($this->updateDir)) {
            mkdir($this->updateDir, 0755, true);
        }
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * 检查是否有新版本
     * @return array ['has_update' => bool, 'local_version' => string, 'remote_version' => string, 'release_info' => array]
     */
    public function checkUpdate() {
        $localVersion = HUBBS_VERSION;
        $releaseInfo = $this->getLatestRelease();

        if (!$releaseInfo) {
            $httpError = $this->getLastHttpError();
            $errorMsg = '无法获取远程版本信息';
            if ($httpError) {
                $errorMsg .= '：' . $httpError;
            } else {
                $errorMsg .= '，请检查网络连接或服务器配置';
            }
            
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => $errorMsg
            ];
        }

        // 检查必要的字段
        if (empty($releaseInfo['tag_name'])) {
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => '远程版本信息格式错误'
            ];
        }

        $remoteVersion = $this->extractVersionFromTag($releaseInfo['tag_name']);
        $hasUpdate = version_compare($remoteVersion, $localVersion, '>');

        return [
            'has_update' => $hasUpdate,
            'local_version' => $localVersion,
            'remote_version' => $remoteVersion,
            'release_info' => $releaseInfo,
            'error' => null
        ];
    }
    
    /**
     * 获取最新 Release 信息
     * @return array|null
     */
    private function getLatestRelease() {
        $url = "{$this->apiBase}/{$this->repoOwner}/{$this->repoName}/releases/latest";
        
        $response = $this->httpGet($url);
        if (!$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * 从 tag 名称提取版本号
     * @param string $tagName 如 "v1.3.0" 或 "1.3.0"
     * @return string
     */
    private function extractVersionFromTag($tagName) {
        return ltrim($tagName, 'vV');
    }
    
    /**
     * 获取 Gitee Release 下载页面链接
     * @param string $version 版本号
     * @return string
     */
    public function getReleasePageUrl($version) {
        $tagName = (strpos($version, 'v') === 0) ? $version : 'v' . $version;
        return "https://gitee.com/{$this->repoOwner}/{$this->repoName}/releases/{$tagName}";
    }
    
    /**
     * 创建备份
     * @return array ['success' => bool, 'backup_dir' => string, 'error' => string]
     */
    public function createBackup() {
        $backupName = 'backup-' . HUBBS_VERSION . '-' . date('YmdHis');
        $backupPath = $this->backupDir . $backupName . '/';
        
        if (!mkdir($backupPath, 0755, true)) {
            return ['success' => false, 'error' => '创建备份目录失败'];
        }
        
        // 复制核心文件到备份
        $dirsToBackup = ['core/', 'modules/', 'templates/', 'static/'];
        foreach ($dirsToBackup as $dir) {
            $source = HUBBS_ROOT . $dir;
            $dest = $backupPath . $dir;
            if (is_dir($source)) {
                $this->copyDirectory($source, $dest);
            }
        }
        
        // 备份根目录下的 PHP 文件
        $files = glob(HUBBS_ROOT . '*.php');
        foreach ($files as $file) {
            copy($file, $backupPath . basename($file));
        }
        
        return ['success' => true, 'backup_dir' => $backupPath];
    }
    
    /**
     * 执行更新（从本地上传的ZIP文件）
     * @param string $zipFile 更新包路径
     * @return array ['success' => bool, 'message' => string]
     */
    public function applyUpdate($zipFile) {
        // 检查文件
        if (!file_exists($zipFile)) {
            return ['success' => false, 'message' => '更新包不存在'];
        }
        
        // 创建临时解压目录
        $extractDir = $this->updateDir . 'extract-' . time() . '/';
        if (!mkdir($extractDir, 0755, true)) {
            return ['success' => false, 'message' => '创建解压目录失败'];
        }
        
        // 解压文件
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            return ['success' => false, 'message' => '无法打开更新包'];
        }
        
        $zip->extractTo($extractDir);
        $zip->close();
        
        // 查找解压后的目录（Gitee 下载的 zip 通常包含一个子目录）
        $extractedDirs = glob($extractDir . '*', GLOB_ONLYDIR);
        if (empty($extractedDirs)) {
            $this->removeDirectory($extractDir);
            return ['success' => false, 'message' => '更新包内容为空'];
        }
        
        $sourceDir = $extractedDirs[0] . '/';
        
        // 复制文件（排除保护目录）
        $result = $this->copyUpdateFiles($sourceDir, HUBBS_ROOT);
        
        // 清理临时文件
        $this->removeDirectory($extractDir);
        
        if (!$result['success']) {
            return $result;
        }
        
        // 执行数据库迁移
        try {
            Migrate::run();
        } catch (Exception $e) {
            return ['success' => false, 'message' => '数据库迁移失败: ' . $e->getMessage()];
        }
        
        return ['success' => true, 'message' => '更新成功'];
    }
    
    /**
     * 复制更新文件（排除保护目录）
     * @param string $source 源目录
     * @param string $dest 目标目录
     * @return array
     */
    private function copyUpdateFiles($source, $dest) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = str_replace($source, '', $item->getPathname());
            $relativePath = ltrim($relativePath, '/\\');
            
            // 检查是否在排除列表中
            if ($this->isExcluded($relativePath)) {
                continue;
            }
            
            $targetPath = $dest . $relativePath;
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                // 确保目录存在
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                if (!copy($item->getPathname(), $targetPath)) {
                    return ['success' => false, 'message' => '复制文件失败: ' . $relativePath];
                }
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * 检查路径是否在排除列表中
     * @param string $path
     * @return bool
     */
    private function isExcluded($path) {
        foreach ($this->excludePaths as $exclude) {
            if (strpos($path, $exclude) === 0 || $path === trim($exclude, '/')) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 获取备份列表
     * @return array
     */
    public function getBackups() {
        $backups = [];
        $dirs = glob($this->backupDir . '*', GLOB_ONLYDIR);
        
        foreach ($dirs as $dir) {
            $backups[] = [
                'name' => basename($dir),
                'path' => $dir,
                'time' => filemtime($dir),
                'size' => $this->getDirectorySize($dir)
            ];
        }
        
        // 按时间倒序
        usort($backups, function($a, $b) {
            return $b['time'] - $a['time'];
        });
        
        return $backups;
    }
    
    /**
     * 回滚到指定备份
     * @param string $backupName
     * @return array
     */
    public function rollback($backupName) {
        $backupPath = $this->backupDir . $backupName . '/';
        
        if (!is_dir($backupPath)) {
            return ['success' => false, 'message' => '备份不存在'];
        }
        
        // 恢复文件
        $result = $this->copyUpdateFiles($backupPath, HUBBS_ROOT);
        
        if (!$result['success']) {
            return $result;
        }
        
        return ['success' => true, 'message' => '回滚成功'];
    }
    
    /**
     * 删除指定备份
     * @param string $backupName
     * @return array
     */
    public function deleteBackup($backupName) {
        $backupPath = $this->backupDir . $backupName . '/';
        
        if (!is_dir($backupPath)) {
            return ['success' => false, 'message' => '备份不存在'];
        }
        
        // 删除备份目录
        $this->removeDirectory($backupPath);
        
        return ['success' => true, 'message' => '备份已删除'];
    }
    
    /**
     * HTTP GET 请求
     * @param string $url
     * @return string|false
     */
    private function httpGet($url) {
        // 记录最后错误
        $lastError = null;
        
        // 方法1: 尝试使用 cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'HuBBS-Updater/' . HUBBS_VERSION);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && $response !== false) {
                return $response;
            }
            
            $lastError = "cURL请求失败: HTTP={$httpCode}, Error={$curlError}";
            error_log("[HuBBS Updater] {$lastError}, URL={$url}");
        } else {
            $lastError = "cURL扩展未安装";
        }
        
        // 方法2: 尝试使用 file_get_contents
        if (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'HuBBS-Updater/' . HUBBS_VERSION,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response !== false) {
                return $response;
            }
            
            $lastError = "file_get_contents请求失败";
            $error = error_get_last();
            if ($error) {
                $lastError .= ": " . ($error['message'] ?? '未知错误');
            }
            error_log("[HuBBS Updater] {$lastError}, URL={$url}");
        } else {
            $lastError = "allow_url_fopen已禁用";
        }
        
        // 保存最后错误到类属性
        $this->lastHttpError = $lastError;
        
        return false;
    }
    
    /**
     * 获取最后的HTTP错误信息
     * @return string|null
     */
    public function getLastHttpError() {
        return $this->lastHttpError ?? null;
    }
    
    /**
     * 复制目录
     * @param string $source
     * @param string $dest
     */
    private function copyDirectory($source, $dest) {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $targetPath = $dest . str_replace($source, '', $item->getPathname());
            
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }
    
    /**
     * 删除目录
     * @param string $dir
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * 获取目录大小
     * @param string $dir
     * @return int
     */
    private function getDirectorySize($dir) {
        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }
    
    /**
     * 清理旧备份（保留最近5个）
     */
    public function cleanupOldBackups() {
        $backups = $this->getBackups();
        
        // 保留最近5个
        if (count($backups) > 5) {
            $toDelete = array_slice($backups, 5);
            foreach ($toDelete as $backup) {
                $this->removeDirectory($backup['path']);
            }
        }
    }
    
    /**
     * 检查系统是否可写
     * @return array ['writable' => bool, 'issues' => array]
     */
    public function checkWritable() {
        $issues = [];
        $dirsToCheck = ['core/', 'modules/', 'templates/', 'static/', 'data/updates/'];
        
        foreach ($dirsToCheck as $dir) {
            $path = HUBBS_ROOT . $dir;
            if (!is_dir($path)) {
                $issues[] = "目录不存在: {$dir}";
            } elseif (!is_writable($path)) {
                $issues[] = "目录不可写: {$dir}";
            }
        }
        
        // 检查 PHP 函数是否可用
        if (!class_exists('ZipArchive')) {
            $issues[] = 'ZipArchive 扩展未安装';
        }
        
        if (!function_exists('curl_init')) {
            $issues[] = 'cURL 扩展未安装';
        }
        
        return [
            'writable' => empty($issues),
            'issues' => $issues
        ];
    }
}
