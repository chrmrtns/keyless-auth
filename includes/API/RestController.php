<?php
/**
 * REST API Controller for Keyless Auth
 *
 * Provides REST API endpoints for magic link authentication.
 * Runs in parallel with existing AJAX handlers for backward compatibility.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\API;

use Chrmrtns\KeylessAuth\Security\SecurityManager;
use Chrmrtns\KeylessAuth\Email\EmailService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RestController class
 *
 * Handles REST API endpoints for:
 * - Magic link login requests
 * - Future: Token validation, 2FA verification, etc.
 */
class RestController {

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'keyless-auth/v1';

    /**
     * Security Manager instance
     *
     * @var SecurityManager
     */
    private $security_manager;

    /**
     * Email Service instance
     *
     * @var EmailService
     */
    private $email_service;

    /**
     * Constructor
     *
     * @param SecurityManager $security_manager Security Manager instance.
     * @param EmailService    $email_service    Email Service instance.
     */
    public function __construct($security_manager, $email_service) {
        $this->security_manager = $security_manager;
        $this->email_service = $email_service;
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_routes() {
        // Check if REST API is enabled via feature flag
        if (!$this->is_rest_api_enabled()) {
            return;
        }

        // Register magic link request endpoint
        register_rest_route(
            $this->namespace,
            '/request-login',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'handle_login_request'),
                'permission_callback' => '__return_true', // Public endpoint
                'args'                => $this->get_login_request_args(),
            )
        );
    }

    /**
     * Check if REST API is enabled
     *
     * @return bool
     */
    private function is_rest_api_enabled() {
        // Feature flag: defaults to disabled for backward compatibility
        // Can be enabled in settings or via filter
        $enabled = get_option('chrmrtns_kla_enable_rest_api', '0') === '1';

        /**
         * Filter to enable/disable REST API
         *
         * @since 3.3.0
         * @param bool $enabled Whether REST API is enabled.
         */
        return apply_filters('chrmrtns_kla_rest_api_enabled', $enabled);
    }

    /**
     * Get arguments for login request endpoint
     *
     * @return array
     */
    private function get_login_request_args() {
        return array(
            'email_or_username' => array(
                'required'          => true,
                'type'              => 'string',
                'description'       => __('Email address or username for login', 'keyless-auth'),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ($param) {
                    return !empty($param);
                },
            ),
            'redirect_url' => array(
                'required'          => false,
                'type'              => 'string',
                'description'       => __('Optional redirect URL after login', 'keyless-auth'),
                'sanitize_callback' => 'esc_url_raw',
                'validate_callback' => function ($param) {
                    // Empty is valid (optional parameter)
                    if (empty($param)) {
                        return true;
                    }
                    // Must be valid URL
                    return filter_var($param, FILTER_VALIDATE_URL) !== false;
                },
            ),
        );
    }

    /**
     * Handle magic link login request
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function handle_login_request($request) {
        // Get parameters
        $email_or_username = $request->get_param('email_or_username');
        $redirect_url = $request->get_param('redirect_url');

        // Check if emergency disabled
        if ($this->security_manager && $this->security_manager->is_emergency_disabled()) {
            return new WP_Error(
                'emergency_disabled',
                __('Magic link login is temporarily disabled. Please contact an administrator.', 'keyless-auth'),
                array('status' => 503)
            );
        }

        // Get user by email or username
        $user = $this->security_manager->get_user_by_email_or_username($email_or_username);

        if (!$user) {
            return new WP_Error(
                'invalid_user',
                __('The username or email you provided does not exist. Please try again.', 'keyless-auth'),
                array('status' => 404)
            );
        }

        // Check admin approval compatibility (Profile Builder)
        if ($this->security_manager->is_admin_approval_required($user)) {
            return new WP_Error(
                'admin_approval_required',
                __('Your account is pending admin approval.', 'keyless-auth'),
                array('status' => 403)
            );
        }

        // Generate and send login link
        $sent = $this->email_service->send_login_email($user, $redirect_url);

        if (!$sent) {
            return new WP_Error(
                'email_failed',
                __('There was a problem sending your email. Please try again or contact an admin.', 'keyless-auth'),
                array('status' => 500)
            );
        }

        // Return success response
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Magic login link sent! Check your email and click the link to login.', 'keyless-auth'),
                'data'    => array(
                    'user_id' => $user->ID,
                    'email'   => $user->user_email,
                ),
            ),
            200
        );
    }

    /**
     * Get REST API base URL
     *
     * @return string
     */
    public function get_rest_url() {
        return rest_url($this->namespace);
    }

    /**
     * Get login request endpoint URL
     *
     * @return string
     */
    public function get_login_request_url() {
        return rest_url($this->namespace . '/request-login');
    }
}
