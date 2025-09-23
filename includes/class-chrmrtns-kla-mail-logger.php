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

class Chrmrtns_KLA_Mail_Logger {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register post type immediately if we're past the init hook, otherwise hook it
        if (did_action('init')) {
            $this->register_mail_logs_post_type();
        } else {
            add_action('init', array($this, 'register_mail_logs_post_type'), 1);
        }
        
        add_action('admin_init', array($this, 'handle_mail_logs_actions'));
        add_filter('wp_mail', array($this, 'log_mail_event'), 10, 1);
    }
    
    /**
     * Register custom post type for mail logs
     */
    public function register_mail_logs_post_type() {
        register_post_type('chrmrtns_kla_logs', array(
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
        if (isset($_POST['chrmrtns_kla_mail_logging_submit'])) {
            if (!isset($_POST['chrmrtns_kla_mail_logs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_mail_logs_nonce'])), 'chrmrtns_kla_mail_logs_settings')) {
                add_settings_error(
                    'chrmrtns_kla_mail_logs_settings',
                    'chrmrtns_kla_mail_logs_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'keyless-auth'),
                    'error'
                );
                return;
            }
            
            if (isset($_POST['chrmrtns_kla_mail_logging_enabled'])) {
                update_option('chrmrtns_kla_mail_logging_enabled', '1');
            } else {
                update_option('chrmrtns_kla_mail_logging_enabled', '0');
            }
            
            // Show success message
            add_settings_error(
                'chrmrtns_kla_mail_logs_settings',
                'chrmrtns_kla_mail_logs_saved',
                __('Mail logging settings saved successfully!', 'keyless-auth'),
                'updated'
            );
        }

        // Handle mail log size limit
        if (isset($_POST['chrmrtns_kla_mail_log_size_limit'])) {
            $size_limit = intval($_POST['chrmrtns_kla_mail_log_size_limit']);
            if ($size_limit < 1) {
                $size_limit = 100;
            }
            update_option('chrmrtns_kla_mail_log_size_limit', $size_limit);
        }

        // Handle clearing all mail logs
        if (isset($_POST['chrmrtns_kla_clear_mail_logs'])) {
            if (!isset($_POST['chrmrtns_kla_clear_logs_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_clear_logs_nonce'])), 'chrmrtns_kla_clear_mail_logs')) {
                add_settings_error(
                    'chrmrtns_kla_mail_logs_settings',
                    'chrmrtns_kla_clear_logs_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'keyless-auth'),
                    'error'
                );
                return;
            }

            $deleted_count = 0;

            if (class_exists('Chrmrtns_KLA_Database')) {
                // Clear from database
                global $wpdb;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for bulk log cleanup, no caching needed
                $deleted_count = $wpdb->query("DELETE FROM {$wpdb->prefix}kla_mail_logs"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            } else {
                // Clear from legacy posts
                $args = array(
                    'post_type'      => 'chrmrtns_kla_logs',
                    'posts_per_page' => -1,
                    'post_status'    => 'any'
                );
                $logs = get_posts($args);
                foreach ($logs as $log) {
                    wp_delete_post($log->ID, true);
                }
                $deleted_count = count($logs);
            }

            add_settings_error(
                'chrmrtns_kla_mail_logs_settings',
                'chrmrtns_kla_mail_logs_cleared',
                sprintf(
                    /* translators: %d: number of deleted mail logs */
                    __('Successfully deleted %d mail logs.', 'keyless-auth'),
                    $deleted_count
                ),
                'updated'
            );
        }

        // Handle bulk delete action
        if (isset($_POST['chrmrtns_kla_bulk_action']) && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
            if (!isset($_POST['chrmrtns_kla_bulk_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_bulk_delete_nonce'])), 'chrmrtns_kla_bulk_delete_mail_logs')) {
                add_settings_error(
                    'chrmrtns_kla_mail_logs_settings',
                    'chrmrtns_kla_bulk_delete_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'keyless-auth'),
                    'error'
                );
                return;
            }

            if (isset($_POST['log_ids']) && is_array($_POST['log_ids'])) {
                $deleted_count = 0;
                $log_ids = array_map('sanitize_text_field', wp_unslash($_POST['log_ids']));

                if (class_exists('Chrmrtns_KLA_Database')) {
                    // Delete from database
                    global $wpdb;
                    foreach ($log_ids as $log_id) {
                        $log_id = intval($log_id);
                        if ($log_id > 0) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for bulk log deletion, no caching needed
                            $result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                                $wpdb->prefix . 'kla_mail_logs',
                                array('id' => $log_id),
                                array('%d')
                            );
                            if ($result) {
                                $deleted_count++;
                            }
                        }
                    }
                } else {
                    // Delete legacy posts
                    foreach ($log_ids as $log_id) {
                        $log_id = intval($log_id);
                        if ($log_id > 0) {
                            wp_delete_post($log_id, true);
                            $deleted_count++;
                        }
                    }
                }

                if ($deleted_count > 0) {
                    add_settings_error(
                        'chrmrtns_kla_mail_logs_settings',
                        'chrmrtns_bulk_logs_deleted',
                        sprintf(
                            /* translators: %d: number of deleted mail logs */
                            __('Successfully deleted %d selected mail log(s).', 'keyless-auth'),
                            $deleted_count
                        ),
                        'updated'
                    );
                }
            }
        }

        // Handle deleting a single mail log
        if (isset($_POST['chrmrtns_kla_delete_log']) && isset($_POST['chrmrtns_kla_delete_log_id'])) {
            if (!isset($_POST['chrmrtns_kla_delete_log_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_delete_log_nonce'])), 'chrmrtns_kla_delete_mail_log')) {
                add_settings_error(
                    'chrmrtns_kla_mail_logs_settings',
                    'chrmrtns_kla_delete_log_nonce_failed',
                    __('Security check failed. Please refresh the page and try again.', 'keyless-auth'),
                    'error'
                );
                return;
            }
            
            $log_id = intval($_POST['chrmrtns_kla_delete_log_id']);
            if ($log_id > 0) {
                $deleted = false;

                if (class_exists('Chrmrtns_KLA_Database')) {
                    // Delete from database
                    global $wpdb;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for single log deletion, no caching needed
                    $deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->prefix . 'kla_mail_logs',
                        array('id' => $log_id),
                        array('%d')
                    );
                } else {
                    // Delete legacy post
                    $deleted = wp_delete_post($log_id, true);
                }

                if ($deleted) {
                    add_settings_error(
                        'chrmrtns_kla_mail_logs_settings',
                        'chrmrtns_single_log_deleted',
                        __('Mail log deleted successfully.', 'keyless-auth'),
                        'updated'
                    );
                }
            }
        }
    }
    
    /**
     * Log mail events
     * Note: This function logs email data for debugging and monitoring purposes.
     * Logged data may contain personal information and should be handled according to privacy laws.
     */
    public function log_mail_event($mail_data) {
        if (get_option('chrmrtns_kla_mail_logging_enabled') !== '1') {
            return $mail_data;
        }

        // Privacy check - allow users to disable logging via filter
        if (!apply_filters('chrmrtns_kla_allow_mail_logging', true)) {
            return $mail_data;
        }

        // Extract and sanitize mail parameters from the array
        $to = isset($mail_data['to']) ? sanitize_email($mail_data['to']) : '';
        $subject = isset($mail_data['subject']) ? sanitize_text_field($mail_data['subject']) : '';
        $message = isset($mail_data['message']) ? wp_kses_post($mail_data['message']) : '';
        $headers = isset($mail_data['headers']) ? $mail_data['headers'] : '';
        $attachments = isset($mail_data['attachments']) ? $mail_data['attachments'] : array();

        // Validate email addresses
        if (!empty($to) && !is_email($to)) {
            // If not a valid email, sanitize as text but mark as invalid
            $to = sanitize_text_field($mail_data['to'] ?? '') . ' (invalid)';
        }

        // Extract From header if present
        $from = sanitize_email(get_option('admin_email')); // Default fallback
        if (is_array($headers)) {
            foreach ($headers as $header) {
                if (is_string($header) && stripos($header, 'From:') === 0) {
                    $extracted_from = trim(preg_replace('/From:\s*/i', '', $header));
                    // Extract email from "Name <email>" format
                    if (preg_match('/<(.+?)>/', $extracted_from, $matches)) {
                        $extracted_from = $matches[1];
                    }
                    if (is_email($extracted_from)) {
                        $from = sanitize_email($extracted_from);
                    } else {
                        $from = sanitize_text_field($extracted_from);
                    }
                    break;
                }
            }
        } elseif (is_string($headers) && stripos($headers, 'From:') === 0) {
            $extracted_from = trim(preg_replace('/From:\s*/i', '', $headers));
            if (preg_match('/<(.+?)>/', $extracted_from, $matches)) {
                $extracted_from = $matches[1];
            }
            if (is_email($extracted_from)) {
                $from = sanitize_email($extracted_from);
            } else {
                $from = sanitize_text_field($extracted_from);
            }
        }

        // Get user ID if available
        $user_id = null;
        if (is_email($to)) {
            $user = get_user_by('email', $to);
            if ($user) {
                $user_id = $user->ID;
            }
        }

        // Use new database system if available
        if (class_exists('Chrmrtns_KLA_Database')) {
            $database = new Chrmrtns_KLA_Database();
            $database->log_email($user_id, $to, $subject, $message, 'sent', null, 'default');

            // Clean up old logs
            $size_limit = get_option('chrmrtns_kla_mail_log_size_limit', 100);
            // TODO: Implement cleanup_old_logs method in database class
        } else {
            // Fallback to legacy post system
            $this->log_mail_event_legacy($mail_data, $to, $subject, $message, $headers, $attachments, $from);
        }

        return $mail_data;
    }

    /**
     * Legacy mail logging using custom post type (for backwards compatibility)
     */
    private function log_mail_event_legacy($mail_data, $to, $subject, $message, $headers, $attachments, $from) {
        // Clean up old logs if needed
        $size_limit = get_option('chrmrtns_kla_mail_log_size_limit', 100);
        $this->cleanup_old_mail_logs($size_limit);

        // Insert the log as a custom post
        $post_data = array(
            'post_type'   => 'chrmrtns_kla_logs',
            'post_status' => 'publish',
            'post_title'  => __('Mail Log', 'keyless-auth') . ' - ' . gmdate('Y-m-d H:i:s')
        );
        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Store sanitized data with proper validation
            update_post_meta($post_id, 'date_time', sanitize_text_field(gmdate('Y-m-d H:i:s')));
            update_post_meta($post_id, 'from', sanitize_text_field($from));
            update_post_meta($post_id, 'to', sanitize_text_field($to));
            update_post_meta($post_id, 'subject', sanitize_text_field($subject));
            update_post_meta($post_id, 'message', wp_kses_post($message));

            // Sanitize headers - can be array or string
            if (is_array($headers)) {
                $sanitized_headers = array_map('sanitize_text_field', $headers);
                update_post_meta($post_id, 'headers', $sanitized_headers);
            } else {
                update_post_meta($post_id, 'headers', sanitize_textarea_field($headers));
            }

            // Sanitize attachments - should be array of file paths
            if (is_array($attachments)) {
                $sanitized_attachments = array_map('sanitize_text_field', $attachments);
                update_post_meta($post_id, 'attachments', $sanitized_attachments);
            } else {
                update_post_meta($post_id, 'attachments', array());
            }
        }
    }
    
    /**
     * Cleanup old mail logs
     */
    public function cleanup_old_mail_logs($limit) {
        $args = array(
            'post_type'      => 'chrmrtns_kla_logs',
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
     * Get mail logs from either database or legacy system
     */
    private function get_mail_logs($limit = 100) {
        if (class_exists('Chrmrtns_KLA_Database')) {
            // Use new database system
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for retrieving mail logs, no caching needed for transactional data
            $results = $wpdb->get_results($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                "SELECT id, user_id, recipient_email as recipient, subject, email_body as message, sent_time, status, 'N/A' as from_email
                FROM {$wpdb->prefix}kla_mail_logs
                ORDER BY sent_time DESC
                LIMIT %d",
                $limit
            ), ARRAY_A);

            // Format for consistent display
            $logs = array();
            foreach ($results as $result) {
                $logs[] = array(
                    'id' => $result['id'],
                    'date_time' => $result['sent_time'],
                    'from' => $result['from_email'],
                    'to' => $result['recipient'],
                    'subject' => $result['subject'],
                    'message' => $result['message'],
                    'status' => $result['status']
                );
            }
            return $logs;
        } else {
            // Use legacy post system
            $args = array(
                'post_type'      => 'chrmrtns_kla_logs',
                'posts_per_page' => $limit,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'post_status'    => 'any'
            );
            $posts = get_posts($args);

            $logs = array();
            foreach ($posts as $post) {
                $meta = get_post_meta($post->ID);
                $logs[] = array(
                    'id' => $post->ID,
                    'date_time' => $meta['date_time'][0] ?? '',
                    'from' => $meta['from'][0] ?? '',
                    'to' => $meta['to'][0] ?? '',
                    'subject' => $meta['subject'][0] ?? '',
                    'message' => $meta['message'][0] ?? '',
                    'status' => 'sent' // Legacy system doesn't track status
                );
            }
            return $logs;
        }
    }

    /**
     * Render mail logs page
     */
    public function render_mail_logs_page() {
        // Additional security check
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        $logging_enabled = get_option('chrmrtns_kla_mail_logging_enabled') === '1';
        $log_size_limit  = get_option('chrmrtns_kla_mail_log_size_limit', 100);

        // Display any settings errors
        settings_errors('chrmrtns_kla_mail_logs_settings');

        ?>
        <div class="wrap chrmrtns-wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('Mail Logs', 'keyless-auth'); ?>
            </h1>
            <p><?php esc_html_e('Track and monitor all emails sent from your WordPress site. Enable logging to see detailed information about sent emails including recipients, subjects, and content.', 'keyless-auth'); ?></p>
            
            <div class="notice notice-info">
                <p><strong><?php esc_html_e('Privacy Notice:', 'keyless-auth'); ?></strong> 
                <?php esc_html_e('Mail logs may contain personal information including email addresses and message content. Ensure compliance with applicable privacy laws (GDPR, CCPA, etc.) when enabling this feature. Logs are automatically cleaned up based on your retention settings.', 'keyless-auth'); ?></p>
            </div>

            <!-- Settings Form -->
            <form method="post" action="">
                <?php wp_nonce_field('chrmrtns_kla_mail_logs_settings', 'chrmrtns_kla_mail_logs_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Mail Logging', 'keyless-auth'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="chrmrtns_kla_mail_logging_enabled" <?php checked($logging_enabled, true); ?> value="1">
                                <?php esc_html_e('Enable mail logging for all emails sent from WordPress', 'keyless-auth'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, all emails sent via wp_mail() will be logged and stored.', 'keyless-auth'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Log Storage Limit', 'keyless-auth'); ?></th>
                        <td>
                            <input type="number" name="chrmrtns_kla_mail_log_size_limit" value="<?php echo esc_attr($log_size_limit); ?>" min="1" max="1000" style="width: 100px;">
                            <p class="description"><?php esc_html_e('Maximum number of mail logs to keep. Older logs will be automatically deleted when this limit is reached.', 'keyless-auth'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'keyless-auth'), 'primary', 'chrmrtns_kla_mail_logging_submit'); ?>
            </form>

            <?php if ($logging_enabled): ?>
                <hr>
                
                <!-- Diagnostic Info -->
                <div style="background: #f1f1f1; padding: 10px; margin: 10px 0; border-left: 4px solid #0073aa;">
                    <strong><?php esc_html_e('Diagnostic Information:', 'keyless-auth'); ?></strong><br>
                    <?php 
                    echo 'Mail logging enabled: ' . (get_option('chrmrtns_kla_mail_logging_enabled') === '1' ? 'Yes' : 'No') . '<br>';
                    echo 'Post type registered: ' . (post_type_exists('chrmrtns_kla_logs') ? 'Yes' : 'No') . '<br>';
                    
                    // Count total logs
                    if (class_exists('Chrmrtns_KLA_Database')) {
                        global $wpdb;
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query for diagnostics, no caching needed
                        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kla_mail_logs"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                        echo 'Total mail logs in database: ' . esc_html($total_logs) . '<br>';
                        echo 'Storage system: Custom database tables<br>';
                    } else {
                        $total_logs = wp_count_posts('chrmrtns_kla_logs');
                        echo 'Total mail logs in database: ' . esc_html($total_logs->publish ?? 0) . '<br>';
                        echo 'Storage system: WordPress custom post type (legacy)<br>';
                    }
                    ?>
                </div>
                
                <!-- Bulk Actions -->
                <form method="post" action="" id="chrmrtns-mail-logs-form">
                    <?php wp_nonce_field('chrmrtns_kla_bulk_delete_mail_logs', 'chrmrtns_kla_bulk_delete_nonce'); ?>

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <select name="bulk_action" id="bulk-action-selector">
                                <option value=""><?php esc_html_e('Bulk Actions', 'keyless-auth'); ?></option>
                                <option value="delete"><?php esc_html_e('Delete', 'keyless-auth'); ?></option>
                            </select>
                            <?php submit_button(__('Apply', 'keyless-auth'), 'action', 'chrmrtns_kla_bulk_action', false); ?>
                        </div>

                        <div class="alignleft actions">
                            <button type="submit" name="chrmrtns_kla_clear_mail_logs" class="button delete"
                                onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete all mail logs? This action cannot be undone.', 'keyless-auth')); ?>');">
                                <?php esc_html_e('Clear All Logs', 'keyless-auth'); ?>
                            </button>
                        </div>
                        <div class="clear"></div>
                    </div>

                <?php
                // Get and display logs
                $logs = $this->get_mail_logs();

                if (empty($logs)): ?>
                    <p><?php esc_html_e('No mail logs found. Emails will appear here once they are sent.', 'keyless-auth'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="chrmrtns-select-all-logs" />
                                </td>
                                <th><?php esc_html_e('Date & Time', 'keyless-auth'); ?></th>
                                <th><?php esc_html_e('From', 'keyless-auth'); ?></th>
                                <th><?php esc_html_e('To', 'keyless-auth'); ?></th>
                                <th><?php esc_html_e('Subject', 'keyless-auth'); ?></th>
                                <th><?php esc_html_e('Status', 'keyless-auth'); ?></th>
                                <th><?php esc_html_e('Actions', 'keyless-auth'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log['id']); ?>" class="chrmrtns-log-checkbox" />
                                    </th>
                                    <td><?php echo esc_html($log['date_time']); ?></td>
                                    <td><?php echo esc_html($log['from'] ?? 'N/A'); ?></td>
                                    <td><?php echo esc_html($log['to']); ?></td>
                                    <td><?php echo esc_html($log['subject']); ?></td>
                                    <td>
                                        <span class="status-<?php echo esc_attr($log['status'] ?? 'sent'); ?>">
                                            <?php echo esc_html(ucfirst($log['status'] ?? 'sent')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="chrmrtnsShowEmailContent(<?php echo esc_attr($log['id']); ?>)"><?php esc_html_e('View Content', 'keyless-auth'); ?></button>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this log?', 'keyless-auth')); ?>');">
                                            <?php wp_nonce_field('chrmrtns_kla_delete_mail_log', 'chrmrtns_kla_delete_log_nonce'); ?>
                                            <input type="hidden" name="chrmrtns_kla_delete_log_id" value="<?php echo esc_attr($log['id']); ?>">
                                            <?php submit_button(__('Delete', 'keyless-auth'), 'delete button-small', 'chrmrtns_kla_delete_log', false); ?>
                                        </form>

                                        <div id="chrmrtns_email_content_<?php echo esc_attr($log['id']); ?>" style="display: none; position: absolute; left: 50%; transform: translateX(-50%); width: 90%; max-width: 800px; margin-top: 10px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000;">
                                            <h4 style="margin-top: 0;"><?php esc_html_e('Email Content:', 'keyless-auth'); ?></h4>
                                            <div style="max-height: 400px; overflow-y: auto; background: white; padding: 15px; border: 1px solid #ccc; border-radius: 3px;">
                                                <?php echo wp_kses_post($log['message']); ?>
                                            </div>
                                            <div style="text-align: center; margin-top: 10px;">
                                                <button type="button" class="button button-small" onclick="chrmrtnsHideEmailContent(<?php echo esc_attr($log['id']); ?>)"><?php esc_html_e('Hide Content', 'keyless-auth'); ?></button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form> <!-- End of bulk action form -->

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Select all checkbox functionality
                    $('#chrmrtns-select-all-logs').on('change', function() {
                        $('.chrmrtns-log-checkbox').prop('checked', $(this).prop('checked'));
                    });

                    // Update select all checkbox based on individual checkboxes
                    $('.chrmrtns-log-checkbox').on('change', function() {
                        var allChecked = $('.chrmrtns-log-checkbox:checked').length === $('.chrmrtns-log-checkbox').length;
                        $('#chrmrtns-select-all-logs').prop('checked', allChecked);
                    });

                    // Confirm bulk delete
                    $('#chrmrtns-mail-logs-form').on('submit', function(e) {
                        if ($('select[name="bulk_action"]').val() === 'delete' &&
                            $('input[name="chrmrtns_kla_bulk_action"]').is(':focus')) {
                            var checkedCount = $('.chrmrtns-log-checkbox:checked').length;
                            if (checkedCount === 0) {
                                alert('<?php echo esc_js(__('Please select at least one log to delete.', 'keyless-auth')); ?>');
                                return false;
                            }
                            return confirm('<?php echo esc_js(__('Are you sure you want to delete the selected logs? This action cannot be undone.', 'keyless-auth')); ?>');
                        }
                    });

                    // Email content show/hide functions (redefine here to ensure availability)
                    window.chrmrtnsShowEmailContent = function(logId) {
                        var contentDiv = document.getElementById('chrmrtns_email_content_' + logId);
                        if (contentDiv) {
                            contentDiv.style.display = 'block';
                        }
                    };

                    window.chrmrtnsHideEmailContent = function(logId) {
                        var contentDiv = document.getElementById('chrmrtns_email_content_' + logId);
                        if (contentDiv) {
                            contentDiv.style.display = 'none';
                        }
                    };
                });
                </script>
                <?php endif; ?>
            <?php else: ?>
                <p><em><?php esc_html_e('Mail logging is currently disabled. Enable it above to start tracking emails.', 'keyless-auth'); ?></em></p>
            <?php endif; ?>
        </div>
        <?php
    }
}