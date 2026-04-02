<?php
/**
 * HuBBS - 自动更新系统
 * 基于 Gitee 仓库实现自动检测和更新
 */

class Updater {
    // Gitee 仓库信息
    private $repoOwner = 'youruihu';
    private $repoName = 'hubbs';
    private $apiBase = 'https://gitee.com/api/v5/repos';
    
    // 更新相关目录
    private $updateDir;
    private $backupDir;
    
    // 排除列表（更新时不覆盖）
    private $excludePaths = [
        'data/',
        'uploads/',
        'install.lock',
        '.git/',
        '.gitignore',
        'data/config.php',
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
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => '无法获取远程版本信息，请检查网络连接或 Gitee 仓库设置'
            ];
        }

        // 检查必要的字段
        if (empty($releaseInfo['tag_name'])) {
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => '远程版本信息格式错误：缺少 tag_name'
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
     * 下载更新包
     * @param string $version 版本号
     * @return array ['success' => bool, 'file' => string, 'error' => string]
     */
    public function downloadUpdate($version) {
        // 确保版本号带有 v 前缀
        $tagName = (strpos($version, 'v') === 0) ? $version : 'v' . $version;
        
        // 从 Release API 获取下载链接
        $releaseInfo = $this->getReleaseByTag($tagName);
        if (!$releaseInfo || empty($releaseInfo['zipball_url'])) {
            // 如果API获取失败，尝试使用Gitee的归档下载链接
            $downloadUrl = "https://gitee.com/{$this->repoOwner}/{$this->repoName}/repository/archive/{$tagName}";
        } else {
            $downloadUrl = $releaseInfo['zipball_url'];
        }
        
        $fileName = "hubbs-{$version}.zip";
        $filePath = $this->updateDir . $fileName;
        
        // 删除旧文件
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 下载文件
        $content = $this->httpGet($downloadUrl);
        if (!$content) {
            return ['success' => false, 'error' => '下载更新包失败，请检查网络连接或版本号是否正确'];
        }
        
        // 验证下载的是否是有效的 zip 文件（检查文件头）
        if (strlen($content) < 4 || substr($content, 0, 4) !== "PK\x03\x04") {
            // 记录实际内容用于调试
            $contentPreview = substr($content, 0, 100);
            error_log("[HuBBS Updater] 下载内容预览: " . $contentPreview);
            return ['success' => false, 'error' => '下载的文件不是有效的 ZIP 格式，可能是版本号错误或仓库不存在'];
        }
        
        // 保存文件
        if (file_put_contents($filePath, $content) === false) {
            return ['success' => false, 'error' => '保存更新包失败，请检查目录权限'];
        }
        
        return ['success' => true, 'file' => $filePath];
    }
    
    /**
     * 根据标签获取 Release 信息
     * @param string $tagName
     * @return array|null
     */
    private function getReleaseByTag($tagName) {
        $url = "{$this->apiBase}/{$this->repoOwner}/{$this->repoName}/releases/tags/{$tagName}";
        
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
     * 执行更新
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
     * HTTP GET 请求
     * @param string $url
     * @return string|false
     */
    private function httpGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        // 设置请求头，模拟浏览器
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Referer: https://gitee.com/'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            // 记录错误日志
            error_log("[HuBBS Updater] HTTP请求失败: URL={$url}, HTTPCode={$httpCode}, Error={$curlError}");
            
            // 如果返回了内容但HTTP码不是200，也记录下来
            if ($response !== false && !empty($response)) {
                $responsePreview = substr($response, 0, 200);
                error_log("[HuBBS Updater] 响应内容预览: " . $responsePreview);
            }
            
            return false;
        }

        return $response;
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
