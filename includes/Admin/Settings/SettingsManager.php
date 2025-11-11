<?php
/**
 * Settings manager for Keyless Auth
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Settings;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SettingsManager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register all plugin settings
     */
    public function register_settings() {
        // Template settings
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_email_template', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_custom_email_body', array(
            'sanitize_callback' => 'wp_kses_post'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_custom_email_styles', array(
            'sanitize_callback' => 'wp_strip_all_tags'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_hover_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_link_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_link_hover_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_text_color', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_hover_text_color', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));

        // Options settings
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_enable_wp_login', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_login_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_redirect_wp_login', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_redirect_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_2fa_setup_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_dark_mode_setting', array(
            'sanitize_callback' => array($this, 'sanitize_dark_mode_setting'),
            'default' => 'auto'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_disable_xmlrpc', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_disable_app_passwords', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_prevent_user_enumeration', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_password_reset', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_password_reset_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_support_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_enable_woocommerce', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_enable_rest_api', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
    }

    /**
     * Sanitize checkbox input
     *
     * @param mixed $input
     * @return string
     */
    public function sanitize_checkbox($input) {
        return ($input === '1' || $input === 1 || $input === true) ? '1' : '0';
    }

    /**
     * Sanitize dark mode setting
     *
     * @param string $input
     * @return string
     */
    public function sanitize_dark_mode_setting($input) {
        $allowed = array('auto', 'light', 'dark');
        return in_array($input, $allowed, true) ? $input : 'auto';
    }
}
