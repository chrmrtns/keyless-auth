<?php
/**
 * Frontend 2FA management for Keyless Auth
 * Handles the [keyless-auth-2fa] shortcode and user-facing 2FA interface
 *
 * @since 2.4.0
 */



namespace Chrmrtns\KeylessAuth\Security\TwoFA;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


class Frontend {

    /**
     * TOTP instance
     */
    private $totp;

    /**
     * Database instance
     */
    private $database;

    /**
     * 2FA Core instance
     */
    private $core;

    /**
     * Constructor
     */
    public function __construct() {
        // Check emergency disable first - don't initialize if disabled
        if ($this->is_emergency_disabled()) {
            return;
        }

        $this->totp = new TOTP();

        global $chrmrtns_kla_database;
        $this->database = $chrmrtns_kla_database;

        // Initialize hooks
        add_shortcode('keyless-auth-2fa', array($this, 'render_2fa_shortcode'));
        add_action('wp_ajax_chrmrtns_2fa_setup', array($this, 'handle_ajax_setup'));
        add_action('wp_ajax_chrmrtns_2fa_disable', array($this, 'handle_ajax_disable'));
        add_action('wp_ajax_chrmrtns_2fa_generate_backup_codes', array($this, 'handle_ajax_generate_backup_codes'));

    }

    /**
     * Check if 2FA is emergency disabled
     *
     * @return bool
     */
    private function is_emergency_disabled() {
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
     * Enqueue frontend scripts and styles
     * Called when [keyless-auth-2fa] shortcode is rendered
     *
     * @since 2.4.0
     * @since 3.1.0 Added chrmrtns_kla_2fa_custom_css_variables filter
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue when shortcode is actually used
        // This ensures scripts/styles are available when needed without loading globally

        // Debug: Log the URLs being used
        $qr_script_url = CHRMRTNS_KLA_PLUGIN_URL . 'assets/js/qrcode.min.js';
        $frontend_script_url = CHRMRTNS_KLA_PLUGIN_URL . 'assets/js/2fa-frontend.js';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log('Keyless Auth Debug: QR script URL: ' . $qr_script_url);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log('Keyless Auth Debug: Frontend script URL: ' . $frontend_script_url);
        }

        // Enqueue QR code library first - load in header to ensure availability
        wp_enqueue_script('chrmrtns-kla-qrcode',
            $qr_script_url,
            array(),
            CHRMRTNS_KLA_VERSION . '-fixed',  // Force cache bust for fixed version
            false  // Load in header, not footer
        );

        // Debug: Add inline script to check if QR library loads
        wp_add_inline_script('chrmrtns-kla-qrcode', 'console.log("QRCode library script loaded, QRCode available:", typeof QRCode !== "undefined");');

        wp_enqueue_script('chrmrtns-kla-2fa-frontend',
            $frontend_script_url,
            array('jquery', 'chrmrtns-kla-qrcode'),
            CHRMRTNS_KLA_VERSION,
            false  // Load in header, not footer
        );

        wp_localize_script('chrmrtns-kla-2fa-frontend', 'chrmrtns_2fa', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chrmrtns_2fa_frontend'),
            'strings' => array(
                'confirm_disable' => __('Are you sure you want to disable 2FA? This will make your account less secure.', 'keyless-auth'),
                'setup_success' => __('2FA has been enabled successfully!', 'keyless-auth'),
                'disable_success' => __('2FA has been disabled.', 'keyless-auth'),
                'error' => __('An error occurred. Please try again.', 'keyless-auth'),
                'invalid_code' => __('Invalid verification code. Please try again.', 'keyless-auth')
            )
        ));

        wp_enqueue_style('chrmrtns-kla-2fa-frontend',
            CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/2fa-frontend.css',
            array(),
            CHRMRTNS_KLA_VERSION
        );

        /**
         * Filter: chrmrtns_kla_2fa_custom_css_variables
         *
         * Allows themes and plugins to customize 2FA page CSS variables without using !important.
         * The filtered CSS is added as inline styles after the main stylesheet,
         * ensuring proper cascade order.
         *
         * @since 3.1.0
         *
         * @param string $css Custom CSS to append after 2FA plugin styles (default: empty string)
         *
         * @example Theme integration for 2FA page
         * add_filter('chrmrtns_kla_2fa_custom_css_variables', function($css) {
         *     return $css . '
         *         :root {
         *             --kla-primary: var(--my-theme-primary);
         *             --kla-background: var(--my-theme-bg);
         *         }
         *     ';
         * });
         */
        $custom_2fa_css = apply_filters('chrmrtns_kla_2fa_custom_css_variables', '');
        if (!empty($custom_2fa_css)) {
            wp_add_inline_style('chrmrtns-kla-2fa-frontend', $custom_2fa_css);
        }
    }

    /**
     * Render 2FA management shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_2fa_shortcode($atts = array()) {
        // Enqueue scripts directly in shortcode to ensure they're loaded
        $this->enqueue_frontend_scripts();

        // Check if 2FA system is enabled
        if (!get_option('chrmrtns_kla_2fa_enabled', false)) {
            return '<div class="chrmrtns-2fa-notice" role="status" aria-live="polite">' .
                   '<p>' . esc_html__('Two-factor authentication is not available.', 'keyless-auth') . '</p>' .
                   '</div>';
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            return '<div class="chrmrtns-2fa-notice" role="alert" aria-live="assertive">' .
                   '<p>' . esc_html__('You must be logged in to manage 2FA settings.', 'keyless-auth') . '</p>' .
                   '</div>';
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $settings = $this->database->get_user_2fa_settings($user_id);
        $has_2fa = $settings && $settings->totp_enabled;
        $role_required = $this->is_role_required($user_id);

        ob_start();

        echo '<div class="chrmrtns-2fa-container" id="chrmrtns-2fa-container">';

        if ($has_2fa) {
            $this->render_2fa_active_view($user, $settings, $role_required);
        } else {
            $this->render_2fa_setup_view($user, $role_required);
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Render 2FA setup view (when not enabled)
     *
     * @param WP_User $user User object
     * @param bool $role_required Whether user's role requires 2FA
     */
    private function render_2fa_setup_view($user, $role_required) {
        $secret = $this->totp->generate_secret();
        $totp_uri = $this->totp->get_totp_uri($secret, $user->user_email);

        // Debug: Log TOTP URI generation and constants
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log('Keyless Auth Debug: Generated TOTP URI: ' . $totp_uri);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log('Keyless Auth Debug: Generated secret: ' . $secret);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log('Keyless Auth Debug: CHRMRTNS_KLA_PLUGIN_DIR constant: ' . CHRMRTNS_KLA_PLUGIN_DIR);
        }

        ?>
        <div class="chrmrtns-2fa-header">
            <h3><?php esc_html_e('Two-Factor Authentication', 'keyless-auth'); ?></h3>
            <?php if ($role_required): ?>
                <div class="chrmrtns-2fa-required-notice">
                    <p><strong><?php esc_html_e('2FA is required for your account role.', 'keyless-auth'); ?></strong></p>
                </div>
            <?php endif; ?>
            <p><?php esc_html_e('Enhance your account security with two-factor authentication using your smartphone.', 'keyless-auth'); ?></p>
        </div>

        <div class="chrmrtns-2fa-setup" id="chrmrtns-2fa-setup">
            <div class="chrmrtns-2fa-step">
                <h4><?php esc_html_e('Step 1: Install an Authenticator App', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('Download one of these apps on your smartphone:', 'keyless-auth'); ?></p>
                <ul class="chrmrtns-2fa-apps">
                    <li><strong>Google Authenticator</strong> (iOS/Android)</li>
                    <li><strong>Authy</strong> (iOS/Android/Desktop)</li>
                    <li><strong>1Password</strong> (Premium)</li>
                    <li><strong>Microsoft Authenticator</strong> (iOS/Android)</li>
                </ul>
            </div>

            <div class="chrmrtns-2fa-step">
                <h4><?php esc_html_e('Step 2: Scan QR Code or Enter Secret', 'keyless-auth'); ?></h4>

                <div class="chrmrtns-2fa-qr-section">
                    <div class="chrmrtns-2fa-qr">
                        <div id="chrmrtns-2fa-qrcode" data-totp-uri="<?php echo esc_attr($totp_uri); ?>" role="img" aria-label="<?php esc_attr_e('QR code for two-factor authentication setup', 'keyless-auth'); ?>">
                            <div class="chrmrtns-qr-loading" role="status" aria-live="polite"><?php esc_html_e('Loading QR code...', 'keyless-auth'); ?></div>
                        </div>

                        <!-- QR code will be generated by external JavaScript file -->
                    </div>
                    <div class="chrmrtns-2fa-manual">
                        <p><strong><?php esc_html_e('Can\'t scan? Enter manually:', 'keyless-auth'); ?></strong></p>
                        <div class="chrmrtns-2fa-secret">
                            <code><?php echo esc_html($secret); ?></code>
                            <button type="button" class="chrmrtns-copy-button" data-copy="<?php echo esc_attr($secret); ?>" aria-label="<?php esc_attr_e('Copy secret key to clipboard', 'keyless-auth'); ?>">
                                <?php esc_html_e('Copy', 'keyless-auth'); ?>
                            </button>
                        </div>
                        <p><small><?php
                        /* translators: %s: user's email address */
                        printf(esc_html__('Account: %s', 'keyless-auth'), esc_html($user->user_email)); ?></small></p>
                        <p><small><?php
                        /* translators: %s: website name */
                        printf(esc_html__('Issuer: %s', 'keyless-auth'), esc_html(get_bloginfo('name'))); ?></small></p>
                    </div>
                </div>
            </div>

            <div class="chrmrtns-2fa-step">
                <h4><?php esc_html_e('Step 3: Verify Setup', 'keyless-auth'); ?></h4>
                <form id="chrmrtns-2fa-setup-form">
                    <?php wp_nonce_field('chrmrtns_2fa_setup', 'chrmrtns_2fa_setup_nonce'); ?>
                    <input type="hidden" name="secret" value="<?php echo esc_attr($secret); ?>">

                    <p>
                        <label for="verification_code"><?php esc_html_e('Enter the 6-digit code from your app:', 'keyless-auth'); ?></label>
                        <input type="text" id="verification_code" name="verification_code"
                               maxlength="6" pattern="[0-9]{6}" required aria-required="true"
                               placeholder="123456" autocomplete="off"
                               aria-describedby="verification-code-hint" />
                        <span id="verification-code-hint" class="sr-only"><?php esc_html_e('Enter six digit numeric code', 'keyless-auth'); ?></span>
                    </p>

                    <p class="submit">
                        <button type="submit" class="chrmrtns-kla-btn chrmrtns-kla-btn-primary chrmrtns-2fa-setup-btn">
                            <?php esc_html_e('Enable 2FA', 'keyless-auth'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <div class="chrmrtns-2fa-info">
            <h4><?php esc_html_e('What is Two-Factor Authentication?', 'keyless-auth'); ?></h4>
            <p><?php esc_html_e('2FA adds an extra layer of security to your account by requiring both your password and a code from your phone to log in. Even if someone gets your password, they can\'t access your account without your phone.', 'keyless-auth'); ?></p>
        </div>
        <?php
    }

    /**
     * Render 2FA active view (when enabled)
     *
     * @param WP_User $user User object
     * @param object $settings 2FA settings
     * @param bool $role_required Whether user's role requires 2FA
     */
    private function render_2fa_active_view($user, $settings, $role_required) {
        $backup_codes = $settings->totp_backup_codes ?: array();
        $backup_count = count($backup_codes);
        $last_used = $settings->totp_last_used ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($settings->totp_last_used)) : __('Never', 'keyless-auth');

        ?>
        <div class="chrmrtns-2fa-header">
            <h3><?php esc_html_e('Two-Factor Authentication', 'keyless-auth'); ?></h3>
            <div class="chrmrtns-2fa-status chrmrtns-2fa-enabled" role="status" aria-live="polite">
                <span class="chrmrtns-2fa-status-icon" aria-hidden="true">✓</span>
                <strong><?php esc_html_e('2FA is Active', 'keyless-auth'); ?></strong>
            </div>
            <p><?php esc_html_e('Your account is protected with two-factor authentication.', 'keyless-auth'); ?></p>
        </div>

        <div class="chrmrtns-2fa-step">
            <h4>🔐 <?php esc_html_e('Security Status', 'keyless-auth'); ?></h4>
            <div class="chrmrtns-2fa-info-grid">
                <div class="chrmrtns-2fa-info-item">
                    <h5><?php esc_html_e('Last Used', 'keyless-auth'); ?></h5>
                    <p><?php echo esc_html($last_used); ?></p>
                </div>
                <div class="chrmrtns-2fa-info-item">
                    <h5><?php esc_html_e('Backup Codes', 'keyless-auth'); ?></h5>
                    <p><?php
                    /* translators: %d: number of backup codes remaining */
                    printf(esc_html(_n('%d code remaining', '%d codes remaining', $backup_count, 'keyless-auth')), (int) $backup_count); ?></p>
                </div>
                <div class="chrmrtns-2fa-info-item">
                    <h5><?php esc_html_e('Account', 'keyless-auth'); ?></h5>
                    <p><?php echo esc_html($user->user_email); ?></p>
                </div>
            </div>
        </div>

        <div class="chrmrtns-2fa-step">
            <h4>🔑 <?php esc_html_e('Backup Codes', 'keyless-auth'); ?></h4>
            <p><?php esc_html_e('Use these codes if you lose access to your authenticator app. Each code can only be used once.', 'keyless-auth'); ?></p>

            <?php if ($backup_count > 0): ?>
                <div class="chrmrtns-2fa-backup-codes" id="chrmrtns-backup-codes" style="display: none;">
                    <h6><?php esc_html_e('Your Backup Codes:', 'keyless-auth'); ?></h6>
                    <div class="chrmrtns-backup-codes-list">
                        <p><em><?php esc_html_e('Backup codes are only shown once when first generated for security.', 'keyless-auth'); ?></em></p>
                    </div>
                </div>
                <button type="button" class="chrmrtns-kla-btn chrmrtns-kla-btn-secondary chrmrtns-kla-btn-small" id="chrmrtns-show-backup-codes">
                    <?php esc_html_e('View Backup Codes', 'keyless-auth'); ?>
                </button>
            <?php endif; ?>

            <button type="button" class="chrmrtns-kla-btn chrmrtns-kla-btn-primary" id="chrmrtns-generate-backup-codes">
                <?php $backup_count > 0 ? esc_html_e('Generate New Backup Codes', 'keyless-auth') : esc_html_e('Generate Backup Codes', 'keyless-auth'); ?>
            </button>

            <?php if ($backup_count > 0): ?>
                <p><small class="chrmrtns-help-text"><?php esc_html_e('⚠️ Generating new codes will invalidate all existing backup codes.', 'keyless-auth'); ?></small></p>
            <?php endif; ?>
        </div>

        <div class="chrmrtns-2fa-step">
            <h4><?php echo $role_required ? '🔒' : '⚙️'; ?> <?php esc_html_e('Disable 2FA', 'keyless-auth'); ?></h4>
            <?php if ($role_required): ?>
                <div class="chrmrtns-2fa-required-notice" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('2FA is required for your account role and cannot be disabled.', 'keyless-auth'); ?></strong></p>
                    <p><em><?php esc_html_e('Contact your site administrator if you need to disable 2FA.', 'keyless-auth'); ?></em></p>
                </div>
            <?php else: ?>
                <p><?php esc_html_e('Disabling 2FA will make your account less secure. Only disable if absolutely necessary.', 'keyless-auth'); ?></p>
                <button type="button" class="chrmrtns-kla-btn chrmrtns-kla-btn-danger" id="chrmrtns-disable-2fa">
                    <?php esc_html_e('Disable 2FA', 'keyless-auth'); ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="chrmrtns-2fa-info">
            <h4>💡 <?php esc_html_e('About Two-Factor Authentication', 'keyless-auth'); ?></h4>
            <p><?php esc_html_e('Two-factor authentication adds an extra layer of security by requiring both your password and a time-based code from your authenticator app. This protects your account even if your password is compromised.', 'keyless-auth'); ?></p>
            <p><strong><?php esc_html_e('Keep your backup codes safe!', 'keyless-auth'); ?></strong> <?php esc_html_e('Store them in a secure location like a password manager. You\'ll need them if you lose access to your authenticator app.', 'keyless-auth'); ?></p>
        </div>
        <?php
    }

    /**
     * Check if user's role requires 2FA
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_role_required($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());
        return !empty(array_intersect($user->roles, $required_roles));
    }

    /**
     * Handle AJAX 2FA setup
     */
    public function handle_ajax_setup() {
        // Verify nonce
        if (!isset($_POST['chrmrtns_2fa_setup_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_2fa_setup_nonce'])), 'chrmrtns_2fa_setup')) {
            wp_die(esc_html__('Security check failed.', 'keyless-auth'));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
                error_log('Keyless Auth: User not logged in');
            }
            wp_die(esc_html__('You must be logged in.', 'keyless-auth'));
        }

        $user_id = get_current_user_id();

        if (!isset($_POST['secret'], $_POST['verification_code'])) {
            wp_send_json_error(__('Missing required fields.', 'keyless-auth'));
        }

        $secret = sanitize_text_field(wp_unslash($_POST['secret']));
        $code = sanitize_text_field(wp_unslash($_POST['verification_code']));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log("Keyless Auth: Setup attempt for user $user_id, secret: " . substr($secret, 0, 10) . "..., code: $code");
        }

        // Validate code format
        if (!$this->totp->is_valid_code_format($code)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
                error_log('Keyless Auth: Invalid code format');
            }
            wp_send_json_error(__('Please enter a 6-digit code.', 'keyless-auth'));
        }

        // Verify the code
        if (!$this->totp->verify_code($code, $secret)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
                error_log('Keyless Auth: Code verification failed');
            }
            wp_send_json_error(__('Invalid verification code. Please try again.', 'keyless-auth'));
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log('Keyless Auth: Code verification successful');
        }

        // Generate backup codes
        $display_codes = $this->totp->get_display_backup_codes(10);
        $hashed_codes = $this->totp->hash_backup_codes($display_codes);

        // Enable 2FA
        $result = $this->database->enable_user_2fa($user_id, $secret, $hashed_codes);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('2FA enabled successfully!', 'keyless-auth'),
                'backup_codes' => $display_codes
            ));
        } else {
            wp_send_json_error(__('Failed to enable 2FA. Please try again.', 'keyless-auth'));
        }
    }

    /**
     * Handle AJAX 2FA disable
     */
    public function handle_ajax_disable() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_2fa_frontend')) {
            wp_die(esc_html__('Security check failed.', 'keyless-auth'));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in.', 'keyless-auth'));
        }

        $user_id = get_current_user_id();

        // Check if role requires 2FA
        if ($this->is_role_required($user_id)) {
            wp_send_json_error(__('2FA is required for your account role and cannot be disabled.', 'keyless-auth'));
        }

        // Disable 2FA
        $result = $this->database->disable_user_2fa($user_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('2FA has been disabled.', 'keyless-auth')
            ));
        } else {
            wp_send_json_error(__('Failed to disable 2FA. Please try again.', 'keyless-auth'));
        }
    }

    /**
     * Handle AJAX backup code generation
     */
    public function handle_ajax_generate_backup_codes() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_2fa_frontend')) {
            wp_die(esc_html__('Security check failed.', 'keyless-auth'));
        }

        // Must be logged in
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in.', 'keyless-auth'));
        }

        $user_id = get_current_user_id();

        // Check if user has 2FA enabled
        $settings = $this->database->get_user_2fa_settings($user_id);
        if (!$settings || !$settings->totp_enabled) {
            wp_send_json_error(__('2FA is not enabled for your account.', 'keyless-auth'));
        }

        // Generate new backup codes
        $display_codes = $this->totp->get_display_backup_codes(10);
        $hashed_codes = $this->totp->hash_backup_codes($display_codes);

        // Update database
        $result = $this->database->enable_user_2fa($user_id, $settings->totp_secret, $hashed_codes);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('New backup codes generated successfully!', 'keyless-auth'),
                'backup_codes' => $display_codes
            ));
        } else {
            wp_send_json_error(__('Failed to generate backup codes. Please try again.', 'keyless-auth'));
        }
    }
}
