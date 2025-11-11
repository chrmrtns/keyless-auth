<?php
/**
 * Core authentication orchestrator for Keyless Auth
 *
 * Pure orchestrator class that coordinates magic link authentication.
 * Delegates actual work to specialized services:
 * - SecurityManager: User validation, token generation, enumeration prevention
 * - EmailService: Magic link email generation and sending
 * - TokenValidator: Token validation and login processing
 * - WpLoginIntegration: wp-login.php integration
 * - RestController: REST API endpoints
 *
 * Responsibilities:
 * - Initialize and wire up service dependencies
 * - Register WordPress hooks and shortcodes
 * - Coordinate form submissions and login requests
 * - Apply security settings from options
 *
 * @package Keyless Auth
 * @since 2.0.1
 * @version 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Security\TwoFA\Core as TwoFACore;
use Chrmrtns\KeylessAuth\Email\Templates;
use Chrmrtns\KeylessAuth\Email\EmailService;
use Chrmrtns\KeylessAuth\Admin\Admin;
use Chrmrtns\KeylessAuth\Admin\Pages\OptionsPage;
use Chrmrtns\KeylessAuth\Frontend\AssetLoader;
use Chrmrtns\KeylessAuth\Frontend\MessageFormatter;
use Chrmrtns\KeylessAuth\Frontend\LoginFormRenderer;
use Chrmrtns\KeylessAuth\Frontend\WpLoginIntegration;
use Chrmrtns\KeylessAuth\Security\SecurityManager;
use Chrmrtns\KeylessAuth\Security\TokenValidator;
use Chrmrtns\KeylessAuth\API\RestController;

/**
 * Core orchestrator class
 *
 * Coordinates magic link authentication by wiring up specialized services
 * and registering WordPress hooks. Does not contain business logic itself.
 *
 * @since 2.0.1
 */
class Core {

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
     * Token Validator instance
     *
     * @var TokenValidator
     */
    private $token_validator;

    /**
     * WP-Login Integration instance
     *
     * @var WpLoginIntegration
     */
    private $wp_login_integration;

    /**
     * REST API Controller instance
     *
     * @var RestController
     */
    private $rest_controller;

    /**
     * Constructor - Initialize services and register hooks
     *
     * Sets up the dependency tree:
     * 1. SecurityManager (user validation, token generation)
     * 2. EmailService (depends on SecurityManager)
     * 3. TokenValidator (depends on SecurityManager)
     * 4. WpLoginIntegration (depends on SecurityManager)
     * 5. RestController (depends on SecurityManager and EmailService)
     *
     * Registers all WordPress hooks and shortcodes.
     * Applies security settings from plugin options.
     *
     * @since 2.0.1
     */
    public function __construct() {
        // Initialize Security Manager
        global $chrmrtns_kla_database;
        $this->security_manager = new SecurityManager($chrmrtns_kla_database);

        // Initialize Email Service
        $templates = new Templates();
        $this->email_service = new EmailService($templates, $chrmrtns_kla_database, $this->security_manager);

        // Initialize Token Validator
        $this->token_validator = new TokenValidator($this->security_manager);

        // Initialize WP-Login Integration
        $this->wp_login_integration = new WpLoginIntegration($this->security_manager, array($this, 'handle_login_request'));
        add_action('wp_ajax_nopriv_chrmrtns_kla_request_login_code', array($this, 'handle_login_request'));
        add_action('wp_ajax_chrmrtns_kla_request_login_code', array($this, 'handle_login_request'));
        add_action('wp_loaded', array($this, 'handle_login_link'), 1);
        add_action('init', array($this, 'handle_form_submission'));

        // Initialize REST API Controller
        $this->rest_controller = new RestController($this->security_manager, $this->email_service);
        add_action('rest_api_init', array($this->rest_controller, 'register_routes'));
        add_shortcode('keyless-auth', array($this, 'render_login_form'));
        add_shortcode('keyless-auth-full', array($this, 'render_full_login_form'));

        // wp-login.php integration - only add hooks if enabled AND redirect is disabled
        // These options are mutually exclusive: can't add magic login field to wp-login.php
        // if we're redirecting away from it
        $enable_wp_login = get_option('chrmrtns_kla_enable_wp_login', '0') === '1';
        $redirect_wp_login = get_option('chrmrtns_kla_redirect_wp_login', '0') === '1';

        if ($enable_wp_login && !$redirect_wp_login) {
            add_action('login_footer', array($this->wp_login_integration, 'add_wp_login_field'));
            add_action('login_init', array($this->wp_login_integration, 'handle_wp_login_submission'));
            add_action('login_enqueue_scripts', array('Chrmrtns\KeylessAuth\Frontend\AssetLoader', 'enqueueFrontendStyles'));
        }

        // Hook early to catch wp-login.php requests for redirect
        add_action('init', array($this->wp_login_integration, 'maybe_redirect_wp_login'), 1);

        // Hook into failed login to redirect with error parameters
        add_action('wp_login_failed', array($this->wp_login_integration, 'handle_failed_login'), 10, 2);

        // Disable XML-RPC if option is enabled
        if (get_option('chrmrtns_kla_disable_xmlrpc', '0') === '1') {
            add_filter('xmlrpc_enabled', '__return_false');
        }

        // Disable Application Passwords if option is enabled
        if (get_option('chrmrtns_kla_disable_app_passwords', '0') === '1') {
            add_filter('wp_is_application_passwords_available', '__return_false');
        }

        // Prevent user enumeration if option is enabled
        if (get_option('chrmrtns_kla_prevent_user_enumeration', '0') === '1') {
            $this->security_manager->setup_enumeration_prevention();
        }
    }

    /**
     * Render magic link login form shortcode
     *
     * Shortcode: [keyless-auth]
     *
     * Displays a simple email/username input form for requesting magic links.
     * Shows status messages (success, errors) and handles logged-in state.
     *
     * Supported attributes:
     * - redirect: URL to redirect after login
     * - button_text: Custom button text
     * - description: Custom form description
     * - label: Custom input label
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML form
     */
    public function render_login_form($atts = array()) {
        // Enqueue styles when shortcode is rendered
        AssetLoader::enqueueFrontendStyles();

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'redirect' => '',
            'button_text' => '',
            'description' => '',
            'label' => ''
        ), $atts, 'keyless-auth');
        ob_start();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Form display logic, not processing
        $account = (isset($_POST['user_email_username'])) ? sanitize_text_field(wp_unslash($_POST['user_email_username'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $error_token = (isset($_GET['chrmrtns_kla_error_token'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_error_token'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $adminapp_error = (isset($_GET['chrmrtns_kla_adminapp_error'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_adminapp_error'])) : false;

        $sent_link = get_option('chrmrtns_kla_login_request_error');

        // Render status messages
        LoginFormRenderer::renderStatusMessages($account, $sent_link, $error_token, $adminapp_error);

        // Render the login form if not logged in
        if (!is_user_logged_in() && !($account && !is_wp_error($sent_link))) {
            LoginFormRenderer::renderLoginFormHtml($atts);
        }

        return ob_get_clean();
    }


    /**
     * Render full login form with both password and magic link options
     *
     * Shortcode: [keyless-auth-full]
     *
     * Displays a complete login form with:
     * - Standard WordPress password login
     * - Magic link request option
     * - Status messages and error handling
     *
     * Supported attributes:
     * - redirect: URL to redirect after login
     * - show_title: Display form title (yes/no, default: yes)
     * - title_text: Custom title text (default: "Login")
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML form
     */
    public function render_full_login_form($atts = array()) {
        // Enqueue styles when shortcode is rendered
        AssetLoader::enqueueFrontendStyles();

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'redirect' => '',
            'show_title' => 'yes',
            'title_text' => __('Login', 'keyless-auth')
        ), $atts, 'keyless-auth-full');

        ob_start();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Form display logic, not processing
        $account = (isset($_POST['user_email_username'])) ? sanitize_text_field(wp_unslash($_POST['user_email_username'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $error_token = (isset($_GET['chrmrtns_kla_error_token'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_error_token'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $adminapp_error = (isset($_GET['chrmrtns_kla_adminapp_error'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_adminapp_error'])) : false;

        $sent_link = get_option('chrmrtns_kla_login_request_error');

        // Render status messages
        LoginFormRenderer::renderFullFormStatusMessages($account, $sent_link, $error_token, $adminapp_error);

        // Render the login form if not logged in
        if (!is_user_logged_in() && !($account && !is_wp_error($sent_link))) {
            // Show title if enabled
            if ($atts['show_title'] === 'yes') {
                echo '<h3 class="chrmrtns-login-title">' . esc_html($atts['title_text']) . '</h3>';
            }

            LoginFormRenderer::renderFullLoginFormHtml($atts);
        }

        return ob_get_clean();
    }


    /**
     * Handle magic link form submission
     *
     * Called on init hook to process magic link request forms.
     * Only handles forms with chrmrtns_kla_magic_form field present.
     * Does NOT handle standard WordPress password logins.
     *
     * Delegates to handle_login_request() for actual processing.
     *
     * @since 2.0.0
     * @return void
     */
    public function handle_form_submission() {
        // Only handle magic link form submissions, not standard WordPress login
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in handle_login_request()
        if (isset($_POST['chrmrtns_kla_magic_form']) && isset($_POST['user_email_username']) && isset($_POST['nonce'])) {
            $this->handle_login_request();
        }
    }
    
    /**
     * Handle magic link login request (AJAX and form submissions)
     *
     * Processes magic link requests from:
     * - Frontend forms (via handle_form_submission)
     * - AJAX requests (wp_ajax_nopriv_chrmrtns_kla_request_login_code)
     * - wp-login.php magic link field
     *
     * Flow:
     * 1. Verify nonce
     * 2. Validate user exists
     * 3. Check admin approval status
     * 4. Generate magic link and send email
     *
     * Errors are stored in chrmrtns_kla_login_request_error option
     * for display on next page load.
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_login_request() {
        // Delete any existing error
        delete_option('chrmrtns_kla_login_request_error');
        
        if (!isset($_POST['user_email_username'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_kla_keyless_login_request')) {
            $error = new WP_Error('nonce_failed', __('Security check failed. Please try again.', 'keyless-auth'));
            update_option('chrmrtns_kla_login_request_error', $error);
            return;
        }
        
        $user_email_username = sanitize_text_field(wp_unslash($_POST['user_email_username']));
        
        // Get user by email or username
        $user = $this->security_manager->get_user_by_email_or_username($user_email_username);
        
        if (!$user) {
            $error = new WP_Error('invalid_user', __('The username or email you provided do not exist. Please try again.', 'keyless-auth'));
            update_option('chrmrtns_kla_login_request_error', $error);
            return;
        }
        
        // Check admin approval compatibility
        if ($this->security_manager->is_admin_approval_required($user)) {
            wp_safe_redirect(add_query_arg('chrmrtns_kla_adminapp_error', '1', UrlHelper::getCurrentPageUrl()));
            exit;
        }
        
        // Generate and send login link
        // Get redirect URL from POST if provided
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- POST data already validated in handle_login_request()
        $redirect_url = isset($_POST['chrmrtns_kla_redirect']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_redirect'])) : '';

        if (!$this->email_service->send_login_email($user, $redirect_url)) {
            $error = new WP_Error('email_failed', __('There was a problem sending your email. Please try again or contact an admin.', 'keyless-auth'));
            update_option('chrmrtns_kla_login_request_error', $error);
        }
    }
    

    /**
     * Handle login link clicks
     *
     * Delegates to TokenValidator for processing.
     */
    public function handle_login_link() {
        $this->token_validator->handle_login_link();
    }
    
}
