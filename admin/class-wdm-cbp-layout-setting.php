<?php

namespace wisdmlabs\cpb;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WdmCPBLayoutSetting')) {
    class WdmCPBLayoutSetting
    {
        public static function getColumnField($mainProductId)
        {
            $layout = get_post_meta($mainProductId, '_wdm_desktop_layout', true);
            $selectedLayout = basename($layout);
            $gridField = array(
                'vertical' => '_wdm_column_field',
                'horizontal' => '_wdm_item_field',
                );

            return apply_filters('wdm_columns_gift_layout', $gridField[$selectedLayout]);
        }

        public static function getProductField($mainProductId)
        {
            $layout = get_post_meta($mainProductId, '_wdm_desktop_layout', true);
            $selectedLayout = basename($layout);
            $gridField = array(
                'vertical' => '_wdm_product_grid',
                'horizontal' => '_wdm_product_item_grid',
                );

            return apply_filters('wdm_columns_product_layout', $gridField[$selectedLayout]);
        }

        public static function getLayoutDirectories()
        {
            $layoutDirectories = apply_filters('cpb_desktop_template_directories', array(
                plugin_dir_path(dirname(__FILE__)) . "templates/product-layouts/desktop-layouts/",
                get_stylesheet_directory() . '/custom-product-boxes/product-layouts/desktop-layouts/',
                get_template_directory() . '/custom-product-boxes/product-layouts/desktop-layouts/'
            ));

            return array_unique($layoutDirectories);
        }

        public static function getAllLayouts()
        {
            global $woo_wdm_bundle;

            $layoutDirectories = self::getLayoutDirectories();
            if (empty($layoutDirectories) || !is_array($layoutDirectories)) {
                return;
            }

            $allLayouts = array();

            foreach ($layoutDirectories as $layoutDirectory) {
                $layouts = array_filter(glob("{$layoutDirectory}*"), 'is_dir');
                
                foreach ($layouts as $layout) {
                    $layoutName = self::getLayoutName($layout);
                    if (!empty($layoutName)) {
                        $allLayouts[$layout] = $layoutName;
                    }
                }
            }
            //put vertical layout at top
            $verticalLayoutKey = $woo_wdm_bundle->pluginPath() . '/templates/product-layouts/desktop-layouts/vertical';
            $allLayouts = array($verticalLayoutKey => $allLayouts[$verticalLayoutKey]) + $allLayouts;
            return $allLayouts;
        }

        public static function getLayoutName($layout)
        {
            $layoutName = "";
            if (file_exists($layout . '/index.php')) {
                $type = self::getSourceType($layout);
                if (empty($type)) {
                    return;
                }
                $sourceName = self::getSourceName($type, $layout);
                
                $layoutData = implode('', file($layout . '/index.php'));
                
                if (preg_match('|Template Name:(.*)$|mi', $layoutData, $name)) {
                    $layoutName = sprintf(__('%s | Source Type: %s | Source: %s', 'custom-product-boxes'), _cleanup_header_comment($name[1]), ucfirst($type), $sourceName);
                }
            }

            return $layoutName;
        }

        public static function getSourceName($type, $layout)
        {
            switch ($type) {
                case 'plugins':
                    $sourceName = self::getPluginName($layout);
                    return $sourceName;
                case 'themes':
                    $sourceName = self::getThemeName($layout);
                    return $sourceName;
            }
        }
  
        public static function getActivePlugins()
        {
            static $plugins;
            
            if (!isset($plugins)) {
                $plugins = apply_filters('active_plugins', get_option('active_plugins'));
            }

            return $plugins;
        }

        public static function getPluginName($layout)
        {
            $plugins = self::getActivePlugins();
            $allPlugins = get_plugins();

            foreach ($plugins as $pluginDir) {
                $offset = strpos($pluginDir, '/');
                $compare = substr($pluginDir, 0, $offset);
                if (strpos($layout, $compare) !== false) {
                    return $allPlugins[$pluginDir]['Name'];
                }
            }
        }

        public static function getThemeName($layout)
        {
            $offset = strpos($layout, 'themes');
            $themeDirectories = substr($layout, 0, strlen('themes') + $offset);
            $themeDirectories = glob($themeDirectories . '/*', GLOB_ONLYDIR);

            foreach ($themeDirectories as $themeDir) {
                $position = strpos($layout, $themeDir);
                
                if ($position !== false) {
                    $position += strlen($themeDir);
                    $subStr = substr($layout, $position, 1);
                    if ($subStr === '/') {
                        return self::getThemeSourceName($themeDir);
                    }
                }
            }
            return $layout;
        }

        public static function getThemeSourceName($themeDir)
        {
            $themeData = implode('', file($themeDir . '/style.css'));
            if (preg_match('|Theme Name:(.*)$|mi', $themeData, $name)) {
                $themeName = sprintf(__('%s Theme', 'custom-product-boxes'), _cleanup_header_comment($name[1]));
                return $themeName;
            }
        }

        public static function getSourceType($layout)
        {
            $sourceTypes = array('plugins', 'themes', 'mu-plugins');

            foreach ($sourceTypes as $sourceType) {
                if (strpos($layout, $sourceType) !== false) {
                    return $sourceType;
                }
            }
        }
    }
}
