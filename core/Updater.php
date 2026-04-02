<?php
/**
 * HuBBS - 自动更新系统
 * 支持自建更新服务器 + Gitee + 手动上传
 */

class Updater {
    // 自建更新服务器配置（优先使用）
    private $updateServer = 'https://update.bbs.huyourui.com';
    private $useCustomServer = true; // 设置为true优先使用自建服务器
    
    // Gitee 仓库信息（备用）
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
        
        // 优先使用自建更新服务器
        if ($this->useCustomServer) {
            $result = $this->checkUpdateFromCustomServer();
            if ($result && empty($result['error'])) {
                return $result;
            }
            // 自建服务器失败，记录日志并尝试Gitee
            error_log('[HuBBS Updater] 自建服务器检查失败，尝试Gitee: ' . ($result['error'] ?? 'Unknown'));
        }
        
        // 使用Gitee作为备用
        return $this->checkUpdateFromGitee();
    }
    
    /**
     * 从自建更新服务器检查更新
     */
    private function checkUpdateFromCustomServer() {
        $localVersion = HUBBS_VERSION;
        $url = rtrim($this->updateServer, '/') . '/check.php?version=' . urlencode($localVersion);
        
        $response = $this->httpGet($url);
        if (!$response) {
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => '无法连接自建更新服务器'
            ];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => '自建服务器返回数据格式错误'
            ];
        }
        
        if (empty($data['success'])) {
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => $data['error'] ?? '自建服务器返回错误'
            ];
        }
        
        // 转换格式以兼容原有接口
        return [
            'has_update' => $data['has_update'] ?? false,
            'local_version' => $localVersion,
            'remote_version' => $data['latest_version'] ?? null,
            'release_info' => [
                'tag_name' => 'v' . ($data['latest_version'] ?? ''),
                'body' => $data['release_notes'] ?? '',
                'published_at' => $data['release_date'] ?? '',
                'download_url' => $data['download_url'] ?? '',
                'file_size' => $data['file_size'] ?? 0,
                'file_hash' => $data['file_hash'] ?? '',
            ],
            'error' => null
        ];
    }
    
    /**
     * 从Gitee检查更新（备用）
     */
    private function checkUpdateFromGitee() {
        $localVersion = HUBBS_VERSION;
        $releaseInfo = $this->getLatestRelease();

        if (!$releaseInfo) {
            return [
                'has_update' => false,
                'local_version' => $localVersion,
                'remote_version' => null,
                'release_info' => null,
                'error' => '无法获取远程版本信息，请检查网络连接'
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
     * @param string $downloadUrl 可选，指定下载链接
     * @return array ['success' => bool, 'file' => string, 'error' => string]
     */
    public function downloadUpdate($version, $downloadUrl = '') {
        // 确保版本号带有 v 前缀
        $tagName = (strpos($version, 'v') === 0) ? $version : 'v' . $version;
        
        $fileName = "hubbs-{$version}.zip";
        $filePath = $this->updateDir . $fileName;
        
        // 删除旧文件
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 方法1: 如果提供了下载链接（来自自建服务器），优先使用
        if (!empty($downloadUrl)) {
            error_log("[HuBBS Updater] 使用自建服务器下载链接: {$downloadUrl}");
            $content = $this->httpGet($downloadUrl);
            if ($content && strlen($content) >= 4 && substr($content, 0, 4) === "PK\x03\x04") {
                if (file_put_contents($filePath, $content) === false) {
                    return ['success' => false, 'error' => '保存更新包失败，请检查目录权限'];
                }
                return ['success' => true, 'file' => $filePath];
            }
            error_log("[HuBBS Updater] 自建服务器下载失败，尝试其他方式");
        }
        
        // 方法2: 尝试使用 git 命令克隆并打包（如果系统支持）
        if ($this->isGitAvailable()) {
            $result = $this->downloadWithGit($tagName, $filePath);
            if ($result['success']) {
                return $result;
            }
            error_log("[HuBBS Updater] Git方式下载失败: " . $result['error']);
        }
        
        // 方法3: 尝试Gitee下载（可能会被验证码拦截）
        $downloadUrls = [
            "https://gitee.com/api/v5/repos/{$this->repoOwner}/{$this->repoName}/zipball/{$tagName}",
            "https://gitee.com/{$this->repoOwner}/{$this->repoName}/zipball/{$tagName}",
            "https://gitee.com/{$this->repoOwner}/{$this->repoName}/repository/archive/{$tagName}.zip",
            "https://gitee.com/{$this->repoOwner}/{$this->repoName}/repository/archive/{$tagName}",
        ];
        
        $lastError = '';
        foreach ($downloadUrls as $index => $url) {
            error_log("[HuBBS Updater] 尝试Gitee下载方式" . ($index + 1) . ": {$url}");
            
            $content = $this->httpGet($url);
            if (!$content) {
                $lastError = '下载更新包失败，请检查网络连接';
                continue;
            }
            
            // 验证下载的是否是有效的 zip 文件（检查文件头）
            if (strlen($content) >= 4 && substr($content, 0, 4) === "PK\x03\x04") {
                if (file_put_contents($filePath, $content) === false) {
                    return ['success' => false, 'error' => '保存更新包失败，请检查目录权限'];
                }
                
                error_log("[HuBBS Updater] Gitee下载成功，使用方式" . ($index + 1));
                return ['success' => true, 'file' => $filePath];
            }
            
            // 记录失败的内容预览
            $contentPreview = substr($content, 0, 100);
            error_log("[HuBBS Updater] Gitee方式" . ($index + 1) . "返回的不是ZIP: " . $contentPreview);
            $lastError = '下载的文件不是有效的 ZIP 格式';
        }
        
        // 所有方式都失败了，返回详细错误信息
        return [
            'success' => false, 
            'error' => '无法下载更新包。可能原因：\n1. 更新服务器无法连接\n2. Gitee开启了机器验证\n3. 服务器未安装Git\n4. 版本号不存在\n\n建议手动下载更新包上传，或联系管理员处理。'
        ];
    }
    
    /**
     * 检查系统是否支持Git
     * @return bool
     */
    private function isGitAvailable() {
        exec('which git 2>/dev/null', $output, $returnCode);
        return $returnCode === 0 && !empty($output);
    }
    
    /**
     * 使用Git下载指定版本的代码
     * @param string $tagName
     * @param string $zipFilePath
     * @return array
     */
    private function downloadWithGit($tagName, $zipFilePath) {
        $tempDir = $this->updateDir . 'git-clone-' . time() . '/';
        
        if (!mkdir($tempDir, 0755, true)) {
            return ['success' => false, 'error' => '创建临时目录失败'];
        }
        
        try {
            // 克隆仓库（浅克隆，只下载最新提交）
            $repoUrl = "https://gitee.com/{$this->repoOwner}/{$this->repoName}.git";
            $cloneCmd = "cd " . escapeshellarg($tempDir) . " && git clone --depth 1 --branch " . escapeshellarg($tagName) . " " . escapeshellarg($repoUrl) . " hubbs 2>&1";
            
            error_log("[HuBBS Updater] 执行Git克隆: {$cloneCmd}");
            exec($cloneCmd, $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->removeDirectory($tempDir);
                return ['success' => false, 'error' => 'Git克隆失败: ' . implode("\n", $output)];
            }
            
            $sourceDir = $tempDir . 'hubbs/';
            if (!is_dir($sourceDir)) {
                $this->removeDirectory($tempDir);
                return ['success' => false, 'error' => '克隆后的目录不存在'];
            }
            
            // 打包为zip
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->removeDirectory($tempDir);
                return ['success' => false, 'error' => '无法创建ZIP文件'];
            }
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir));
                
                if (!$file->isDir()) {
                    $zip->addFile($filePath, $relativePath);
                } else {
                    $zip->addEmptyDir($relativePath);
                }
            }
            
            $zip->close();
            
            // 清理临时目录
            $this->removeDirectory($tempDir);
            
            if (!file_exists($zipFilePath)) {
                return ['success' => false, 'error' => 'ZIP文件创建失败'];
            }
            
            return ['success' => true, 'file' => $zipFilePath];
            
        } catch (Exception $e) {
            $this->removeDirectory($tempDir);
            return ['success' => false, 'error' => 'Git下载异常: ' . $e->getMessage()];
        }
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
