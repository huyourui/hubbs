<?php
/**
 * HuBBS 版本更新脚本
 * 用法: php update_version.php [新版本号]
 * 示例: php update_version.php 1.9.0
 */

// 获取新版本号
$newVersion = $argv[1] ?? null;

if (!$newVersion) {
    echo "用法: php update_version.php [新版本号]\n";
    echo "示例: php update_version.php 1.9.0\n";
    exit(1);
}

// 验证版本号格式
if (!preg_match('/^\d+\.\d+\.\d+$/', $newVersion)) {
    echo "错误: 版本号格式不正确，应为 x.x.x 格式\n";
    exit(1);
}

$rootDir = __DIR__ . '/';
$today = date('Y-m-d');

echo "=== HuBBS 版本更新工具 ===\n";
echo "新版本号: $newVersion\n";
echo "更新日期: $today\n\n";

// 1. 更新 core/config.php
echo "[1/3] 更新 core/config.php...\n";
$configFile = $rootDir . 'core/config.php';
$configContent = file_get_contents($configFile);
$configContent = preg_replace(
    "/define\('HUBBS_VERSION', '[^']+'\);/",
    "define('HUBBS_VERSION', '$newVersion');",
    $configContent
);
file_put_contents($configFile, $configContent);
echo "    ✓ 已更新版本号为 $newVersion\n\n";

// 2. 更新 README.md 标题
echo "[2/3] 更新 README.md 标题...\n";
$readmeFile = $rootDir . 'README.md';
$readmeContent = file_get_contents($readmeFile);
$readmeContent = preg_replace(
    '/# HuBBS v[\d.]+ 发布说明/',
    "# HuBBS v$newVersion 发布说明",
    $readmeContent
);

// 3. 在更新日志中添加新条目
echo "[3/3] 添加更新日志条目...\n";
$changelogEntry = "### v$newVersion ($today)\n";
$changelogEntry .= "- **更新内容**\n";
$changelogEntry .= "  - 在此添加更新内容\n";

// 在 "## 更新日志" 后面插入新条目
$readmeContent = preg_replace(
    '/(## 更新日志\n\n)/',
    "$1$changelogEntry\n",
    $readmeContent
);

file_put_contents($readmeFile, $readmeContent);
echo "    ✓ 已添加更新日志条目，日期: $today\n\n";

echo "=== 版本更新完成 ===\n";
echo "请编辑 README.md，完善更新日志内容\n";
echo "然后执行 git 提交并推送到 Gitee\n";
