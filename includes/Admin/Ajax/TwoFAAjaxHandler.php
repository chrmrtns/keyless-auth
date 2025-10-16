<?php
/**
 * 2FA AJAX handler for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Ajax;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class TwoFAAjaxHandler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_chrmrtns_kla_admin_disable_2fa', array($this, 'disable_2fa'));
    }

    /**
     * Handle admin disable 2FA AJAX request
     */
    public function disable_2fa() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_kla_ajax_nonce')) {
            wp_send_json_error(__('Security check failed.', 'keyless-auth'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'keyless-auth'));
        }

        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'keyless-auth'));
        }

        // Disable 2FA for user
        global $chrmrtns_kla_database;
        if (!$chrmrtns_kla_database) {
            wp_send_json_error(__('Database not available.', 'keyless-auth'));
        }

        $result = $chrmrtns_kla_database->disable_user_2fa($user_id);

        if ($result) {
            wp_send_json_success(__('2FA has been disabled for the user.', 'keyless-auth'));
        } else {
            wp_send_json_error(__('Failed to disable 2FA. Please try again.', 'keyless-auth'));
        }
    }
}
