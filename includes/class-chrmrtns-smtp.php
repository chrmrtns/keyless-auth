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

class Chrmrtns_SMTP {
    
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
        register_setting('chrmrtns_smtp_settings_group', 'chrmrtns_smtp_settings', array($this, 'sanitize_smtp_settings'));

        add_settings_section(
            'chrmrtns_smtp_settings_section',
            __('SMTP Settings', 'passwordless-auth'),
            array($this, 'smtp_settings_section_callback'),
            'chrmrtns-smtp-settings'
        );

        $this->add_smtp_fields();
    }
    
    /**
     * Add SMTP settings fields
     */
    private function add_smtp_fields() {
        $fields = array(
            'enable_smtp' => __('Enable SMTP', 'passwordless-auth'),
            'force_from_name' => __('Force From Name', 'passwordless-auth'),
            'from_name' => __('From Name', 'passwordless-auth'),
            'smtp_host' => __('SMTP Host', 'passwordless-auth'),
            'smtp_encryption' => __('Encryption', 'passwordless-auth'),
            'smtp_port' => __('SMTP Port', 'passwordless-auth'),
            'smtp_auth' => __('Authentication', 'passwordless-auth'),
            'credential_storage' => __('Credential Storage', 'passwordless-auth'),
            'smtp_username' => __('SMTP Username', 'passwordless-auth'),
            'smtp_password' => __('SMTP Password', 'passwordless-auth')
        );
        
        foreach ($fields as $field_id => $field_title) {
            add_settings_field(
                $field_id,
                $field_title,
                array($this, 'render_' . $field_id . '_field'),
                'chrmrtns-smtp-settings',
                'chrmrtns_smtp_settings_section'
            );
        }
    }
    
    /**
     * SMTP settings section callback
     */
    public function smtp_settings_section_callback() {
        echo '<p>' . esc_html__('Configure your SMTP settings below. Test your configuration using the test email feature.', 'passwordless-auth') . '</p>';
    }
    
    /**
     * Render enable SMTP field
     */
    public function render_enable_smtp_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $checked = isset($options['enable_smtp']) && $options['enable_smtp'] ? 'checked' : '';
        ?>
        <input type='checkbox' name='chrmrtns_smtp_settings[enable_smtp]' <?php echo esc_attr($checked); ?> value='1'>
        <p class="description"><?php esc_html_e('Enable SMTP for email delivery instead of PHP mail().', 'passwordless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render force from name field
     */
    public function render_force_from_name_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $checked = isset($options['force_from_name']) && $options['force_from_name'] ? 'checked' : '';
        ?>
        <input type='checkbox' name='chrmrtns_smtp_settings[force_from_name]' <?php echo esc_attr($checked); ?> value='1' onchange="chrmrtnsToggleFromName(this.checked)">
        <p class="description"><?php esc_html_e('Force a custom "From" name for all emails sent via SMTP.', 'passwordless-auth'); ?></p>
        
        <script>
        function chrmrtnsToggleFromName(enabled) {
            var fromNameField = document.querySelector('input[name="chrmrtns_smtp_settings[from_name]"]').closest('tr');
            fromNameField.style.display = enabled ? 'table-row' : 'none';
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            var checkbox = document.querySelector('input[name="chrmrtns_smtp_settings[force_from_name]"]');
            if (checkbox) {
                chrmrtnsToggleFromName(checkbox.checked);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render from name field
     */
    public function render_from_name_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        ?>
        <input type='text' name='chrmrtns_smtp_settings[from_name]' 
            value='<?php echo esc_attr($options['from_name'] ?? ''); ?>' 
            size='50' 
            placeholder="Your Website Name">
        <p class="description"><?php esc_html_e('The name that will appear as the sender (e.g., "Your Website Name").', 'passwordless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP host field
     */
    public function render_smtp_host_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        ?>
        <input type='text' name='chrmrtns_smtp_settings[smtp_host]' 
            value='<?php echo esc_attr($options['smtp_host'] ?? ''); ?>' 
            size='50' 
            placeholder="smtp.gmail.com">
        <p class="description"><?php esc_html_e('Your SMTP server hostname (e.g., smtp.gmail.com, smtp.mailgun.org).', 'passwordless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render encryption field
     */
    public function render_smtp_encryption_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $current = $options['smtp_encryption'] ?? 'none';
        ?>
        <select name='chrmrtns_smtp_settings[smtp_encryption]' onchange="chrmrtnsUpdatePort(this.value)">
            <option value='none' <?php selected($current, 'none'); ?>><?php esc_html_e('None', 'passwordless-auth'); ?></option>
            <option value='ssl' <?php selected($current, 'ssl'); ?>><?php esc_html_e('SSL', 'passwordless-auth'); ?></option>
            <option value='tls' <?php selected($current, 'tls'); ?>><?php esc_html_e('TLS', 'passwordless-auth'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Encryption type. SSL uses port 465, TLS uses port 587.', 'passwordless-auth'); ?></p>
        
        <script>
        function chrmrtnsUpdatePort(encryption) {
            var portField = document.querySelector('input[name="chrmrtns_smtp_settings[smtp_port]"]');
            if (encryption === 'ssl') {
                portField.value = '465';
            } else if (encryption === 'tls') {
                portField.value = '587';
            } else {
                portField.value = '25';
            }
        }
        </script>
        <?php
    }
    
    /**
     * Render SMTP port field
     */
    public function render_smtp_port_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        ?>
        <input type='number' name='chrmrtns_smtp_settings[smtp_port]' 
            value='<?php echo esc_attr($options['smtp_port'] ?? '25'); ?>' 
            min='1' max='65535' 
            size='10'>
        <p class="description"><?php esc_html_e('SMTP port number. Common ports: 25 (none), 587 (TLS), 465 (SSL).', 'passwordless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render authentication field
     */
    public function render_smtp_auth_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $checked = isset($options['smtp_auth']) && $options['smtp_auth'] ? 'checked' : '';
        ?>
        <input type='checkbox' name='chrmrtns_smtp_settings[smtp_auth]' <?php echo esc_attr($checked); ?> value='1'>
        <p class="description"><?php esc_html_e('Enable SMTP authentication (recommended for most providers).', 'passwordless-auth'); ?></p>
        <?php
    }
    
    /**
     * Render credential storage field
     */
    public function render_credential_storage_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $storage_type = $options['credential_storage'] ?? 'database';
        ?>
        <label>
            <input type='radio' name='chrmrtns_smtp_settings[credential_storage]' value='database' 
                <?php checked($storage_type, 'database'); ?> onchange="chrmrtnsToggleCredentialFields('database')">
            <?php esc_html_e('Store in Database', 'passwordless-auth'); ?>
        </label><br>
        
        <label>
            <input type='radio' name='chrmrtns_smtp_settings[credential_storage]' value='wp_config' 
                <?php checked($storage_type, 'wp_config'); ?> onchange="chrmrtnsToggleCredentialFields('wp_config')">
            <?php esc_html_e('Store in wp-config.php', 'passwordless-auth'); ?>
        </label>
        
        <p class="description">
            <?php esc_html_e('Choose where to store SMTP credentials. wp-config.php is more secure as it\'s outside the web root.', 'passwordless-auth'); ?>
        </p>
        
        <div id="wp-config-instructions" style="display: none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #0073aa;">
            <strong><?php esc_html_e('To use wp-config.php storage, add these lines to your wp-config.php file:', 'passwordless-auth'); ?></strong><br><br>
            <code style="background: #fff; padding: 5px; display: block; margin: 5px 0;">
                define('CHRMRTNS_PA_SMTP_USERNAME', 'your-email@example.com');<br>
                define('CHRMRTNS_PA_SMTP_PASSWORD', 'your-smtp-password');
            </code>
            <small><?php esc_html_e('Replace the values with your actual SMTP credentials. The fields below will be disabled when using wp-config.php storage.', 'passwordless-auth'); ?></small>
        </div>
        
        <script>
        function chrmrtnsToggleCredentialFields(storageType) {
            var usernameField = document.querySelector('input[name="chrmrtns_smtp_settings[smtp_username]"]');
            var passwordField = document.querySelector('input[name="chrmrtns_smtp_settings[smtp_password]"]');
            var instructions = document.getElementById('wp-config-instructions');
            
            if (storageType === 'wp_config') {
                usernameField.disabled = true;
                passwordField.disabled = true;
                usernameField.style.opacity = '0.5';
                passwordField.style.opacity = '0.5';
                instructions.style.display = 'block';
            } else {
                usernameField.disabled = false;
                passwordField.disabled = false;
                usernameField.style.opacity = '1';
                passwordField.style.opacity = '1';
                instructions.style.display = 'none';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            var wpConfigRadio = document.querySelector('input[name="chrmrtns_smtp_settings[credential_storage]"][value="wp_config"]');
            if (wpConfigRadio && wpConfigRadio.checked) {
                chrmrtnsToggleCredentialFields('wp_config');
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render username field
     */
    public function render_smtp_username_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $storage_type = $options['credential_storage'] ?? 'database';
        $username = '';
        
        if ($storage_type === 'wp_config' && defined('CHRMRTNS_PA_SMTP_USERNAME')) {
            $username = CHRMRTNS_PA_SMTP_USERNAME;
        } else {
            $username = $options['smtp_username'] ?? '';
        }
        ?>
        <input type='text' name='chrmrtns_smtp_settings[smtp_username]' 
            value='<?php echo esc_attr($username); ?>' 
            size='50' 
            placeholder="your-email@gmail.com">
        
        <?php if ($storage_type === 'wp_config'): ?>
            <p class="description" style="color: #0073aa;">
                <?php if (defined('CHRMRTNS_PA_SMTP_USERNAME')): ?>
                    <?php esc_html_e('✓ Using username from wp-config.php', 'passwordless-auth'); ?>
                <?php else: ?>
                    <?php esc_html_e('⚠ CHRMRTNS_PA_SMTP_USERNAME not defined in wp-config.php', 'passwordless-auth'); ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="description"><?php esc_html_e('Your SMTP username (usually your email address).', 'passwordless-auth'); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render password field
     */
    public function render_smtp_password_field() {
        $options = get_option('chrmrtns_smtp_settings', array());
        $storage_type = $options['credential_storage'] ?? 'database';
        $password = '';
        
        if ($storage_type === 'wp_config' && defined('CHRMRTNS_PA_SMTP_PASSWORD')) {
            $password = str_repeat('*', strlen(CHRMRTNS_PA_SMTP_PASSWORD)); // Mask the password
        } else {
            $password = $options['smtp_password'] ?? '';
        }
        ?>
        <input type='password' name='chrmrtns_smtp_settings[smtp_password]' 
            value='<?php echo esc_attr($password); ?>' 
            size='50'>
        
        <?php if ($storage_type === 'wp_config'): ?>
            <p class="description" style="color: #0073aa;">
                <?php if (defined('CHRMRTNS_PA_SMTP_PASSWORD')): ?>
                    <?php esc_html_e('✓ Using password from wp-config.php', 'passwordless-auth'); ?>
                <?php else: ?>
                    <?php esc_html_e('⚠ CHRMRTNS_PA_SMTP_PASSWORD not defined in wp-config.php', 'passwordless-auth'); ?>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="description"><?php esc_html_e('Your SMTP password or app-specific password.', 'passwordless-auth'); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Sanitize SMTP settings
     */
    public function sanitize_smtp_settings($input) {
        $sanitized = array();
        
        // Sanitize each field
        $sanitized['enable_smtp'] = isset($input['enable_smtp']) ? 1 : 0;
        $sanitized['force_from_name'] = isset($input['force_from_name']) ? 1 : 0;
        $sanitized['from_name'] = isset($input['from_name']) ? sanitize_text_field($input['from_name']) : '';
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
            add_settings_error('chrmrtns_smtp_settings', 'invalid_port', __('Invalid port number. Using default port 25.', 'passwordless-auth'), 'error');
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
        $options = get_option('chrmrtns_smtp_settings', array());
        
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
                $phpmailer->Username = defined('CHRMRTNS_PA_SMTP_USERNAME') ? CHRMRTNS_PA_SMTP_USERNAME : '';
                $phpmailer->Password = defined('CHRMRTNS_PA_SMTP_PASSWORD') ? CHRMRTNS_PA_SMTP_PASSWORD : '';
            } else {
                $phpmailer->Username = $options['smtp_username'] ?? '';
                $phpmailer->Password = $options['smtp_password'] ?? '';
            }
        }
        
        // Force From Name if enabled
        if (!empty($options['force_from_name']) && !empty($options['from_name'])) {
            $phpmailer->FromName = $options['from_name'];
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
     * Handle test email submission
     */
    public function handle_test_email_submission() {
        if (!isset($_POST['chrmrtns_smtp_send_test_email'])) {
            return;
        }

        if (!check_admin_referer('chrmrtns_smtp_send_test_email_action', 'chrmrtns_smtp_send_test_email_nonce')) {
            add_settings_error(
                'chrmrtns_smtp_test_email',
                'chrmrtns_smtp_nonce_failed',
                __('Security check failed. Please refresh the page and try again.', 'passwordless-auth'),
                'error'
            );
            return;
        }

        $to = isset($_POST['test_email_address']) ? sanitize_email(wp_unslash($_POST['test_email_address'])) : '';
        
        if (empty($to)) {
            $to = get_option('admin_email');
        }

        // Check if SMTP is enabled and test connection
        $options = get_option('chrmrtns_smtp_settings', array());
        if (!empty($options['enable_smtp'])) {
            $host = $options['smtp_host'] ?? '';
            $port = $options['smtp_port'] ?? 25;

            if (!$this->check_port_availability($host, $port)) {
                add_settings_error(
                    'chrmrtns_smtp_test_email',
                    'chrmrtns_smtp_port_blocked',
                    sprintf(
                        /* translators: %1$s: SMTP hostname, %2$d: port number */
                        __('Could not connect to %1$s on port %2$d. It may be blocked by your hosting environment.', 'passwordless-auth'),
                        esc_html($host),
                        esc_html($port)
                    ),
                    'error'
                );
                return;
            }
        }

        $subject = __('SMTP Test Email - Passwordless Auth', 'passwordless-auth');
        $message = __('This is a test email sent via your SMTP settings from the Passwordless Auth plugin.', 'passwordless-auth');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            add_settings_error(
                'chrmrtns_smtp_test_email',
                'chrmrtns_smtp_test_email_success',
                sprintf(
                    /* translators: %s: recipient email address */
                    __('Test email sent successfully to %s! Check the inbox.', 'passwordless-auth'),
                    esc_html($to)
                ),
                'updated'
            );
        } else {
            add_settings_error(
                'chrmrtns_smtp_test_email',
                'chrmrtns_smtp_test_email_failed',
                __('Failed to send test email. Check your SMTP settings or server logs for more information.', 'passwordless-auth'),
                'error'
            );
        }
    }
}