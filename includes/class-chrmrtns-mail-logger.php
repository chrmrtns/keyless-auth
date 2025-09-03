<?php
/**
 * Mail logging functionality for Passwordless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Mail_Logger {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_mail_logs_post_type'));
        add_action('admin_init', array($this, 'handle_mail_logs_actions'));
        add_filter('wp_mail', array($this, 'log_mail_event'), 10, 1);
    }
    
    /**
     * Register custom post type for mail logs
     */
    public function register_mail_logs_post_type() {
        register_post_type('chrmrtns_mail_logs', array(
            'public' => false,
            'publicly_queryable' => false,
            'show_in_menu' => false,
        ));
    }
    
    /**
     * Handle mail logs actions
     */
    public function handle_mail_logs_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle enabling/disabling mail logging
        if (isset($_POST['chrmrtns_mail_logging_submit'])) {
            if (!isset($_POST['chrmrtns_mail_logs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_mail_logs_nonce'])), 'chrmrtns_mail_logs_settings')) {
                add_settings_error(
                    'chrmrtns_mail_logs_settings',
                    'chrmrtns_mail_logs_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'passwordless-auth'),
                    'error'
                );
                return;
            }
            
            if (isset($_POST['chrmrtns_mail_logging_enabled'])) {
                update_option('chrmrtns_mail_logging_enabled', '1');
            } else {
                update_option('chrmrtns_mail_logging_enabled', '0');
            }
            
            // Show success message
            add_settings_error(
                'chrmrtns_mail_logs_settings',
                'chrmrtns_mail_logs_saved',
                __('Mail logging settings saved successfully!', 'passwordless-auth'),
                'updated'
            );
        }

        // Handle mail log size limit
        if (isset($_POST['chrmrtns_mail_log_size_limit'])) {
            $size_limit = intval($_POST['chrmrtns_mail_log_size_limit']);
            if ($size_limit < 1) {
                $size_limit = 100;
            }
            update_option('chrmrtns_mail_log_size_limit', $size_limit);
        }

        // Handle clearing all mail logs
        if (isset($_POST['chrmrtns_clear_mail_logs'])) {
            if (!isset($_POST['chrmrtns_clear_logs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_clear_logs_nonce'])), 'chrmrtns_clear_mail_logs')) {
                add_settings_error(
                    'chrmrtns_mail_logs_settings',
                    'chrmrtns_clear_logs_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'passwordless-auth'),
                    'error'
                );
                return;
            }
            
            $args = array(
                'post_type'      => 'chrmrtns_mail_logs',
                'posts_per_page' => -1,
                'post_status'    => 'any'
            );
            $logs = get_posts($args);
            foreach ($logs as $log) {
                wp_delete_post($log->ID, true);
            }
            
            add_settings_error(
                'chrmrtns_mail_logs_settings',
                'chrmrtns_mail_logs_cleared',
                sprintf(
                    /* translators: %d: number of deleted mail logs */
                    __('Successfully deleted %d mail logs.', 'passwordless-auth'), 
                    count($logs)
                ),
                'updated'
            );
        }

        // Handle deleting a single mail log
        if (isset($_POST['chrmrtns_delete_log']) && isset($_POST['chrmrtns_delete_log_id'])) {
            if (!isset($_POST['chrmrtns_delete_log_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_delete_log_nonce'])), 'chrmrtns_delete_mail_log')) {
                add_settings_error(
                    'chrmrtns_mail_logs_settings',
                    'chrmrtns_delete_log_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'passwordless-auth'),
                    'error'
                );
                return;
            }
            
            $log_id = intval($_POST['chrmrtns_delete_log_id']);
            if ($log_id > 0) {
                wp_delete_post($log_id, true);
                add_settings_error(
                    'chrmrtns_mail_logs_settings',
                    'chrmrtns_single_log_deleted',
                    __('Mail log deleted successfully.', 'passwordless-auth'),
                    'updated'
                );
            }
        }
    }
    
    /**
     * Log mail events
     */
    public function log_mail_event($mail_data) {
        if (get_option('chrmrtns_mail_logging_enabled') !== '1') {
            return $mail_data;
        }

        // Extract mail parameters from the array
        $to = isset($mail_data['to']) ? $mail_data['to'] : '';
        $subject = isset($mail_data['subject']) ? $mail_data['subject'] : '';
        $message = isset($mail_data['message']) ? $mail_data['message'] : '';
        $headers = isset($mail_data['headers']) ? $mail_data['headers'] : '';
        $attachments = isset($mail_data['attachments']) ? $mail_data['attachments'] : array();

        // Extract From header if present
        $from = get_option('admin_email'); // Default fallback
        if (is_array($headers)) {
            foreach ($headers as $header) {
                if (stripos($header, 'From:') === 0) {
                    $from = trim(preg_replace('/From:\s*/i', '', $header));
                    break;
                }
            }
        } else {
            if (stripos($headers, 'From:') === 0) {
                $from = trim(preg_replace('/From:\s*/i', '', $headers));
            }
        }

        // Clean up old logs if needed
        $size_limit = get_option('chrmrtns_mail_log_size_limit', 100);
        $this->cleanup_old_mail_logs($size_limit);

        // Insert the log as a custom post
        $post_data = array(
            'post_type'   => 'chrmrtns_mail_logs',
            'post_status' => 'publish',
            'post_title'  => __('Mail Log', 'passwordless-auth') . ' - ' . gmdate('Y-m-d H:i:s')
        );
        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            update_post_meta($post_id, 'date_time', gmdate('Y-m-d H:i:s'));
            update_post_meta($post_id, 'from', $from);
            update_post_meta($post_id, 'to', $to);
            update_post_meta($post_id, 'subject', $subject);
            update_post_meta($post_id, 'message', $message);
            update_post_meta($post_id, 'headers', $headers);
            update_post_meta($post_id, 'attachments', $attachments);
        }

        return $mail_data;
    }
    
    /**
     * Cleanup old mail logs
     */
    public function cleanup_old_mail_logs($limit) {
        $args = array(
            'post_type'      => 'chrmrtns_mail_logs',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'any'
        );
        
        $logs = get_posts($args);
        
        if (count($logs) > $limit) {
            $logs_to_delete = array_slice($logs, $limit);
            foreach ($logs_to_delete as $log) {
                wp_delete_post($log->ID, true);
            }
        }
    }
    
    /**
     * Render mail logs page
     */
    public function render_mail_logs_page() {
        $logging_enabled = get_option('chrmrtns_mail_logging_enabled') === '1';
        $log_size_limit  = get_option('chrmrtns_mail_log_size_limit', 100);

        // Display any settings errors
        settings_errors('chrmrtns_mail_logs_settings');

        ?>
        <div class="wrap chrmrtns-wrap">
            <h1><?php esc_html_e('Mail Logs', 'passwordless-auth'); ?></h1>
            <p><?php esc_html_e('Track and monitor all emails sent from your WordPress site. Enable logging to see detailed information about sent emails including recipients, subjects, and content.', 'passwordless-auth'); ?></p>

            <!-- Settings Form -->
            <form method="post" action="">
                <?php wp_nonce_field('chrmrtns_mail_logs_settings', 'chrmrtns_mail_logs_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Mail Logging', 'passwordless-auth'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_mail_logging_enabled" <?php checked($logging_enabled, true); ?> value="1">
                                <?php esc_html_e('Enable mail logging for all emails sent from WordPress', 'passwordless-auth'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, all emails sent via wp_mail() will be logged and stored.', 'passwordless-auth'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Log Storage Limit', 'passwordless-auth'); ?></th>
                        <td>
                            <input type="number" name="chrmrtns_mail_log_size_limit" value="<?php echo esc_attr($log_size_limit); ?>" min="1" max="1000" style="width: 100px;">
                            <p class="description"><?php esc_html_e('Maximum number of mail logs to keep. Older logs will be automatically deleted when this limit is reached.', 'passwordless-auth'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'passwordless-auth'), 'primary', 'chrmrtns_mail_logging_submit'); ?>
            </form>

            <?php if ($logging_enabled): ?>
                <hr>
                
                <!-- Clear All Logs Button -->
                <div class="tablenav top">
                    <form method="post" action="" style="float: left;" onsubmit="return confirm('<?php esc_attresc_html_e('Are you sure you want to delete all mail logs? This action cannot be undone.', 'passwordless-auth'); ?>');">
                        <?php wp_nonce_field('chrmrtns_clear_mail_logs', 'chrmrtns_clear_logs_nonce'); ?>
                        <?php submit_button(__('Clear All Logs', 'passwordless-auth'), 'delete', 'chrmrtns_clear_mail_logs', false); ?>
                    </form>
                    <div class="clear"></div>
                </div>

                <?php
                // Get and display logs
                $args = array(
                    'post_type'      => 'chrmrtns_mail_logs',
                    'posts_per_page' => 100,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'post_status'    => 'any'
                );
                $logs = get_posts($args);

                if (empty($logs)): ?>
                    <p><?php esc_html_e('No mail logs found. Emails will appear here once they are sent.', 'passwordless-auth'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Date & Time', 'passwordless-auth'); ?></th>
                                <th><?php esc_html_e('From', 'passwordless-auth'); ?></th>
                                <th><?php esc_html_e('To', 'passwordless-auth'); ?></th>
                                <th><?php esc_html_e('Subject', 'passwordless-auth'); ?></th>
                                <th><?php esc_html_e('Actions', 'passwordless-auth'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $meta = get_post_meta($log->ID);
                                $date_time = $meta['date_time'][0] ?? '';
                                $from = $meta['from'][0] ?? '';
                                $to = $meta['to'][0] ?? '';
                                $subject = $meta['subject'][0] ?? '';
                                $message = $meta['message'][0] ?? '';
                                ?>
                                <tr>
                                    <td><?php echo esc_html($date_time); ?></td>
                                    <td><?php echo esc_html($from); ?></td>
                                    <td><?php echo esc_html($to); ?></td>
                                    <td><?php echo esc_html($subject); ?></td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="chrmrtnsShowEmailContent(<?php echo esc_attr($log->ID); ?>)"><?php esc_html_e('View Content', 'passwordless-auth'); ?></button>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_attresc_html_e('Are you sure you want to delete this log?', 'passwordless-auth'); ?>');">
                                            <?php wp_nonce_field('chrmrtns_delete_mail_log', 'chrmrtns_delete_log_nonce'); ?>
                                            <input type="hidden" name="chrmrtns_delete_log_id" value="<?php echo esc_attr($log->ID); ?>">
                                            <?php submit_button(__('Delete', 'passwordless-auth'), 'delete button-small', 'chrmrtns_delete_log', false); ?>
                                        </form>
                                        
                                        <div id="chrmrtns_email_content_<?php echo esc_attr($log->ID); ?>" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                                            <h4><?php esc_html_e('Email Content:', 'passwordless-auth'); ?></h4>
                                            <div style="max-height: 300px; overflow-y: auto; background: white; padding: 10px; border: 1px solid #ccc;">
                                                <?php echo wp_kses_post($message); ?>
                                            </div>
                                            <button type="button" class="button button-small" onclick="chrmrtnsHideEmailContent(<?php echo esc_attr($log->ID); ?>)" style="margin-top: 10px;"><?php esc_html_e('Hide Content', 'passwordless-auth'); ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <p><em><?php esc_html_e('Mail logging is currently disabled. Enable it above to start tracking emails.', 'passwordless-auth'); ?></em></p>
            <?php endif; ?>
        </div>

        <style>
        #chrmrtns_clear_mail_logs {
            background: #dc3232;
            border-color: #dc3232;
            color: white;
        }
        #chrmrtns_clear_mail_logs:hover {
            background: #b32d2e;
            border-color: #b32d2e;
        }
        </style>

        <script>
        function chrmrtnsShowEmailContent(logId) {
            document.getElementById('chrmrtns_email_content_' + logId).style.display = 'block';
        }
        
        function chrmrtnsHideEmailContent(logId) {
            document.getElementById('chrmrtns_email_content_' + logId).style.display = 'none';
        }
        </script>
        <?php
    }
}