<?php
/**
 * Email Service Class
 *
 * Handles email sending for magic link authentication in Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Email;

use Chrmrtns\KeylessAuth\Core\Database;
use Chrmrtns\KeylessAuth\Core\UrlHelper;
use Chrmrtns\KeylessAuth\Security\SecurityManager;

/**
 * EmailService class
 *
 * Manages email operations including:
 * - Login email generation and sending
 * - Email template integration
 * - Token generation and storage
 */
class EmailService {

    /**
     * Templates instance
     *
     * @var Templates
     */
    private $templates;

    /**
     * Database instance
     *
     * @var Database
     */
    private $database;

    /**
     * Security Manager instance
     *
     * @var SecurityManager
     */
    private $security_manager;

    /**
     * Constructor
     *
     * @param Templates        $templates        Optional Templates instance for dependency injection.
     * @param Database         $database         Optional Database instance for dependency injection.
     * @param SecurityManager  $security_manager Optional SecurityManager instance for dependency injection.
     */
    public function __construct($templates = null, $database = null, $security_manager = null) {
        $this->templates = $templates;
        $this->database = $database;
        $this->security_manager = $security_manager;
    }

    /**
     * Send login email with magic link
     *
     * @param \WP_User $user          User object.
     * @param string   $redirect_url  Optional redirect URL after login.
     * @return bool True if email sent successfully, false otherwise.
     */
    public function send_login_email($user, $redirect_url = '') {
        $user_id = $user->ID;
        $user_email = $user->user_email;

        // Generate secure token
        $expiration_time = time() + apply_filters('chrmrtns_kla_change_link_expiration', 600); // 10 minutes default
        $token = $this->security_manager->generate_secure_token($user_id, $expiration_time);

        // Store token in database
        if ($this->database && class_exists('Chrmrtns\\KeylessAuth\\Core\\Database')) {
            $this->database->store_login_token($user_id, $token, $expiration_time);
        } else {
            // Fallback to user meta (backwards compatibility)
            update_user_meta($user_id, 'chrmrtns_kla_login_token', $token);
            update_user_meta($user_id, 'chrmrtns_kla_login_token_expiration', $expiration_time);
        }

        // Build login URL
        $login_url = $this->build_login_url($user_id, $token, $redirect_url);

        // Get email body from template
        $email_body = $this->get_email_body($user_email, $login_url);

        // Get email subject
        $subject = sprintf(
            /* translators: %s: site name */
            __('Login at %s', 'keyless-auth'),
            get_bloginfo('name')
        );

        // Set email headers
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send email
        return wp_mail($user_email, $subject, $email_body, apply_filters('chrmrtns_kla_email_headers', $headers));
    }

    /**
     * Build magic link login URL
     *
     * @param int    $user_id      User ID.
     * @param string $token        Login token.
     * @param string $redirect_url Optional redirect URL.
     * @return string Magic link URL.
     */
    private function build_login_url($user_id, $token, $redirect_url = '') {
        // Create login URL with optional redirect
        $url_args = array(
            'chrmrtns_kla_token'   => $token,
            'chrmrtns_kla_user_id' => $user_id
        );

        // Add custom redirect if provided
        if (!empty($redirect_url)) {
            $url_args['chrmrtns_kla_redirect'] = rawurlencode($redirect_url);
        }

        return add_query_arg($url_args, UrlHelper::getCurrentPageUrl());
    }

    /**
     * Get email body from template or fallback
     *
     * @param string $user_email User email address.
     * @param string $login_url  Magic link URL.
     * @return string Email body HTML.
     */
    private function get_email_body($user_email, $login_url) {
        // Get email template if Templates class is available
        if ($this->templates && class_exists('Chrmrtns\\KeylessAuth\\Email\\Templates')) {
            return $this->templates->get_email_template($user_email, $login_url);
        }

        // Fallback template if Templates class not available
        return sprintf(
            /* translators: %1$s: site name, %2$s: login URL for href attribute, %3$s: login URL for display text */
            __('Hello! <br><br>Login at %1$s by visiting this url: <a href="%2$s" target="_blank">%3$s</a>', 'keyless-auth'),
            get_bloginfo('name'),
            esc_url($login_url),
            esc_url($login_url)
        );
    }
}
