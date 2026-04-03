<?php
/**
 * 调试 header 中的链接
 */
require_once 'core/config.php';
require_once 'core/functions.php';
require_once 'core/db.php';
require_once 'core/Auth.php';

// 模拟发帖页面的环境
$_GET['module'] = 'post';
$_GET['action'] = 'create';

$action = $_GET['action'] ?? 'list';
$forumId = $_GET['forum_id'] ?? 0;

echo "<h2>Current Page Simulation</h2>";
echo "<pre>";
echo "module: " . ($_GET['module'] ?? 'not set') . "\n";
echo "action: " . ($_GET['action'] ?? 'not set') . "\n";
echo "forumId: " . $forumId . "\n";
echo "</pre>";

echo "<h2>Header Links</h2>";
echo "<pre>";

// 首页链接
$homeUrl = base_url();
$isHomeActive = ($action === 'list' && empty($forumId));
echo "首页链接: {$homeUrl}\n";
echo "是否active: " . ($isHomeActive ? 'yes' : 'no') . "\n\n";

// 发帖链接
$postUrl = base_url('index.php?module=post&action=create');
echo "发帖链接: {$postUrl}\n\n";

// 其他测试
echo "base_url(): " . base_url() . "\n";
echo "base_url('index.php'): " . base_url('index.php') . "\n";
echo "</pre>";

echo "<h2>Actual HTML Output</h2>";
echo '<div style="background:#f5f5f5;padding:10px;">';
echo '<a href="' . $homeUrl . '" class="nav-link' . ($isHomeActive ? ' active' : '') . '">首页</a>';
echo ' | ';
echo '<a href="' . $postUrl . '" class="nav-link">发帖</a>';
echo '</div>';
