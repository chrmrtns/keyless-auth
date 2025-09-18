<?php
/**
 * Database management for Keyless Auth
 *
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
// Direct database queries are necessary for custom table management, statistics, and cleanup operations.
// Caching is not appropriate for transactional data like login attempts and email logs.
// Schema changes are required for table creation, indexing, and maintenance operations.

class Chrmrtns_KLA_Database {

    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';

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
            $this->create_tables();
            update_option('chrmrtns_kla_db_version', self::DB_VERSION);
        }
    }

    /**
     * Create all plugin tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Login audit log table
        $login_logs_table = $wpdb->prefix . 'kla_login_logs';
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
        $mail_logs_table = $wpdb->prefix . 'kla_mail_logs';
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

        // User devices table (for future 2FA and companion app)
        $devices_table = $wpdb->prefix . 'kla_user_devices';
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
            PRIMARY KEY (id),
            UNIQUE KEY unique_device (user_id, device_fingerprint),
            KEY user_id (user_id),
            KEY device_token (device_token),
            KEY last_used (last_used),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Login tokens table (replace user_meta storage)
        $tokens_table = $wpdb->prefix . 'kla_login_tokens';
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
        $webhooks_table = $wpdb->prefix . 'kla_webhook_logs';
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
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_mail_logs_user_status ON {$wpdb->prefix}kla_mail_logs (user_id, status)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_login_logs_user_time ON {$wpdb->prefix}kla_login_logs (user_id, login_time)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_tokens_user_expires ON {$wpdb->prefix}kla_login_tokens (user_id, expires_at)");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_devices_user_active ON {$wpdb->prefix}kla_user_devices (user_id, is_active)");
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
        $sql = "SELECT * FROM {$wpdb->prefix}kla_login_logs
                WHERE $where_clause
                ORDER BY $order_by
                LIMIT %d OFFSET %d";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql contains sanitized dynamic content, $where_values properly prepared
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }

    /**
     * Log a login attempt
     */
    public function log_login_attempt($user_id, $user_email, $status, $token_hash = null, $notes = null) {
        global $wpdb;

        $ip_address = $this->get_client_ip();
        $user_agent = $this->get_user_agent();
        $device_type = $this->detect_device_type($user_agent);

        return $wpdb->insert(
            $wpdb->prefix . 'kla_login_logs',
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

        return $wpdb->insert(
            $wpdb->prefix . 'kla_mail_logs',
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

        return $wpdb->insert(
            $wpdb->prefix . 'kla_login_tokens',
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

        $sql = "SELECT * FROM {$wpdb->prefix}kla_login_tokens
                WHERE user_id = %d
                AND token_hash = %s
                AND expires_at > NOW()
                AND is_used = 0
                LIMIT 1";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is hardcoded with proper placeholders, no dynamic content
        $token = $wpdb->get_row($wpdb->prepare($sql, $user_id, $token_hash));

        if ($token) {
            // Mark token as used
            $wpdb->update(
                $wpdb->prefix . 'kla_login_tokens',
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
            "UPDATE {$wpdb->prefix}kla_login_tokens
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

        $where_clause = "expires_at < NOW()";
        $where_values = array();

        if ($user_id) {
            $where_clause .= " AND user_id = %d";
            $where_values[] = $user_id;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where_clause is safely constructed above
        $sql = "DELETE FROM {$wpdb->prefix}kla_login_tokens WHERE $where_clause";

        if (!empty($where_values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql contains sanitized $where_clause, $where_values properly prepared
            return $wpdb->query($wpdb->prepare($sql, $where_values));
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input, just static expires_at check
            return $wpdb->query($sql);
        }
    }

    /**
     * Get statistics
     */
    public function get_statistics() {
        global $wpdb;

        $stats = array();

        // Total logins
        $stats['total_logins'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kla_login_logs WHERE status = 'success'");

        // Logins this month
        $stats['logins_this_month'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kla_login_logs
             WHERE status = 'success'
             AND login_time >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        // Failed attempts
        $stats['failed_attempts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kla_login_logs WHERE status = 'failed'");

        // Total emails sent
        $stats['emails_sent'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kla_mail_logs WHERE status = 'sent'");

        // Active tokens
        $stats['active_tokens'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kla_login_tokens WHERE expires_at > NOW() AND is_used = 0");

        return $stats;
    }

    /**
     * Clean up old logs (for maintenance)
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;

        $date_threshold = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Clean login logs
        $login_deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}kla_login_logs WHERE login_time < %s",
            $date_threshold
        ));

        // Clean mail logs
        $mail_deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}kla_mail_logs WHERE sent_time < %s",
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
     * Drop all plugin tables (for uninstall)
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'kla_login_logs',
            $wpdb->prefix . 'kla_mail_logs',
            $wpdb->prefix . 'kla_user_devices',
            $wpdb->prefix . 'kla_login_tokens',
            $wpdb->prefix . 'kla_webhook_logs'
        );

        foreach ($tables as $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is safely constructed from $wpdb->prefix above
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('chrmrtns_kla_db_version');
    }
}