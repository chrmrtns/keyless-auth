<?php
/**
 * WooCommerce Integration for Keyless Auth
 *
 * Adds magic link authentication to WooCommerce login forms
 *
 * @package Chrmrtns\KeylessAuth
 * @since 3.1.0
 */

namespace Chrmrtns\KeylessAuth\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce {

    /**
     * Constructor
     */
    public function __construct() {
        // Only add hooks if WooCommerce is active and setting is enabled
        if (!$this->is_enabled()) {
            return;
        }

        // Add magic link field to WooCommerce login forms
        add_action('woocommerce_login_form', array($this, 'add_magic_link_field'), 20);

        // Show success message if redirected after sending magic link
        add_action('woocommerce_login_form_start', array($this, 'show_success_message'));

        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handler for WooCommerce magic link requests
        add_action('wp_ajax_nopriv_chrmrtns_kla_wc_request_magic_link', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_chrmrtns_kla_wc_request_magic_link', array($this, 'handle_ajax_request'));
    }

    /**
     * Check if WooCommerce integration is enabled
     *
     * @return bool
     */
    private function is_enabled() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return false;
        }

        // Check if setting is enabled
        if (get_option('chrmrtns_kla_enable_woocommerce', '0') !== '1') {
            return false;
        }

        return true;
    }

    /**
     * Show success message after magic link sent
     */
    public function show_success_message() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking display parameter
        if (isset($_GET['chrmrtns_kla_wc_sent']) && sanitize_text_field(wp_unslash($_GET['chrmrtns_kla_wc_sent'])) === '1') {
            ?>
            <div class="woocommerce-message" role="alert">
                <?php esc_html_e('Magic login link sent! Check your email and click the link to login.', 'keyless-auth'); ?>
            </div>
            <?php
        }
    }

    /**
     * Add magic link field to WooCommerce login forms
     */
    public function add_magic_link_field() {
        $random_id = wp_generate_password(8, false);
        ?>

        <p class="chrmrtns-kla-wc-magic-link-toggle">
            <a href="#" class="chrmrtns-kla-wc-toggle-link" data-target="chrmrtns-kla-wc-form-<?php echo esc_attr($random_id); ?>">
                <?php esc_html_e('Or login with magic link instead', 'keyless-auth'); ?>
            </a>
        </p>

        <div id="chrmrtns-kla-wc-form-<?php echo esc_attr($random_id); ?>" class="chrmrtns-kla-wc-magic-form" style="display: none;">
            <p class="form-row form-row-wide">
                <label for="chrmrtns_kla_wc_email_<?php echo esc_attr($random_id); ?>">
                    <?php esc_html_e('Email address', 'keyless-auth'); ?>&nbsp;
                    <span class="required" aria-hidden="true">*</span>
                    <span class="screen-reader-text"><?php esc_html_e('Required', 'keyless-auth'); ?></span>
                </label>
                <input
                    type="email"
                    class="input-text woocommerce-Input"
                    name="chrmrtns_kla_wc_email"
                    id="chrmrtns_kla_wc_email_<?php echo esc_attr($random_id); ?>"
                    autocomplete="email"
                    required
                    aria-required="true"
                    placeholder="<?php esc_attr_e('your@email.com', 'keyless-auth'); ?>"
                />
            </p>

            <p class="form-row">
                <?php wp_nonce_field('chrmrtns_kla_wc_magic_login', 'chrmrtns_kla_wc_nonce'); ?>
                <button
                    type="button"
                    class="button chrmrtns-kla-wc-submit-magic"
                    data-email-field="chrmrtns_kla_wc_email_<?php echo esc_attr($random_id); ?>"
                >
                    <?php esc_html_e('Send Magic Link', 'keyless-auth'); ?>
                </button>
            </p>

            <p class="chrmrtns-kla-wc-magic-status" style="display: none;"></p>
        </div>

        <?php
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on WooCommerce pages with login forms
        if (!is_account_page() && !is_checkout()) {
            return;
        }

        // Enqueue API abstraction layer first
        \Chrmrtns\KeylessAuth\Frontend\AssetLoader::enqueueFrontendScripts();

        // Enqueue WooCommerce-specific JavaScript (depends on API layer)
        wp_enqueue_script(
            'chrmrtns-kla-wc-integration',
            CHRMRTNS_KLA_PLUGIN_URL . 'assets/js/woocommerce-integration.js',
            array('keyless-auth-api'), // Depend on API abstraction layer
            CHRMRTNS_KLA_VERSION,
            true
        );

        // Localize script with AJAX data (for backward compatibility)
        wp_localize_script('chrmrtns-kla-wc-integration', 'chrmrtns_kla_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrmrtns_kla_ajax_nonce')
        ));

        // Localize script with translatable strings
        wp_localize_script('chrmrtns-kla-wc-integration', 'chrmrtns_kla_wc', array(
            'error_invalid_email' => __('Please enter a valid email address.', 'keyless-auth'),
            'sending' => __('Sending...', 'keyless-auth'),
            'send_link' => __('Send Magic Link', 'keyless-auth'),
            'error_occurred' => __('An error occurred. Please try again.', 'keyless-auth'),
            'open_form' => __('Or login with magic link instead', 'keyless-auth'),
            'close_form' => __('Close magic link form', 'keyless-auth'),
        ));

        // Enqueue WooCommerce-specific CSS
        wp_enqueue_style(
            'chrmrtns-kla-wc-integration',
            CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/woocommerce-integration.css',
            array(),
            CHRMRTNS_KLA_VERSION
        );
    }

    /**
     * Handle AJAX request for magic link
     */
    public function handle_ajax_request() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_kla_ajax_nonce')) {
            wp_send_json_error(__('Security check failed. Please try again.', 'keyless-auth'));
        }

        // Get and validate email
        if (!isset($_POST['email'])) {
            wp_send_json_error(__('Email address is required.', 'keyless-auth'));
        }

        $email = sanitize_email(wp_unslash($_POST['email']));

        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'keyless-auth'));
        }

        // Get user by email
        $user = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error(__('No account found with this email address.', 'keyless-auth'));
        }

        // Check admin approval (Profile Builder compatibility)
        if (function_exists('wppb_check_admin_approval')) {
            $admin_approval = get_user_meta($user->ID, 'wppb_approved', true);
            if ($admin_approval !== 'approved') {
                wp_send_json_error(__('Your account is pending admin approval.', 'keyless-auth'));
            }
        }

        // Set redirect URL based on referer
        // If coming from checkout, redirect back to checkout after login
        if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to'])) {
            $_POST['chrmrtns_kla_redirect'] = esc_url_raw(wp_unslash($_POST['redirect_to']));
        }

        // Send login email using Core class
        if (class_exists('Chrmrtns\\KeylessAuth\\Core\\Core')) {
            $core = new \Chrmrtns\KeylessAuth\Core\Core();
            $reflection = new \ReflectionClass($core);
            $method = $reflection->getMethod('send_login_email');
            $method->setAccessible(true);

            if (!$method->invoke($core, $user)) {
                wp_send_json_error(__('There was a problem sending your email. Please try again or contact an admin.', 'keyless-auth'));
            }

            wp_send_json_success(__('Magic login link sent! Check your email.', 'keyless-auth'));
        }

        wp_send_json_error(__('System error. Please try again.', 'keyless-auth'));
    }
}
