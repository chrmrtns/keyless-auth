<?php
/**
 * Mail logs page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Email\MailLogger;

class MailLogsPage {

    /**
     * Render the mail logs page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        // Render mail logs page if Mail Logger class is loaded
        if (class_exists('Chrmrtns\\KeylessAuth\\Email\\MailLogger')) {
            $mail_logger = new MailLogger();
            $mail_logger->render_mail_logs_page();
        }
    }
}
