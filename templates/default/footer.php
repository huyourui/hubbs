        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <?php
            // 获取版权信息，并动态替换版本号
            $copyright = Settings::get('site_copyright', 'HuBBS - 开源论坛程序 v' . HUBBS_VERSION);
            // 将版权信息中的版本号替换为当前版本号（支持 v1.0.0 或 v1.1.0 等格式）
            $copyright = preg_replace('/v\d+\.\d+\.\d+/', 'v' . HUBBS_VERSION, $copyright);
            ?>
            <p>&copy; <?php echo date('Y'); ?> <?php echo $copyright; ?></p>
            <p>Powered by <a href="https://bbs.huyourui.com" target="_blank" rel="noopener">HuBBS</a> | 支持亿级数据架构</p>
        </div>
    </footer>
    <script src="<?php echo base_url('static/js/editor.js?v=' . HUBBS_VERSION); ?>"></script>
</body>
</html>
