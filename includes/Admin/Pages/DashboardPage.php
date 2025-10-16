<?php
/**
 * Dashboard page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class DashboardPage {

    /**
     * Render the dashboard page
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
     * Render dashboard content
     */
    private function render_content() {
        ?>
        <h1 class="chrmrtns-header">
            <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
            <?php esc_html_e('Keyless Auth', 'keyless-auth'); ?>
            <small>v.<?php echo esc_html(CHRMRTNS_KLA_VERSION); ?></small>
        </h1>
        <p class="chrmrtns-text">
            <?php
            $successful_logins = get_option('chrmrtns_kla_successful_logins', 0);
            echo wp_kses(
                sprintf(
                    /* translators: %d: number of successful passwordless logins */
                    __('<p>A front-end login form without a password.</p><p><strong style="font-size: 16px; color:#d54e21;">%d</strong> successful logins so far.</p>', 'keyless-auth'),
                    intval($successful_logins)
                ),
                array(
                    'p' => array(),
                    'strong' => array(
                        'style' => array()
                    )
                )
            );
            ?>
        </p>

        <div class="chrmrtns-callout">
            <?php esc_html_e('One time password for WordPress', 'keyless-auth'); ?>
        </div>

        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h2><?php esc_html_e('[keyless-auth] shortcode', 'keyless-auth'); ?></h2>
                <p><?php echo wp_kses(
                    __('Just place <strong class="nowrap">[keyless-auth]</strong> shortcode in a page or a widget and you\'re good to go.', 'keyless-auth'),
                    array('strong' => array('class' => array()))
                ); ?></p>
                <p><textarea class="chrmrtns-shortcode textarea" readonly onclick="this.select();" style="width: 100%; height: 60px; padding: 10px;">[keyless-auth]</textarea></p>
            </div>

            <div>
                <h2><?php esc_html_e('An alternative to passwords', 'keyless-auth'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Visual email template selection with live previews', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('WYSIWYG email editor with HTML support', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Advanced color controls (hex, RGB, HSL, HSLA)', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Separate button and link color customization', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Enhanced security with timing attack protection', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('SMTP configuration for reliable email delivery', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Comprehensive email logging and monitoring', 'keyless-auth'); ?></li>
                </ul>
                <p><?php echo wp_kses(
                    __('Keyless Auth <strong>does not</strong> replace the default login functionality in WordPress. Instead you can have the two work in parallel.', 'keyless-auth'),
                    array('strong' => array())
                ); ?></p>
            </div>
        </div>

        <hr>

        <h2><?php esc_html_e('Advanced Email Features', 'keyless-auth'); ?></h2>
        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h3><?php esc_html_e('SMTP Configuration', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Configure SMTP settings to ensure reliable email delivery with support for major providers like Gmail, Outlook, Mailgun, and SendGrid.', 'keyless-auth'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=chrmrtns-kla-smtp-settings')); ?>" class="button button-primary"><?php esc_html_e('Configure SMTP', 'keyless-auth'); ?></a></p>
            </div>
            <div>
                <h3><?php esc_html_e('Mail Logging', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Track and monitor all emails sent from your WordPress site with detailed logging including timestamps, recipients, and content.', 'keyless-auth'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=chrmrtns-mail-logs')); ?>" class="button button-primary"><?php esc_html_e('View Mail Logs', 'keyless-auth'); ?></a></p>
            </div>
        </div>
        <?php
    }
}
