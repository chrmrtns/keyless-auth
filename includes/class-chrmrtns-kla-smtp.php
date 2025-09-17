<?php
/**
 * SMTP functionality for Passwordless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_KLA_SMTP {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init_smtp_settings'));
        add_action('phpmailer_init', array($this, 'configure_phpmailer'));
    }
    
    /**
     * Initialize SMTP settings
     */
    public function init_smtp_settings() {
        register_setting('chrmrtns_kla_smtp_settings_group', 'chrmrtns_kla_smtp_settings', array($this, 'sanitize_smtp_settings'));

        add_settings_section(
            'chrmrtns_kla_smtp_settings_section',
            __('SMTP Settings', 'keyless-auth'),
            array($this, 'smtp_settings_section_callback'),
            'chrmrtns-kla-smtp-settings'
        );

        $this->add_smtp_fields();
    }
    
    /**
     * Add SMTP settings fields
     */
    private function add_smtp_fields() {
        $fields = array(
            'enable_smtp' => __('Enable SMTP', 'keyless-auth'),
            'force_from_name' => __('Force From Name', 'keyless-auth'),
            'from_name' => __('From Name', 'keyless-auth'),
            'smtp_host' => __('SMTP Host', 'keyless-auth'),
            'smtp_encryption' => __('Encryption', 'keyless-auth'),
            'smtp_port' => __('SMTP Port', 'keyless-auth'),
            'smtp_auth' => __('Authentication', 'keyless-auth'),
            'credential_storage' => __('Credential Storage', 'keyless-auth'),
            'smtp_username' => __('SMTP Username', 'keyless-auth'),
            'smtp_password' => __('SMTP Password', 'keyless-auth')
        );
        
        foreach ($fields as $field_id => $field_title) {
            add_settings_field(
                $field_id,
                $field_title,
                array($this, 'render_' . $field_id . '_field'),
                'chrmrtns-kla-smtp-settings',
                'chrmrtns_kla_smtp_settings_section'
            );
        }
    }
    
    /**
     * SMTP settings section callback
     */
    public function smtp_settings_section_callback() {
        echo '<p>' . esc_html__('Configure your SMTP settings below. Test your configuration using the test email feature.', 'keyless-auth') . '</p>';
    }
    
    /**
     * Render enable SMTP field
     */
    public function render_enable_smtp_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $checked = isset($options['enable_smtp']) && $options['enable_smtp'] ? 'checked' : '';
        ?>
        <input type='checkbox' name='chrmrtns_kla_smtp_settings[enable_smtp]' <?php echo esc_attr($checked); ?> value='1'>
        <p class="description"><?php esc_html_e('Enable SMTP for email delivery instead of PHP mail().', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render force from name field
     */
    public function render_force_from_name_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $checked = isset($options['force_from_name']) && $options['force_from_name'] ? 'checked' : '';
        ?>
        <input type='checkbox' name='chrmrtns_kla_smtp_settings[force_from_name]' <?php echo esc_attr($checked); ?> value='1'>
        <p class="description"><?php esc_html_e('Force a custom "From" name for all emails sent via SMTP.', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render from name field
     */
    public function render_from_name_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        ?>
        <input type='text' name='chrmrtns_kla_smtp_settings[from_name]' 
            value='<?php echo esc_attr($options['from_name'] ?? ''); ?>' 
            size='50' 
            placeholder="Your Website Name">
        <p class="description"><?php esc_html_e('The name that will appear as the sender (e.g., "Your Website Name").', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP host field
     */
    public function render_smtp_host_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        ?>
        <input type='text' name='chrmrtns_kla_smtp_settings[smtp_host]' 
            value='<?php echo esc_attr($options['smtp_host'] ?? ''); ?>' 
            size='50' 
            placeholder="smtp.gmail.com">
        <p class="description"><?php esc_html_e('Your SMTP server hostname (e.g., smtp.gmail.com, smtp.mailgun.org).', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render encryption field
     */
    public function render_smtp_encryption_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $current = $options['smtp_encryption'] ?? 'none';
        ?>
        <select name='chrmrtns_kla_smtp_settings[smtp_encryption]'>
            <option value='none' <?php selected($current, 'none'); ?>><?php esc_html_e('None', 'keyless-auth'); ?></option>
            <option value='ssl' <?php selected($current, 'ssl'); ?>><?php esc_html_e('SSL', 'keyless-auth'); ?></option>
            <option value='tls' <?php selected($current, 'tls'); ?>><?php esc_html_e('TLS', 'keyless-auth'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Encryption type. SSL uses port 465, TLS uses port 587.', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP port field
     */
    public function render_smtp_port_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        ?>
        <input type='number' name='chrmrtns_kla_smtp_settings[smtp_port]' 
            value='<?php echo esc_attr($options['smtp_port'] ?? '25'); ?>' 
            min='1' max='65535' 
            size='10'>
        <p class="description"><?php esc_html_e('SMTP port number. Common ports: 25 (none), 587 (TLS), 465 (SSL).', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render authentication field
     */
    public function render_smtp_auth_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $checked = isset($options['smtp_auth']) && $options['smtp_auth'] ? 'checked' : '';
        ?>
        <input type='checkbox' name='chrmrtns_kla_smtp_settings[smtp_auth]' <?php echo esc_attr($checked); ?> value='1'>
        <p class="description"><?php esc_html_e('Enable SMTP authentication (recommended for most providers).', 'keyless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render credential storage field
     */
    public function render_credential_storage_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $storage_type = $options['credential_storage'] ?? 'database';
        ?>
        <label>
            <input type='radio' name='chrmrtns_kla_smtp_settings[credential_storage]' value='database' 
                <?php checked($storage_type, 'database'); ?>>
            <?php esc_html_e('Store in Database', 'keyless-auth'); ?>
        </label><br>
        
        <label>
            <input type='radio' name='chrmrtns_kla_smtp_settings[credential_storage]' value='wp_config' 
                <?php checked($storage_type, 'wp_config'); ?>>
            <?php esc_html_e('Store in wp-config.php', 'keyless-auth'); ?>
        </label>
        
        <p class="description">
            <?php esc_html_e('Choose where to store SMTP credentials. wp-config.php is more secure as it\'s outside the web root.', 'keyless-auth'); ?>
        </p>
        
        <?php
        // Show instructions if wp_config is selected
        $show_instructions = ($storage_type === 'wp_config') ? 'block' : 'none';
        ?>
        <div id="wp-config-instructions" style="display: <?php echo esc_attr($show_instructions); ?>; margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #0073aa;">
            <strong><?php esc_html_e('To use wp-config.php storage, add these lines to your wp-config.php file:', 'keyless-auth'); ?></strong><br><br>
            <code style="background: #fff; padding: 5px; display: block; margin: 5px 0;">
                define('CHRMRTNS_KLA_SMTP_USERNAME', 'your-email@example.com');<br>
                define('CHRMRTNS_KLA_SMTP_PASSWORD', 'your-smtp-password');
            </code>
            <small><?php esc_html_e('Replace the values with your actual SMTP credentials. The fields below will be disabled when using wp-config.php storage.', 'keyless-auth'); ?></small>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Inline credential storage toggle for reliability
            $('input[name="chrmrtns_kla_smtp_settings[credential_storage]"]').on('change', function() {
                if ($(this).val() === 'wp_config' && $(this).is(':checked')) {
                    $('#wp-config-instructions').show();
                } else if ($(this).val() === 'database' && $(this).is(':checked')) {
                    $('#wp-config-instructions').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render username field
     */
    public function render_smtp_username_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $storage_type = $options['credential_storage'] ?? 'database';
        $username = '';
        
        if ($storage_type === 'wp_config' && defined('CHRMRTNS_KLA_SMTP_USERNAME')) {
            $username = CHRMRTNS_KLA_SMTP_USERNAME;
        } else {
            $username = $options['smtp_username'] ?? '';
        }
        ?>
        <input type='text' name='chrmrtns_kla_smtp_settings[smtp_username]' 
            value='<?php echo esc_attr($username); ?>' 
            size='50' 
            placeholder="your-email@gmail.com">
        
        <?php if ($storage_type === 'wp_config'): ?>
            <p class="description" style="color: #0073aa;">
                <?php if (defined('CHRMRTNS_KLA_SMTP_USERNAME')): ?>
                    <?php esc_html_e('✓ Using username from wp-config.php', 'keyless-auth'); ?>
                <?php else: ?>
                    <?php esc_html_e('⚠ CHRMRTNS_KLA_SMTP_USERNAME not defined in wp-config.php', 'keyless-auth'); ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="description"><?php esc_html_e('Your SMTP username (usually your email address).', 'keyless-auth'); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render password field
     */
    public function render_smtp_password_field() {
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        $storage_type = $options['credential_storage'] ?? 'database';
        $password = '';
        
        if ($storage_type === 'wp_config' && defined('CHRMRTNS_KLA_SMTP_PASSWORD')) {
            $password = str_repeat('*', strlen(CHRMRTNS_KLA_SMTP_PASSWORD)); // Mask the password
        } else {
            $password = $options['smtp_password'] ?? '';
        }
        ?>
        <input type='password' name='chrmrtns_kla_smtp_settings[smtp_password]' 
            value='<?php echo esc_attr($password); ?>' 
            size='50'>
        
        <?php if ($storage_type === 'wp_config'): ?>
            <p class="description" style="color: #0073aa;">
                <?php if (defined('CHRMRTNS_KLA_SMTP_PASSWORD')): ?>
                    <?php esc_html_e('✓ Using password from wp-config.php', 'keyless-auth'); ?>
                <?php else: ?>
                    <?php esc_html_e('⚠ CHRMRTNS_KLA_SMTP_PASSWORD not defined in wp-config.php', 'keyless-auth'); ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="description"><?php esc_html_e('Your SMTP password or app-specific password.', 'keyless-auth'); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Sanitize SMTP settings
     */
    public function sanitize_smtp_settings($input) {
        $sanitized = array();

        // Clear any cached values before saving
        wp_cache_delete('chrmrtns_kla_smtp_settings', 'options');
        wp_cache_delete('alloptions', 'options');

        // Sanitize each field
        $sanitized['enable_smtp'] = isset($input['enable_smtp']) ? 1 : 0;
        $sanitized['force_from_name'] = isset($input['force_from_name']) ? 1 : 0;
        $sanitized['from_name'] = isset($input['from_name']) ? sanitize_text_field($input['from_name']) : '';
        $sanitized['from_email'] = isset($input['from_email']) ? sanitize_email($input['from_email']) : '';
        $sanitized['smtp_host'] = isset($input['smtp_host']) ? sanitize_text_field($input['smtp_host']) : '';
        $sanitized['smtp_encryption'] = isset($input['smtp_encryption']) ? sanitize_text_field($input['smtp_encryption']) : 'none';
        $sanitized['smtp_port'] = isset($input['smtp_port']) ? absint($input['smtp_port']) : 25;
        $sanitized['smtp_auth'] = isset($input['smtp_auth']) ? 1 : 0;
        $sanitized['credential_storage'] = isset($input['credential_storage']) ? sanitize_text_field($input['credential_storage']) : 'database';
        
        // Handle credentials based on storage type
        if ($sanitized['credential_storage'] === 'wp_config') {
            // Don't store credentials in database when using wp-config
            $sanitized['smtp_username'] = '';
            $sanitized['smtp_password'] = '';
        } else {
            // Store in database as usual
            $sanitized['smtp_username'] = isset($input['smtp_username']) ? sanitize_email($input['smtp_username']) : '';
            $sanitized['smtp_password'] = isset($input['smtp_password']) ? $input['smtp_password'] : ''; // Don't sanitize password to preserve special chars
        }
        
        // Validate port range
        if ($sanitized['smtp_port'] < 1 || $sanitized['smtp_port'] > 65535) {
            $sanitized['smtp_port'] = 25;
            add_settings_error('chrmrtns_kla_smtp_settings', 'invalid_port', __('Invalid port number. Using default port 25.', 'keyless-auth'), 'error');
        }
        
        // Validate encryption options
        $valid_encryptions = array('none', 'ssl', 'tls');
        if (!in_array($sanitized['smtp_encryption'], $valid_encryptions)) {
            $sanitized['smtp_encryption'] = 'none';
        }
        
        // Validate credential storage options
        $valid_storage_types = array('database', 'wp_config');
        if (!in_array($sanitized['credential_storage'], $valid_storage_types)) {
            $sanitized['credential_storage'] = 'database';
        }
        
        return $sanitized;
    }
    
    /**
     * Configure PHPMailer for SMTP
     */
    public function configure_phpmailer($phpmailer) {
        // Force fresh read from database, bypassing cache
        wp_cache_delete('chrmrtns_kla_smtp_settings', 'options');
        wp_cache_delete('alloptions', 'options');

        $options = get_option('chrmrtns_kla_smtp_settings', array());

        if (empty($options['enable_smtp'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $options['smtp_host'] ?? '';
        $phpmailer->Port = $options['smtp_port'] ?? 25;
        
        // Set encryption
        if (!empty($options['smtp_encryption']) && $options['smtp_encryption'] !== 'none') {
            $phpmailer->SMTPSecure = $options['smtp_encryption'];
        }
        
        // Set authentication
        if (!empty($options['smtp_auth'])) {
            $phpmailer->SMTPAuth = true;
            
            // Get credentials based on storage type
            $storage_type = $options['credential_storage'] ?? 'database';
            if ($storage_type === 'wp_config') {
                $phpmailer->Username = defined('CHRMRTNS_KLA_SMTP_USERNAME') ? CHRMRTNS_KLA_SMTP_USERNAME : '';
                $phpmailer->Password = defined('CHRMRTNS_KLA_SMTP_PASSWORD') ? CHRMRTNS_KLA_SMTP_PASSWORD : '';
            } else {
                $phpmailer->Username = $options['smtp_username'] ?? '';
                $phpmailer->Password = $options['smtp_password'] ?? '';
            }
        }
        
        // Force From Name if enabled
        if (!empty($options['force_from_name']) && !empty($options['from_name'])) {
            $phpmailer->FromName = $options['from_name'];
        }
        
        // Set the From email address (use from_email field if specified, otherwise SMTP username)
        if (!empty($phpmailer->Username)) {
            $from_email = !empty($options['from_email']) ? $options['from_email'] : $phpmailer->Username;
            $phpmailer->From = $from_email;

            // Set the Message-ID domain to match the From email domain for better deliverability
            // This improves SPF/DKIM/DMARC alignment
            $from_parts = explode('@', $from_email);
            if (count($from_parts) === 2) {
                $phpmailer->Hostname = $from_parts[1]; // This affects how Message-ID is generated
            }
        }

        // Additional settings
        $phpmailer->SMTPDebug = 0; // Disable debug output
        $phpmailer->Timeout = 30; // 30 seconds timeout
    }
    
    /**
     * Check port availability
     * Note: Uses fsockopen() for network connectivity testing - this is legitimate use
     * for testing SMTP server connectivity and cannot be replaced with WP_Filesystem
     */
    public function check_port_availability($host, $port) {
        // Sanitize inputs
        $host = sanitize_text_field($host);
        $port = absint($port);
        
        if (empty($host) || $port <= 0) {
            return false;
        }
        
        $errno = 0;
        $errstr = '';
        $timeout = 3; // seconds
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fsockopen -- Required for network connectivity testing
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (is_resource($connection)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Required for closing network connection
            fclose($connection);
            return true;
        }

        // Only log errors in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
            error_log("CHRMRTNS SMTP: Could not connect to $host on port $port. Error ($errno): $errstr");
        }
        return false;
    }
    
    /**
     * Handle cache clearing
     */
    public function handle_cache_clear() {
        if (!isset($_POST['chrmrtns_kla_smtp_clear_cache'])) {
            return;
        }

        if (!check_admin_referer('chrmrtns_kla_smtp_clear_cache_action', 'chrmrtns_kla_smtp_clear_cache_nonce')) {
            add_settings_error(
                'chrmrtns_kla_smtp_cache',
                'chrmrtns_kla_smtp_nonce_failed',
                __('Security check failed. Please refresh the page and try again.', 'keyless-auth'),
                'error'
            );
            return;
        }

        // Clear all related caches
        wp_cache_delete('chrmrtns_kla_smtp_settings', 'options');
        wp_cache_delete('alloptions', 'options');

        // Force refresh the option
        delete_transient('chrmrtns_kla_smtp_settings');

        // Optionally flush object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        add_settings_error(
            'chrmrtns_kla_smtp_cache',
            'chrmrtns_kla_smtp_cache_cleared',
            __('SMTP settings cache has been cleared successfully.', 'keyless-auth'),
            'updated'
        );
    }

    /**
     * Handle test email submission
     */
    public function handle_test_email_submission() {
        if (!isset($_POST['chrmrtns_kla_smtp_send_test_email'])) {
            return;
        }

        if (!check_admin_referer('chrmrtns_kla_smtp_send_test_email_action', 'chrmrtns_kla_smtp_send_test_email_nonce')) {
            add_settings_error(
                'chrmrtns_kla_smtp_test_email',
                'chrmrtns_kla_smtp_nonce_failed',
                __('Security check failed. Please refresh the page and try again.', 'keyless-auth'),
                'error'
            );
            return;
        }

        $to = isset($_POST['test_email_address']) ? sanitize_email(wp_unslash($_POST['test_email_address'])) : '';
        
        if (empty($to)) {
            $to = get_option('admin_email');
        }

        // Check if SMTP is enabled and test connection
        $options = get_option('chrmrtns_kla_smtp_settings', array());
        if (!empty($options['enable_smtp'])) {
            $host = $options['smtp_host'] ?? '';
            $port = $options['smtp_port'] ?? 25;

            if (!$this->check_port_availability($host, $port)) {
                add_settings_error(
                    'chrmrtns_kla_smtp_test_email',
                    'chrmrtns_kla_smtp_port_blocked',
                    sprintf(
                        /* translators: %1$s: SMTP hostname, %2$d: port number */
                        __('Could not connect to %1$s on port %2$d. It may be blocked by your hosting environment.', 'keyless-auth'),
                        esc_html($host),
                        esc_html($port)
                    ),
                    'error'
                );
                return;
            }
        }

        $subject = __('SMTP Test Email - Passwordless Auth', 'keyless-auth');
        $message = __('This is a test email sent via your SMTP settings from the Passwordless Auth plugin.', 'keyless-auth');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            add_settings_error(
                'chrmrtns_kla_smtp_test_email',
                'chrmrtns_kla_smtp_test_email_success',
                sprintf(
                    /* translators: %s: recipient email address */
                    __('Test email sent successfully to %s! Check the inbox.', 'keyless-auth'),
                    esc_html($to)
                ),
                'updated'
            );
        } else {
            add_settings_error(
                'chrmrtns_kla_smtp_test_email',
                'chrmrtns_kla_smtp_test_email_failed',
                __('Failed to send test email. Check your SMTP settings or server logs for more information.', 'keyless-auth'),
                'error'
            );
        }
    }
}