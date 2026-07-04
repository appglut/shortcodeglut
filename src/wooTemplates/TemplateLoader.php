<?php
namespace Shortcodeglut\wooTemplates;

if (!defined('ABSPATH')) {
    exit;
}

class TemplateLoader {

    private static $templates_dir;
    private static $cache = array();

    public static function init() {
        self::$templates_dir = SHORTCODEGLUT_PATH . 'src/wooTemplates/templates/';
    }

    public static function get_all_templates() {
        if (!empty(self::$cache)) {
            return self::$cache;
        }

        $templates = array();
        $dirs = glob(self::$templates_dir . '*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $template_id = basename($dir);
            $json_file = $dir . '/template.json';

            if (file_exists($json_file)) {
                $json_data = json_decode(file_get_contents($json_file), true);
                if ($json_data) {
                    $templates[$template_id] = array(
                        'template_id' => $template_id,
                        'template_name' => $json_data['name'] ?? $template_id,
                        // Don't set template_html for file-based templates - they use PHP include
                        'template_html' => '',
                        'preview_html' => self::get_preview_html($template_id),
                        'preview_css' => self::get_preview_css($template_id),
                        'is_default' => $json_data['is_default'] ?? 0,
                        'description' => $json_data['description'] ?? '',
                        'category' => $json_data['category'] ?? 'general',
                    );
                }
            }
        }

        self::$cache = $templates;
        return $templates;
    }

    public static function get_template_html($template_id) {
        $file = self::$templates_dir . $template_id . '/template.php';
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return '';
    }

    public static function get_template_css($template_id) {
        $file = self::$templates_dir . $template_id . '/style.css';
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return '';
    }

    public static function get_preview_html($template_id) {
        $file = self::$templates_dir . $template_id . '/preview.html';
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return '';
    }

    public static function get_preview_css($template_id) {
        $file = self::$templates_dir . $template_id . '/preview.css';
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return '';
    }

    public static function get_template_meta($template_id) {
        $file = self::$templates_dir . $template_id . '/template.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return array();
    }

    public static function template_exists($template_id) {
        return is_dir(self::$templates_dir . $template_id);
    }

    public static function clear_cache() {
        self::$cache = array();
    }
}
