<?php
/**
 * 调试 base_url 函数
 */
require_once 'core/config.php';
require_once 'core/functions.php';

echo "<h2>Server Variables</h2>";
echo "<pre>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "</pre>";

echo "<h2>base_url() Test</h2>";
echo "<pre>";
echo "base_url(): " . base_url() . "\n";
echo "base_url('index.php'): " . base_url('index.php') . "\n";
echo "base_url('static/css/style.css'): " . base_url('static/css/style.css') . "\n";
echo "</pre>";

echo "<h2>dirname() Test</h2>";
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
echo "<pre>";
echo "dirname('{$scriptName}'): " . dirname($scriptName) . "\n";
echo "</pre>";
