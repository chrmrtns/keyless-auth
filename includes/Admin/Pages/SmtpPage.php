<?php
/**
 * SMTP settings page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Email\SMTP;

class SmtpPage {

    /**
     * Render the SMTP settings page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        // Handle test email and cache clearing if SMTP class is loaded
        if (class_exists('Chrmrtns\\KeylessAuth\\Email\\SMTP')) {
            $smtp = new SMTP();
            $smtp->handle_test_email_submission();
            $smtp->handle_cache_clear();
            settings_errors('chrmrtns_kla_smtp_test_email');
            settings_errors('chrmrtns_kla_smtp_cache');
        }

        ?>
        <div class="wrap chrmrtns-wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('SMTP Settings', 'keyless-auth'); ?>
            </h1>
            <p><?php esc_html_e('Configure SMTP settings to ensure reliable email delivery for your passwordless login emails.', 'keyless-auth'); ?></p>

            <form action='options.php' method='post'>
                <?php
                settings_fields('chrmrtns_kla_smtp_settings_group');
                do_settings_sections('chrmrtns-kla-smtp-settings');
                submit_button();
                ?>
            </form>

            <?php if (class_exists('Chrmrtns\\KeylessAuth\\Email\\SMTP')): ?>
                <hr />
                <h2><?php esc_html_e('Send Test Email', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Send a test email to verify your SMTP configuration is working correctly.', 'keyless-auth'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('chrmrtns_kla_smtp_send_test_email_action', 'chrmrtns_kla_smtp_send_test_email_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Test Email Address', 'keyless-auth'); ?></th>
                            <td>
                                <input type="email" name="test_email_address" value="<?php echo esc_attr(get_option('admin_email')); ?>" size="50">
                                <p class="description"><?php esc_html_e('Email address to send the test email to. Defaults to admin email.', 'keyless-auth'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Send Test Email', 'keyless-auth'), 'secondary', 'chrmrtns_kla_smtp_send_test_email'); ?>
                </form>

                <hr />
                <h2><?php esc_html_e('Clear Settings Cache', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('If your SMTP settings are not updating properly, clear the cache to force the plugin to reload settings from the database.', 'keyless-auth'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('chrmrtns_kla_smtp_clear_cache_action', 'chrmrtns_kla_smtp_clear_cache_nonce'); ?>
                    <?php submit_button(__('Clear SMTP Cache', 'keyless-auth'), 'secondary', 'chrmrtns_kla_smtp_clear_cache'); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
