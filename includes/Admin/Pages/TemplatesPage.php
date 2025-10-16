<?php
/**
 * Email templates page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Email\Templates;

class TemplatesPage {

    /**
     * Render the templates page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }
        ?>
        <div class="wrap chrmrtns-wrap">
            <?php $this->render_content(); ?>
        </div>
        <?php
    }

    /**
     * Render templates content
     */
    private function render_content() {
        if (class_exists('Chrmrtns\\KeylessAuth\\Email\\Templates')) {
            $email_templates = new Templates();
            $email_templates->render_settings_page();
        }
    }
}
