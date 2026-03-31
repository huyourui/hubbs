<?php
/**
 * HuBBS - 系统设置类
 */

class Settings {
    private static $cache = [];
    private static $loaded = false;

    /**
     * 加载所有设置到缓存
     */
    private static function load() {
        if (self::$loaded) {
            return;
        }

        $db = DB::getInstance();
        try {
            $settings = $db->fetchAll("SELECT setting_key, setting_value FROM {$db->table('settings')}");
            foreach ($settings as $setting) {
                self::$cache[$setting['setting_key']] = $setting['setting_value'];
            }
            self::$loaded = true;
        } catch (Exception $e) {
            // 表不存在时使用默认值
            self::$cache = self::getDefaults();
        }
    }

    /**
     * 获取默认设置
     */
    private static function getDefaults() {
        return [
            'site_title' => 'HuBBS',
            'site_subtitle' => '开源论坛程序',
            'site_keywords' => 'HuBBS,论坛,开源,PHP',
            'site_description' => 'HuBBS是一款轻量级开源论坛程序',
            'enable_register' => '1',
        ];
    }

    /**
     * 获取设置值
     */
    public static function get($key, $default = '') {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    /**
     * 设置值
     */
    public static function set($key, $value) {
        $db = DB::getInstance();

        // 检查是否已存在
        $exists = $db->fetch(
            "SELECT id FROM {$db->table('settings')} WHERE setting_key = ? LIMIT 1",
            [$key]
        );

        if ($exists) {
            $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }

        // 更新缓存
        self::$cache[$key] = $value;
    }

    /**
     * 批量设置
     */
    public static function setMultiple($data) {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * 获取所有设置
     */
    public static function getAll() {
        self::load();
        return self::$cache;
    }

    /**
     * 获取完整的网站标题（标题 - 副标题）
     */
    public static function getFullTitle() {
        $title = self::get('site_title', 'HuBBS');
        $subtitle = self::get('site_subtitle', '');

        if ($subtitle) {
            return $title . ' - ' . $subtitle;
        }
        return $title;
    }

    /**
     * 是否开放注册
     */
    public static function isRegisterEnabled() {
        return self::get('enable_register', '1') === '1';
    }

    /**
     * 清除缓存
     */
    public static function clearCache() {
        self::$cache = [];
        self::$loaded = false;
    }
}
