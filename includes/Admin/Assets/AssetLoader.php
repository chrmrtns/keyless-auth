<?php
/**
 * Asset loader for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Assets;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AssetLoader {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        $allowed_pages = array(
            'toplevel_page_keyless-auth',
            'keyless-auth_page_keyless-auth-settings',
            'keyless-auth_page_chrmrtns-kla-smtp-settings',
            'keyless-auth_page_chrmrtns-mail-logs',
            'keyless-auth_page_keyless-auth-options',
            'keyless-auth_page_keyless-auth-2fa-users',
            'keyless-auth_page_keyless-auth-help'
        );

        if (in_array($hook, $allowed_pages)) {
            // Enqueue main admin stylesheet
            wp_enqueue_style(
                'chrmrtns_kla_admin_stylesheet',
                CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/style-back-end.css',
                array(),
                CHRMRTNS_KLA_VERSION
            );

            // Enqueue additional admin styles
            wp_enqueue_style(
                'chrmrtns_kla_admin_style',
                CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/admin-style.css',
                array(),
                CHRMRTNS_KLA_VERSION
            );

            // Add inline styles for header and cards
            $inline_css = '
                .chrmrtns-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
                .chrmrtns-header-logo { width: 40px; height: 40px; border-radius: 6px; }
                .chrmrtns-header small { color: #666; font-weight: normal; margin-left: 10px; }
                #setting-error-settings_saved { max-width: 800px; }
                .chrmrtns-badge { display: none; }
                .chrmrtns_kla_card { position: relative; margin-top: 20px; padding: 1.2em 2em 1.5em; min-width: 600px; max-width: 100%; border: 1px solid #c3c4c7; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); background: #fff; box-sizing: border-box; border-radius: 4px; }
                .chrmrtns_kla_card h2 { margin-top: 0; color: #23282d; border-bottom: 1px solid #e1e1e1; padding-bottom: 8px; margin-bottom: 15px; }
                .chrmrtns_kla_card h3 { color: #23282d; margin-top: 20px; margin-bottom: 10px; }
            ';
            wp_add_inline_style('chrmrtns_kla_admin_stylesheet', $inline_css);

            // Enqueue admin JavaScript
            wp_enqueue_script(
                'chrmrtns_kla_admin_script',
                CHRMRTNS_KLA_PLUGIN_URL . 'assets/js/admin-script.js',
                array('jquery'),
                CHRMRTNS_KLA_VERSION,
                true
            );

            // Localize script for AJAX
            wp_localize_script('chrmrtns_kla_admin_script', 'chrmrtns_kla_ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('chrmrtns_kla_ajax_nonce')
            ));

            // Enqueue editor scripts for settings page
            if ($hook === 'keyless-auth_page_keyless-auth-settings') {
                wp_enqueue_editor();
                wp_enqueue_media();
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_style('wp-color-picker');
            }
        }
    }
}
