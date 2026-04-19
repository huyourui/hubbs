<?php
/**
 * HuBBS - URL 调试页面
 * 用于诊断 base_url() 函数在服务器上的问题
 */

// 加载核心配置和函数
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/functions.php';

// 输出调试信息
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>URL 调试 - HuBBS</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; }
        h2 { color: #666; font-size: 18px; margin-top: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #555; width: 40%; }
        td { color: #333; font-family: monospace; }
        .value { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; }
        .result { background: #e8f5e9; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .result.error { background: #ffebee; }
        .test-link { display: inline-block; margin: 10px 10px 10px 0; padding: 10px 20px; background: #ff6b6b; color: #fff; text-decoration: none; border-radius: 6px; }
        .test-link:hover { background: #ff5252; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 HuBBS URL 调试页面</h1>
        
        <h2>服务器变量</h2>
        <table>
            <tr>
                <th>HUBBS_ROOT</th>
                <td><span class="value"><?php echo HUBBS_ROOT; ?></span></td>
            </tr>
            <tr>
                <th>$_SERVER['DOCUMENT_ROOT']</th>
                <td><span class="value"><?php echo isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '未设置'; ?></span></td>
            </tr>
            <tr>
                <th>$_SERVER['SCRIPT_NAME']</th>
                <td><span class="value"><?php echo isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '未设置'; ?></span></td>
            </tr>
            <tr>
                <th>$_SERVER['PHP_SELF']</th>
                <td><span class="value"><?php echo isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '未设置'; ?></span></td>
            </tr>
            <tr>
                <th>$_SERVER['REQUEST_URI']</th>
                <td><span class="value"><?php echo isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '未设置'; ?></span></td>
            </tr>
            <tr>
                <th>$_SERVER['SCRIPT_FILENAME']</th>
                <td><span class="value"><?php echo isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '未设置'; ?></span></td>
            </tr>
        </table>
        
        <h2>计算结果</h2>
        <?php
        // 详细计算过程
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $phpSelf = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
        $rootPath = HUBBS_ROOT;
        
        // 方案1：使用 SCRIPT_NAME/PHP_SELF
        $entryScript = !empty($scriptName) ? $scriptName : $phpSelf;
        $basePath1 = '';
        if (!empty($entryScript)) {
            $lastSlash = strrpos($entryScript, '/');
            if ($lastSlash !== false) {
                $basePath1 = substr($entryScript, 0, $lastSlash);
            }
        }
        
        // 方案2：使用 HUBBS_ROOT 和 DOCUMENT_ROOT
        $rootPathNorm = str_replace('\\', '/', $rootPath);
        $docRootNorm = str_replace('\\', '/', $docRoot);
        $basePath2 = '';
        if (!empty($docRootNorm) && strpos($rootPathNorm, $docRootNorm) === 0) {
            $basePath2 = substr($rootPathNorm, strlen($docRootNorm));
        }
        ?>
        <table>
            <tr>
                <th>使用的入口脚本</th>
                <td><span class="value"><?php echo $entryScript ?: '无'; ?></span></td>
            </tr>
            <tr>
                <th>方案1结果 (SCRIPT_NAME)</th>
                <td><span class="value"><?php echo $basePath1; ?></span></td>
            </tr>
            <tr>
                <th>方案2结果 (DOCUMENT_ROOT)</th>
                <td><span class="value"><?php echo $basePath2; ?></span></td>
            </tr>
            <tr>
                <th>最终 base_url()</th>
                <td><span class="value" style="background: #ff6b6b; color: #fff;"><?php echo base_url(); ?></span></td>
            </tr>
            <tr>
                <th>base_url('index.php')</th>
                <td><span class="value"><?php echo base_url('index.php'); ?></span></td>
            </tr>
        </table>
        
        <h2>测试结果</h2>
        <div class="result">
            <p><strong>Logo 链接:</strong> <a href="<?php echo base_url(); ?>"><?php echo base_url(); ?></a></p>
            <p><strong>首页链接:</strong> <a href="<?php echo base_url('index.php'); ?>"><?php echo base_url('index.php'); ?></a></p>
        </div>
        
        <h2>测试导航</h2>
        <a href="<?php echo base_url('index.php'); ?>" class="test-link">🏠 首页</a>
        <a href="<?php echo base_url('index.php?module=admin'); ?>" class="test-link">⚙️ 后台</a>
        <a href="<?php echo base_url('debug_url.php'); ?>" class="test-link">🔧 刷新调试页</a>
        
        <p style="margin-top: 30px; color: #999; font-size: 12px;">
            请将此页面的截图发送给开发者，以便诊断问题。
        </p>
    </div>
</body>
</html>
