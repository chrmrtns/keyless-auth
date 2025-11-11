<?php
/**
 * Asset Loader Class
 *
 * Handles CSS and JavaScript asset loading for Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Frontend;

/**
 * AssetLoader class
 *
 * Manages frontend asset enqueuing with dark mode support and custom CSS filters.
 */
class AssetLoader {

    /**
     * Enqueue frontend stylesheets
     *
     * Loads base styles and enhanced form styles with dark mode support.
     * Applies custom CSS variables filter for theme integration.
     *
     * @since 3.3.0
     */
    public static function enqueueFrontendStyles() {
        // Enqueue legacy styles for backward compatibility
        if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . '/assets/css/style-front-end.css')) {
            wp_register_style(
                'chrmrtns_frontend_stylesheet',
                CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/style-front-end.css',
                array(),
                CHRMRTNS_KLA_VERSION
            );
            wp_enqueue_style('chrmrtns_frontend_stylesheet');
        }

        // Get dark mode setting
        $dark_mode_setting = self::getDarkModeSetting();

        // Determine which CSS file to load
        $css_file = self::getDarkModeCssFile($dark_mode_setting);

        // Enqueue the appropriate enhanced forms stylesheet
        if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . '/assets/css/' . $css_file)) {
            wp_enqueue_style(
                'chrmrtns_kla_forms_enhanced',
                CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/' . $css_file,
                array('chrmrtns_frontend_stylesheet'), // Load after the base stylesheet
                CHRMRTNS_KLA_VERSION,
                'all'
            );

            // Apply custom CSS variables filter
            $custom_css = self::getCustomCss();
            if (!empty($custom_css)) {
                wp_add_inline_style('chrmrtns_kla_forms_enhanced', $custom_css);
            }
        }
    }

    /**
     * Get dark mode setting from options
     *
     * @return string Dark mode setting (auto|light|dark)
     */
    private static function getDarkModeSetting() {
        return get_option('chrmrtns_kla_dark_mode_setting', 'auto');
    }

    /**
     * Get CSS filename based on dark mode setting
     *
     * @param string $dark_mode_setting Dark mode setting value.
     * @return string CSS filename
     */
    private static function getDarkModeCssFile($dark_mode_setting) {
        switch ($dark_mode_setting) {
            case 'light':
                return 'forms-enhanced-light.css';
            case 'dark':
                return 'forms-enhanced-dark.css';
            case 'auto':
            default:
                return 'forms-enhanced.css';
        }
    }

    /**
     * Get custom CSS from filter
     *
     * Applies the 'chrmrtns_kla_custom_css_variables' filter to allow
     * themes and plugins to customize CSS variables without using !important.
     *
     * @since 3.3.0
     * @return string Custom CSS or empty string
     */
    private static function getCustomCss() {
        /**
         * Filter: chrmrtns_kla_custom_css_variables
         *
         * Allows themes and plugins to customize CSS variables without using !important.
         * The filtered CSS is added as inline styles after the main stylesheet,
         * ensuring proper cascade order.
         *
         * @since 3.1.0
         *
         * @param string $css Custom CSS to append after plugin styles (default: empty string)
         *
         * @example Theme integration without !important
         * add_filter('chrmrtns_kla_custom_css_variables', function($css) {
         *     return $css . '
         *         :root {
         *             --kla-primary: var(--my-theme-primary);
         *             --kla-background: var(--my-theme-bg);
         *             --kla-text: var(--my-theme-text);
         *         }
         *     ';
         * });
         *
         * @example Dark mode theme integration
         * add_filter('chrmrtns_kla_custom_css_variables', function($css) {
         *     return $css . '
         *         :root.cf-theme-light {
         *             --kla-primary: var(--primary);
         *         }
         *         :root.cf-theme-dark {
         *             --kla-primary: var(--primary);
         *             --kla-background: var(--tertiary-5);
         *         }
         *     ';
         * });
         */
        return apply_filters('chrmrtns_kla_custom_css_variables', '');
    }

    /**
     * Check if specific CSS file exists
     *
     * @param string $filename CSS filename to check.
     * @return bool True if file exists
     */
    public static function cssFileExists($filename) {
        return file_exists(CHRMRTNS_KLA_PLUGIN_DIR . '/assets/css/' . $filename);
    }

    /**
     * Get asset URL for a given filename
     *
     * @param string $filename Asset filename.
     * @param string $type     Asset type (css|js).
     * @return string Full URL to asset
     */
    public static function getAssetUrl($filename, $type = 'css') {
        return CHRMRTNS_KLA_PLUGIN_URL . 'assets/' . $type . '/' . $filename;
    }
}
