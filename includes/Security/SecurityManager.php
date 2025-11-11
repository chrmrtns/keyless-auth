<?php
/**
 * Security Manager Class
 *
 * Handles token generation, validation, user verification, and security hardening
 * for Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Security;

use Chrmrtns\KeylessAuth\Core\Database;

/**
 * SecurityManager class
 *
 * Manages security operations including:
 * - Token generation and validation
 * - User lookup and verification
 * - User enumeration prevention
 * - Emergency disable functionality
 */
class SecurityManager {

    /**
     * Database instance
     *
     * @var Database
     */
    private $database;

    /**
     * Constructor
     *
     * @param Database $database Optional database instance for dependency injection.
     */
    public function __construct($database = null) {
        $this->database = $database;
    }

    /**
     * Generate secure token for magic link authentication
     *
     * @param int $user_id User ID.
     * @param int $expiration_time Token expiration timestamp.
     * @return string Secure token hash.
     */
    public function generate_secure_token($user_id, $expiration_time) {
        return wp_hash($user_id . $expiration_time . wp_salt());
    }

    /**
     * Validate login token
     *
     * @param int    $user_id       User ID.
     * @param string $provided_token Token from URL.
     * @return bool True if valid, false otherwise.
     */
    public function validate_login_token($user_id, $provided_token) {
        // Try new database system first
        if ($this->database && class_exists('Chrmrtns\\KeylessAuth\\Core\\Database')) {
            // Log the login attempt
            $user = get_user_by('ID', $user_id);
            $user_email = $user ? $user->user_email : '';

            if ($this->database->validate_login_token($user_id, $provided_token)) {
                // Log successful login
                $this->database->log_login_attempt($user_id, $user_email, 'success', $provided_token);
                return true;
            } else {
                // Log failed login
                $this->database->log_login_attempt($user_id, $user_email, 'failed', $provided_token, 'Invalid or expired token');
                return false;
            }
        }

        // Fallback to legacy user meta system
        $stored_token = get_user_meta($user_id, 'chrmrtns_kla_login_token', true);
        $expiration = get_user_meta($user_id, 'chrmrtns_kla_login_token_expiration', true);

        // Check if token exists
        if (empty($stored_token) || empty($expiration)) {
            return false;
        }

        // Check expiration
        if (time() > $expiration) {
            delete_user_meta($user_id, 'chrmrtns_kla_login_token');
            delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');
            return false;
        }

        // Use hash_equals to prevent timing attacks
        if (!hash_equals($stored_token, $provided_token)) {
            return false;
        }

        return true;
    }

    /**
     * Get user by email or username
     *
     * @param string $user_email_username Email or username.
     * @return \WP_User|false User object or false if not found.
     */
    public function get_user_by_email_or_username($user_email_username) {
        if (is_email($user_email_username)) {
            return get_user_by('email', $user_email_username);
        } else {
            return get_user_by('login', $user_email_username);
        }
    }

    /**
     * Check if admin approval is required (Profile Builder integration)
     *
     * @param \WP_User $user User object.
     * @return bool True if admin approval required and not approved.
     */
    public function is_admin_approval_required($user) {
        // Admin approval compatibility with Profile Builder
        if (function_exists('wppb_check_admin_approval')) {
            $admin_approval = get_user_meta($user->ID, 'wppb_approved', true);
            return ($admin_approval !== 'approved');
        }
        return false;
    }

    /**
     * Check if 2FA is emergency disabled
     *
     * @return bool True if emergency disabled.
     */
    public function is_emergency_disabled() {
        // Emergency disable via wp-config.php constant
        if (defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) {
            return true;
        }

        // Emergency disable via database option (for easier recovery)
        if (get_option('chrmrtns_kla_2fa_emergency_disable', false)) {
            return true;
        }

        return false;
    }

    /**
     * Setup user enumeration prevention
     *
     * Registers filters and actions to prevent username discovery attacks.
     *
     * @since 3.3.0
     */
    public function setup_enumeration_prevention() {
        // Block REST API user endpoints
        add_filter('rest_endpoints', array($this, 'block_rest_user_endpoints'));

        // Block ?author=N queries early (before canonical redirect)
        add_action('parse_request', array($this, 'block_author_query_early'));

        // Block author archive access
        add_action('template_redirect', array($this, 'block_author_archives'));

        // Remove login error messages
        add_filter('login_errors', array($this, 'remove_login_errors'));

        // Remove comment author classes that expose usernames
        add_filter('comment_class', array($this, 'remove_comment_author_class'));

        // Block oembed user data
        add_filter('oembed_response_data', array($this, 'remove_oembed_author_data'), 10, 2);
    }

    /**
     * Block REST API user endpoints for non-logged-in users
     *
     * @param array $endpoints REST API endpoints.
     * @return array Filtered endpoints.
     */
    public function block_rest_user_endpoints($endpoints) {
        if (!is_user_logged_in()) {
            if (isset($endpoints['/wp/v2/users'])) {
                unset($endpoints['/wp/v2/users']);
            }
            if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
                unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
            }
        }
        return $endpoints;
    }

    /**
     * Block ?author=N queries early (before WordPress canonical redirect)
     *
     * @param \WP $wp WordPress environment object.
     */
    public function block_author_query_early($wp) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Blocking enumeration attack, not processing form data
        if (isset($_GET['author']) && !empty($_GET['author'])) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }

    /**
     * Block author archive access
     */
    public function block_author_archives() {
        if (is_admin()) {
            return;
        }

        // Block author archives (catches /author/username/ URLs)
        if (is_author()) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }

    /**
     * Remove login error messages to prevent username enumeration
     *
     * @param string $error Error message.
     * @return string Empty string.
     */
    public function remove_login_errors($error) {
        return '';
    }

    /**
     * Remove comment author classes that expose usernames
     *
     * @param array $classes CSS classes.
     * @return array Filtered classes.
     */
    public function remove_comment_author_class($classes) {
        foreach ($classes as $key => $class) {
            if (strpos($class, 'comment-author-') === 0) {
                unset($classes[$key]);
            }
        }
        return $classes;
    }

    /**
     * Remove author data from oembed responses
     *
     * @param array    $data oembed data.
     * @param \WP_Post $post Post object.
     * @return array Filtered data.
     */
    public function remove_oembed_author_data($data, $post) {
        unset($data['author_name']);
        unset($data['author_url']);
        return $data;
    }
}
