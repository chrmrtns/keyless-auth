<?php
/**
 * Database management for Keyless Auth
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
// Direct database queries are necessary for custom table management, statistics, and cleanup operations.
// Caching is not appropriate for transactional data like login attempts and email logs.
// Schema changes are required for table creation, indexing, and maintenance operations.

class Database {

    /**
     * Database version
     */
    const DB_VERSION = '1.2.0';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'check_database_version'));
    }

    /**
     * Check database version and update if needed
     */
    public function check_database_version() {
        $installed_version = get_option('chrmrtns_kla_db_version');

        if ($installed_version !== self::DB_VERSION) {
            // Migrate old tables if upgrading from pre-1.2.0
            if ($installed_version && version_compare($installed_version, '1.2.0', '<')) {
                $this->migrate_old_tables();
            }

            $this->create_tables();
            update_option('chrmrtns_kla_db_version', self::DB_VERSION);
        }
    }

    /**
     * Migrate old kla_* tables to chrmrtns_kla_* tables
     * This ensures backward compatibility when upgrading from version < 1.2.0
     *
     * @since 1.2.0
     */
    private function migrate_old_tables() {
        global $wpdb;

        $old_new_table_map = array(
            'kla_login_logs' => 'chrmrtns_kla_login_logs',
            'kla_mail_logs' => 'chrmrtns_kla_mail_logs',
            'kla_user_devices' => 'chrmrtns_kla_user_devices',
            'kla_login_tokens' => 'chrmrtns_kla_login_tokens',
            'kla_webhook_logs' => 'chrmrtns_kla_webhook_logs'
        );

        foreach ($old_new_table_map as $old_suffix => $new_suffix) {
            $old_table = $wpdb->prefix . $old_suffix;
            $new_table = $wpdb->prefix . $new_suffix;

            // Check if old table exists
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration check for table existence
            $old_table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $old_table)) === $old_table;

            if ($old_table_exists) {
                // Check if new table already exists
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration check for table existence
                $new_table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $new_table)) === $new_table;

                if (!$new_table_exists) {
                    // Rename old table to new table name (faster than copy + delete)
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Renaming table during migration, table names are safely constructed
                    $wpdb->query("RENAME TABLE `{$old_table}` TO `{$new_table}`");
                } else {
                    // If both exist, copy data from old to new, then drop old
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Migration data copy, table names are safely constructed
                    $wpdb->query("INSERT IGNORE INTO `{$new_table}` SELECT * FROM `{$old_table}`");
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dropping old table after migration, table name is safely constructed
                    $wpdb->query("DROP TABLE IF EXISTS `{$old_table}`");
                }
            }
        }
    }

    /**
     * Create all plugin tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Login audit log table
        $login_logs_table = $wpdb->prefix . 'chrmrtns_kla_login_logs';
        $login_logs_sql = "CREATE TABLE $login_logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            user_email varchar(100) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            login_time datetime DEFAULT CURRENT_TIMESTAMP,
            device_type varchar(50),
            location varchar(100),
            status enum('success','failed','expired','blocked') DEFAULT 'success',
            token_hash varchar(64),
            notes text,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY login_time (login_time),
            KEY status (status),
            KEY ip_address (ip_address)
        ) $charset_collate;";

        // Enhanced mail log table
        $mail_logs_table = $wpdb->prefix . 'chrmrtns_kla_mail_logs';
        $mail_logs_sql = "CREATE TABLE $mail_logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            recipient_email varchar(100) NOT NULL,
            subject varchar(200),
            email_body longtext,
            sent_time datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('sent','failed','pending') DEFAULT 'pending',
            token_hash varchar(64),
            expires_at datetime,
            smtp_response text,
            error_message text,
            template_used varchar(50),
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY recipient_email (recipient_email),
            KEY sent_time (sent_time),
            KEY expires_at (expires_at),
            KEY status (status),
            KEY token_hash (token_hash)
        ) $charset_collate;";

        // User devices table (with 2FA support)
        $devices_table = $wpdb->prefix . 'chrmrtns_kla_user_devices';
        $devices_sql = "CREATE TABLE $devices_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            device_token varchar(255),
            device_name varchar(100),
            device_type varchar(50),
            device_fingerprint varchar(255),
            registered_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used datetime,
            is_active boolean DEFAULT 1,
            trust_level enum('untrusted','trusted','verified') DEFAULT 'untrusted',
            app_version varchar(20),
            push_token varchar(500),
            totp_secret varchar(32) NULL,
            totp_enabled tinyint(1) DEFAULT 0,
            totp_backup_codes text NULL,
            totp_last_used datetime NULL,
            totp_failed_attempts int(3) DEFAULT 0,
            totp_locked_until datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_device (user_id, device_fingerprint),
            KEY user_id (user_id),
            KEY device_token (device_token),
            KEY last_used (last_used),
            KEY is_active (is_active),
            KEY totp_enabled (totp_enabled)
        ) $charset_collate;";

        // Login tokens table (replace user_meta storage)
        $tokens_table = $wpdb->prefix . 'chrmrtns_kla_login_tokens';
        $tokens_sql = "CREATE TABLE $tokens_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            token_hash varchar(64) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            used_at datetime NULL,
            ip_address varchar(45),
            user_agent text,
            device_fingerprint varchar(255),
            is_used boolean DEFAULT 0,
            attempt_count int(3) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY user_id (user_id),
            KEY expires_at (expires_at),
            KEY is_used (is_used),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Webhook logs table (for future webhook support)
        $webhooks_table = $wpdb->prefix . 'chrmrtns_kla_webhook_logs';
        $webhooks_sql = "CREATE TABLE $webhooks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            webhook_url varchar(500) NOT NULL,
            event_type varchar(50) NOT NULL,
            payload longtext,
            response_code int(3),
            response_body text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('success','failed','retry') DEFAULT 'success',
            retry_count int(2) DEFAULT 0,
            next_retry datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY sent_at (sent_at),
            KEY status (status),
            KEY next_retry (next_retry)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($login_logs_sql);
        dbDelta($mail_logs_sql);
        dbDelta($devices_sql);
        dbDelta($tokens_sql);
        dbDelta($webhooks_sql);

        // Create indexes for better performance
        $this->create_indexes();
    }

    /**
     * Create additional indexes for performance
     */
    private function create_indexes() {
        global $wpdb;

        // Composite indexes for common queries
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Creating performance indexes for custom tables
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_mail_logs_user_status ON {$wpdb->prefix}chrmrtns_kla_mail_logs (user_id, status)");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Creating performance indexes for custom tables
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_login_logs_user_time ON {$wpdb->prefix}chrmrtns_kla_login_logs (user_id, login_time)");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Creating performance indexes for custom tables
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_tokens_user_expires ON {$wpdb->prefix}chrmrtns_kla_login_tokens (user_id, expires_at)");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Creating performance indexes for custom tables
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_devices_user_active ON {$wpdb->prefix}chrmrtns_kla_user_devices (user_id, is_active)");
    }

    /**
     * Get login logs with optional filters
     */
    public function get_login_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'user_id' => null,
            'status' => null,
            'limit' => 100,
            'offset' => 0,
            'order_by' => 'login_time',
            'order' => 'DESC',
            'date_from' => null,
            'date_to' => null
        );

        $args = wp_parse_args($args, $defaults);

        $where_conditions = array('1=1');
        $where_values = array();

        if ($args['user_id']) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ($args['status']) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        if ($args['date_from']) {
            $where_conditions[] = 'login_time >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where_conditions[] = 'login_time <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where_conditions);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);

        $where_values[] = (int) $args['limit'];
        $where_values[] = (int) $args['offset'];

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where_clause and $order_by are properly sanitized above
        $sql = "SELECT * FROM {$wpdb->prefix}chrmrtns_kla_login_logs
                WHERE $where_clause
                ORDER BY $order_by
                LIMIT %d OFFSET %d";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql contains sanitized dynamic content, $where_values properly prepared
        return $wpdb->get_results($wpdb->prepare($sql, $where_values)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Log a login attempt
     */
    public function log_login_attempt($user_id, $user_email, $status, $token_hash = null, $notes = null) {
        global $wpdb;

        $ip_address = $this->get_client_ip();
        $user_agent = $this->get_user_agent();
        $device_type = $this->detect_device_type($user_agent);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Logging to custom login audit table
        return $wpdb->insert(
            $wpdb->prefix . 'chrmrtns_kla_login_logs',
            array(
                'user_id' => $user_id,
                'user_email' => $user_email,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'device_type' => $device_type,
                'status' => $status,
                'token_hash' => $token_hash,
                'notes' => $notes,
                'login_time' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Log email sending
     */
    public function log_email($user_id, $recipient_email, $subject, $email_body, $status, $token_hash = null, $template_used = null) {
        global $wpdb;

        $ip_address = $this->get_client_ip();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Logging to custom mail logs table
        $wpdb->insert(
            $wpdb->prefix . 'chrmrtns_kla_mail_logs',
            array(
                'user_id' => $user_id,
                'recipient_email' => $recipient_email,
                'subject' => $subject,
                'email_body' => $email_body,
                'status' => $status,
                'token_hash' => $token_hash,
                'template_used' => $template_used,
                'ip_address' => $ip_address,
                'sent_time' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // Return the inserted ID, not the result
        return $wpdb->insert_id;
    }

    /**
     * Store login token
     */
    public function store_login_token($user_id, $token_hash, $expires_at) {
        global $wpdb;

        // Clean up old tokens for this user first
        $this->cleanup_expired_tokens($user_id);

        $ip_address = $this->get_client_ip();
        $user_agent = $this->get_user_agent();
        $device_fingerprint = $this->generate_device_fingerprint();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Storing tokens in custom table
        return $wpdb->insert(
            $wpdb->prefix . 'chrmrtns_kla_login_tokens',
            array(
                'user_id' => $user_id,
                'token_hash' => $token_hash,
                'expires_at' => gmdate('Y-m-d H:i:s', $expires_at),
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'device_fingerprint' => $device_fingerprint,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Validate login token
     */
    public function validate_login_token($user_id, $token_hash) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is hardcoded with proper placeholders, no dynamic content
        $sql = "SELECT * FROM {$wpdb->prefix}chrmrtns_kla_login_tokens
                WHERE user_id = %d
                AND token_hash = %s
                AND expires_at > UTC_TIMESTAMP()
                AND is_used = 0
                LIMIT 1";

        $token = $wpdb->get_row($wpdb->prepare($sql, $user_id, $token_hash)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

        if ($token) {
            // Mark token as used
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating token usage in custom table
            $wpdb->update(
                $wpdb->prefix . 'chrmrtns_kla_login_tokens',
                array(
                    'is_used' => 1,
                    'used_at' => current_time('mysql')
                ),
                array('id' => $token->id),
                array('%d', '%s'),
                array('%d')
            );

            return true;
        }

        // Increment attempt count for failed attempts
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}chrmrtns_kla_login_tokens
             SET attempt_count = attempt_count + 1
             WHERE user_id = %d AND token_hash = %s",
            $user_id, $token_hash
        ));

        return false;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens($user_id = null) {
        global $wpdb;

        $where_clause = "expires_at < UTC_TIMESTAMP()";
        $where_values = array();

        if ($user_id) {
            $where_clause .= " AND user_id = %d";
            $where_values[] = $user_id;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where_clause is safely constructed above
        $sql = "DELETE FROM {$wpdb->prefix}chrmrtns_kla_login_tokens WHERE $where_clause"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        if (!empty($where_values)) {
            return $wpdb->query($wpdb->prepare($sql, $where_values)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        } else {
            return $wpdb->query($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }
    }

    /**
     * Get statistics
     */
    public function get_statistics() {
        global $wpdb;

        $stats = array();

        // Total logins
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['total_logins'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chrmrtns_kla_login_logs WHERE status = 'success'");

        // Logins this month
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['logins_this_month'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}chrmrtns_kla_login_logs
             WHERE status = 'success'
             AND login_time >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        // Failed attempts
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['failed_attempts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chrmrtns_kla_login_logs WHERE status = 'failed'");

        // Total emails sent
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['emails_sent'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chrmrtns_kla_mail_logs WHERE status = 'sent'");

        // Active tokens
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['active_tokens'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}chrmrtns_kla_login_tokens WHERE expires_at > NOW() AND is_used = 0");

        return $stats;
    }

    /**
     * Clean up old logs (for maintenance)
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;

        $date_threshold = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Clean login logs
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance cleanup of old logs
        $login_deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}chrmrtns_kla_login_logs WHERE login_time < %s",
            $date_threshold
        ));

        // Clean mail logs
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance cleanup of old logs
        $mail_deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}chrmrtns_kla_mail_logs WHERE sent_time < %s",
            $date_threshold
        ));

        return array(
            'login_logs_deleted' => $login_deleted,
            'mail_logs_deleted' => $mail_deleted
        );
    }

    /**
     * Helper functions
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
                // Handle comma-separated IPs (forwarded)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '';
    }

    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ?
            sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
    }

    private function detect_device_type($user_agent) {
        if (empty($user_agent)) return 'unknown';

        $user_agent = strtolower($user_agent);

        if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false) {
            return 'mobile';
        } elseif (strpos($user_agent, 'tablet') !== false || strpos($user_agent, 'ipad') !== false) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    private function generate_device_fingerprint() {
        $fingerprint_data = array(
            'user_agent' => $this->get_user_agent(),
            'ip' => $this->get_client_ip(),
            // Could add more browser/device specific data here
        );

        return hash('sha256', serialize($fingerprint_data));
    }

    /**
     * 2FA Management Methods
     */

    /**
     * Enable 2FA for user with secret key
     */
    public function enable_user_2fa($user_id, $totp_secret, $backup_codes = array()) {
        global $wpdb;

        $device_fingerprint = $this->generate_device_fingerprint();

        // Check if device record exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Querying custom devices table
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}chrmrtns_kla_user_devices WHERE user_id = %d AND device_fingerprint = %s",
            $user_id, $device_fingerprint
        ));

        $backup_codes_json = !empty($backup_codes) ? wp_json_encode($backup_codes) : null;

        if ($existing) {
            // Update existing device record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating custom devices table
            return $wpdb->update(
                $wpdb->prefix . 'chrmrtns_kla_user_devices',
                array(
                    'totp_secret' => $totp_secret,
                    'totp_enabled' => 1,
                    'totp_backup_codes' => $backup_codes_json,
                    'totp_failed_attempts' => 0,
                    'totp_locked_until' => null,
                    'last_used' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%s', '%d', '%s', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new device record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Inserting into custom devices table
            return $wpdb->insert(
                $wpdb->prefix . 'chrmrtns_kla_user_devices',
                array(
                    'user_id' => $user_id,
                    'device_fingerprint' => $device_fingerprint,
                    'device_type' => $this->detect_device_type($this->get_user_agent()),
                    'totp_secret' => $totp_secret,
                    'totp_enabled' => 1,
                    'totp_backup_codes' => $backup_codes_json,
                    'registered_at' => current_time('mysql'),
                    'last_used' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Disable 2FA for user
     */
    public function disable_user_2fa($user_id) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating custom devices table
        return $wpdb->update(
            $wpdb->prefix . 'chrmrtns_kla_user_devices',
            array(
                'totp_enabled' => 0,
                'totp_secret' => null,
                'totp_backup_codes' => null,
                'totp_failed_attempts' => 0,
                'totp_locked_until' => null
            ),
            array('user_id' => $user_id),
            array('%d', '%s', '%s', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Get user 2FA settings
     */
    public function get_user_2fa_settings($user_id) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Querying custom devices table
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT totp_secret, totp_enabled, totp_backup_codes, totp_last_used, totp_failed_attempts, totp_locked_until
             FROM {$wpdb->prefix}chrmrtns_kla_user_devices
             WHERE user_id = %d AND totp_enabled = 1
             LIMIT 1",
            $user_id
        ));

        if ($result && !empty($result->totp_backup_codes)) {
            $result->totp_backup_codes = json_decode($result->totp_backup_codes, true);
        }

        return $result;
    }

    /**
     * Record 2FA attempt (success or failure)
     */
    public function record_2fa_attempt($user_id, $success = false) {
        global $wpdb;

        if ($success) {
            // Reset failed attempts on success
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating custom devices table
            return $wpdb->update(
                $wpdb->prefix . 'chrmrtns_kla_user_devices',
                array(
                    'totp_last_used' => current_time('mysql'),
                    'totp_failed_attempts' => 0,
                    'totp_locked_until' => null,
                    'last_used' => current_time('mysql')
                ),
                array('user_id' => $user_id, 'totp_enabled' => 1),
                array('%s', '%d', '%s', '%s'),
                array('%d', '%d')
            );
        } else {
            // Increment failed attempts
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating custom devices table
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}chrmrtns_kla_user_devices
                 SET totp_failed_attempts = totp_failed_attempts + 1,
                     totp_locked_until = CASE
                         WHEN totp_failed_attempts >= %d THEN DATE_ADD(NOW(), INTERVAL %d MINUTE)
                         ELSE totp_locked_until
                     END
                 WHERE user_id = %d AND totp_enabled = 1",
                get_option('chrmrtns_kla_2fa_max_attempts', 5),
                get_option('chrmrtns_kla_2fa_lockout_duration', 15),
                $user_id
            ));
        }
    }

    /**
     * Use backup code
     */
    public function use_backup_code($user_id, $code) {
        global $wpdb;

        $settings = $this->get_user_2fa_settings($user_id);

        if (!$settings || empty($settings->totp_backup_codes)) {
            return false;
        }

        $backup_codes = $settings->totp_backup_codes;

        // Check if code exists and remove it
        foreach ($backup_codes as $index => $stored_hash) {
            if (wp_check_password($code, $stored_hash)) {
                // Remove used code
                unset($backup_codes[$index]);
                $backup_codes = array_values($backup_codes); // Re-index array

                // Update database
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating custom devices table
                $wpdb->update(
                    $wpdb->prefix . 'chrmrtns_kla_user_devices',
                    array(
                        'totp_backup_codes' => wp_json_encode($backup_codes),
                        'totp_last_used' => current_time('mysql'),
                        'totp_failed_attempts' => 0,
                        'totp_locked_until' => null
                    ),
                    array('user_id' => $user_id, 'totp_enabled' => 1),
                    array('%s', '%s', '%d', '%s'),
                    array('%d', '%d')
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Get all users with 2FA enabled (for admin management)
     */
    public function get_2fa_users($search = '') {
        global $wpdb;

        $base_query = "SELECT u.ID, u.user_login, u.user_email, u.display_name, d.totp_enabled, d.totp_last_used, d.totp_failed_attempts, d.totp_locked_until
                       FROM {$wpdb->users} u
                       INNER JOIN {$wpdb->prefix}chrmrtns_kla_user_devices d ON u.ID = d.user_id AND d.totp_enabled = 1";

        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $query = $base_query . " WHERE (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s) ORDER BY u.user_login ASC";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Querying custom devices table for admin interface with search, query properly prepared with placeholders
            $prepared_query = $wpdb->prepare($query, $search_term, $search_term, $search_term);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query already prepared with placeholders above
            $results = $wpdb->get_results($prepared_query);
        } else {
            $query = $base_query . " ORDER BY u.user_login ASC";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Querying custom devices table for admin interface, no placeholders needed
            $results = $wpdb->get_results($query);
        }

        return $results;
    }

    /**
     * Drop all plugin tables (for uninstall)
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'chrmrtns_kla_login_logs',
            $wpdb->prefix . 'chrmrtns_kla_mail_logs',
            $wpdb->prefix . 'chrmrtns_kla_user_devices',
            $wpdb->prefix . 'chrmrtns_kla_login_tokens',
            $wpdb->prefix . 'chrmrtns_kla_webhook_logs'
        );

        foreach ($tables as $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- $table is safely constructed from $wpdb->prefix above
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('chrmrtns_kla_db_version');
    }
}
