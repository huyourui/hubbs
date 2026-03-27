<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
?>
    </div>

    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container">
            <?php 
            $footerLinks = getLinks();
            if (!empty($footerLinks)): 
            ?>
                <div class="text-center mb-3">
                    <span class="text-muted me-2">友情链接：</span>
                    <?php foreach ($footerLinks as $link): ?>
                        <a href="<?php echo escape($link['url']); ?>" target="_blank" rel="noopener" 
                           class="text-decoration-none me-3"<?php echo $link['description'] ? ' title="' . escape($link['description']) . '"' : ''; ?>>
                            <?php echo escape($link['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center text-muted">
                <p class="mb-0">
                    Powered by <a href="https://huyourui.com" class="text-decoration-none" target="_blank">HuBBS</a> 
                    <span class="mx-2">|</span> 
                    &copy; <?php echo date('Y'); ?> <?php echo escape(getSetting('site_title', SITE_NAME)); ?>
                </p>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/public/assets/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($extraScripts)): ?>
    <script><?php echo $extraScripts; ?></script>
    <?php endif; ?>
</body>
</html>
