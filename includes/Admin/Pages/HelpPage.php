<?php
/**
 * Help page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class HelpPage {

    /**
     * Render the help page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }
        ?>
        <div class="wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('Keyless Auth - Help & Instructions', 'keyless-auth'); ?>
            </h1>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Getting Started', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Keyless Auth allows your users to login without passwords using secure email magic links. Here\'s how to get started:', 'keyless-auth'); ?></p>

                <ol>
                    <li><strong><?php esc_html_e('Configure SMTP Settings:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to SMTP tab and configure your email settings for reliable delivery.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Customize Email Templates:', 'keyless-auth'); ?></strong> <?php esc_html_e('Use the Templates tab to customize how your login emails look.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Add Login Form:', 'keyless-auth'); ?></strong> <?php esc_html_e('Use the shortcode [keyless-auth] on any page or post.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Enable wp-login.php (Optional):', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options to add magic login to the WordPress login page.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Enable Two-Factor Authentication (Optional):', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options to enable 2FA system and use [keyless-auth-2fa] shortcode for user setup.', 'keyless-auth'); ?></li>
                </ol>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Available Shortcodes', 'keyless-auth'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Shortcode', 'keyless-auth'); ?></th>
                            <th><?php esc_html_e('Description', 'keyless-auth'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[keyless-auth]</code></td>
                            <td><?php esc_html_e('Main passwordless login form (magic link only). Supports attributes: redirect, button_text, description, label', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[keyless-auth-full]</code></td>
                            <td><?php esc_html_e('Complete login form with both password and magic link options. Supports attributes: redirect, show_title, title_text', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[keyless-auth-2fa]</code></td>
                            <td><?php esc_html_e('Two-factor authentication setup and management interface (requires 2FA system to be enabled in Options)', 'keyless-auth'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Shortcode Usage Examples', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Here are some examples of how to use the shortcodes:', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('Basic Usage:', 'keyless-auth'); ?></h4>
                <p><code>[keyless-auth]</code> - <?php esc_html_e('Magic link login form only', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full]</code> - <?php esc_html_e('Both password and magic link options', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-2fa]</code> - <?php esc_html_e('2FA setup interface (when 2FA is enabled)', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('[keyless-auth] Options:', 'keyless-auth'); ?></h4>
                <p><code>[keyless-auth redirect="/dashboard/"]</code><br><?php esc_html_e('Redirect to dashboard after magic link login', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth button_text="Email login link"]</code><br><?php esc_html_e('Custom button text', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth label="Your Email"]</code><br><?php esc_html_e('Custom field label', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth description="Secure passwordless access"]</code><br><?php esc_html_e('Add description text above the form', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth button_text="Email login link" description="Secure passwordless access" label="Your Email" redirect="/dashboard/"]</code><br><?php esc_html_e('Combined options example', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('Advanced [keyless-auth-full] Options:', 'keyless-auth'); ?></h4>
                <p><code>[keyless-auth-full redirect="/dashboard/"]</code><br><?php esc_html_e('Redirect to dashboard after login', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full show_title="no"]</code><br><?php esc_html_e('Hide the main title', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full title_text="Member Login"]</code><br><?php esc_html_e('Custom title text', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full title_text="Member Login" redirect="/members/" show_title="yes"]</code><br><?php esc_html_e('Combined options example', 'keyless-auth'); ?></p>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('How It Works', 'keyless-auth'); ?></h2>
                <ol>
                    <li><?php esc_html_e('User enters their email address or username', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('System generates a secure, time-limited token', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Email is sent with a magic login link', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('User clicks the link and is automatically logged in', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Token expires after 10 minutes for security', 'keyless-auth'); ?></li>
                </ol>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Security Features', 'keyless-auth'); ?></h2>
                <ul>
                    <li><strong><?php esc_html_e('Token Expiration:', 'keyless-auth'); ?></strong> <?php esc_html_e('All login links expire after 10 minutes', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('One-Time Use:', 'keyless-auth'); ?></strong> <?php esc_html_e('Each token can only be used once', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('IP Tracking:', 'keyless-auth'); ?></strong> <?php esc_html_e('Login attempts are logged with IP addresses', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Device Fingerprinting:', 'keyless-auth'); ?></strong> <?php esc_html_e('Tracks device information for audit purposes', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Database Logging:', 'keyless-auth'); ?></strong> <?php esc_html_e('All attempts are logged for security analysis', 'keyless-auth'); ?></li>
                </ul>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Two-Factor Authentication (2FA)', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Add an extra layer of security with TOTP-based two-factor authentication using smartphone authenticator apps.', 'keyless-auth'); ?></p>

                <h3><?php esc_html_e('Setup Instructions', 'keyless-auth'); ?></h3>
                <ol>
                    <li><strong><?php esc_html_e('Enable 2FA System:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options → Enable 2FA System checkbox', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Configure Role Requirements:', 'keyless-auth'); ?></strong> <?php esc_html_e('Select user roles that require 2FA (optional)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Add User Interface:', 'keyless-auth'); ?></strong> <?php esc_html_e('Place [keyless-auth-2fa] shortcode on a page for user setup', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('User Setup:', 'keyless-auth'); ?></strong> <?php esc_html_e('Users scan QR code with authenticator app and verify setup', 'keyless-auth'); ?></li>
                </ol>

                <h3><?php esc_html_e('Supported Authenticator Apps', 'keyless-auth'); ?></h3>
                <ul>
                    <li><strong>Google Authenticator</strong> (iOS/Android)</li>
                    <li><strong>Authy</strong> (iOS/Android/Desktop)</li>
                    <li><strong>1Password</strong> (Premium users)</li>
                    <li><strong>Microsoft Authenticator</strong> (iOS/Android)</li>
                    <li><?php esc_html_e('Any RFC 6238 compliant TOTP app', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('Key Features', 'keyless-auth'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Universal Coverage:', 'keyless-auth'); ?></strong> <?php esc_html_e('Works with ALL login methods (magic links, passwords, SSO)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Backup Codes:', 'keyless-auth'); ?></strong> <?php esc_html_e('10 single-use recovery codes for emergency access', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Admin Controls:', 'keyless-auth'); ?></strong> <?php esc_html_e('Admins can disable 2FA for any user', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Grace Periods:', 'keyless-auth'); ?></strong> <?php esc_html_e('Configurable setup time for required users (1-30 days)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Failed Attempt Protection:', 'keyless-auth'); ?></strong> <?php esc_html_e('Automatic lockouts after too many failed attempts', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('API and Programmatic Access', 'keyless-auth'); ?></h3>
                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Important:', 'keyless-auth'); ?></strong> <?php esc_html_e('REST API and XML-RPC requests bypass 2FA when using Application Passwords.', 'keyless-auth'); ?></p>
                </div>
                <h4><?php esc_html_e('Application Password Requirements', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('For programmatic access to WordPress, you MUST use Application Passwords:', 'keyless-auth'); ?></p>
                <ul>
                    <li><strong><?php esc_html_e('REST API:', 'keyless-auth'); ?></strong> <?php esc_html_e('All REST API requests must authenticate using Application Passwords', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('XML-RPC:', 'keyless-auth'); ?></strong> <?php esc_html_e('XML-RPC requests must use Application Passwords (not regular passwords)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('WP-CLI:', 'keyless-auth'); ?></strong> <?php esc_html_e('Command-line tools automatically bypass 2FA', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Third-party Apps:', 'keyless-auth'); ?></strong> <?php esc_html_e('Mobile apps, CI/CD tools, integrations must use Application Passwords', 'keyless-auth'); ?></li>
                </ul>

                <h4><?php esc_html_e('How to Create Application Passwords', 'keyless-auth'); ?></h4>
                <ol>
                    <li><?php esc_html_e('Go to Users → Your Profile', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Scroll to "Application Passwords" section', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Enter a name for your application (e.g., "Mobile App", "API Script")', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Click "Add New Application Password"', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Copy the generated password and use it for API authentication', 'keyless-auth'); ?></li>
                </ol>

                <h4><?php esc_html_e('Authentication Methods Overview', 'keyless-auth'); ?></h4>
                <table class="wp-list-table widefat fixed striped" style="margin: 15px 0;">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php esc_html_e('Login Method', 'keyless-auth'); ?></th>
                            <th style="width: 20%;"><?php esc_html_e('2FA Required?', 'keyless-auth'); ?></th>
                            <th><?php esc_html_e('Notes', 'keyless-auth'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('Interactive Login', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(Web browser, admin panel)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #d63638;"><?php esc_html_e('YES', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('All interactive logins require 2FA when enabled (magic links, passwords, SSO)', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('REST API', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(with Application Passwords)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #2271b1;"><?php esc_html_e('NO', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Application Passwords provide separate secure authentication', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('XML-RPC', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(with Application Passwords)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #2271b1;"><?php esc_html_e('NO', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Must use Application Passwords, not regular passwords', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('WP-CLI', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(Command line)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #2271b1;"><?php esc_html_e('NO', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Automatically detected and bypassed', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Legacy API Access', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(using regular passwords)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #d63638;"><?php esc_html_e('BLOCKED', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Will fail - must upgrade to Application Passwords', 'keyless-auth'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="notice notice-warning inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Security Note:', 'keyless-auth'); ?></strong> <?php esc_html_e('Application Passwords are time-limited tokens that can be revoked individually. They provide better security than using regular passwords for API access.', 'keyless-auth'); ?></p>
                </div>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Appearance & Theme Settings', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Control how login forms appear in light and dark mode themes.', 'keyless-auth'); ?></p>

                <h3><?php esc_html_e('Dark Mode Behavior', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('You can control how login forms render in dark mode from the Options page. Three modes are available:', 'keyless-auth'); ?></p>

                <ul>
                    <li><strong><?php esc_html_e('Auto (Default):', 'keyless-auth'); ?></strong> <?php esc_html_e('Automatically detects system dark mode preference and theme dark mode classes. Forms adapt to match user\'s system settings and theme.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Light Only:', 'keyless-auth'); ?></strong> <?php esc_html_e('Forces light theme always, disables dark mode completely. Use this if you want consistent light appearance regardless of user preferences.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Dark Only:', 'keyless-auth'); ?></strong> <?php esc_html_e('Forces dark theme always. Use this if your site has a dark theme and you want forms to always match.', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('Where to configure:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options → Appearance & Theme Settings → Dark Mode Behavior', 'keyless-auth'); ?></p>

                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Performance Note:', 'keyless-auth'); ?></strong> <?php esc_html_e('CSS files only load when shortcodes are used on a page, saving bandwidth on pages without login forms.', 'keyless-auth'); ?></p>
                </div>

                <h3><?php esc_html_e('Theme Integration (Advanced)', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('For developers and advanced users: integrate Keyless Auth styles with your theme\'s color system using WordPress filter hooks.', 'keyless-auth'); ?></p>

                <p><strong><?php esc_html_e('Why use filters instead of custom CSS?', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('No !important needed - proper CSS cascade order', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Map plugin variables to your theme\'s existing CSS variables', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Automatic dark mode support when using theme variables', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Cleaner, more maintainable integration', 'keyless-auth'); ?></li>
                </ul>

                <h4><?php esc_html_e('Basic Example - Login Forms', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('Add this code to your theme\'s functions.php or a custom plugin:', 'keyless-auth'); ?></p>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px;"><code>&lt;?php
add_filter('chrmrtns_kla_custom_css_variables', function($css) {
    $customCSS = &lt;&lt;&lt;CSS
:root {
    --kla-primary: var(--my-theme-primary);
    --kla-background: var(--my-theme-bg);
    --kla-text: var(--my-theme-text);
}
CSS;
    return \$css . "\n" . \$customCSS;
});</code></pre>

                <h4><?php esc_html_e('Advanced Example - With Dark Mode', 'keyless-auth'); ?></h4>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px;"><code>&lt;?php
add_filter('chrmrtns_kla_custom_css_variables', function($css) {
    $customCSS = &lt;&lt;&lt;CSS
/* Light mode */
:root, :root.light-mode {
    --kla-primary: var(--primary);
    --kla-success: var(--success);
    --kla-background: var(--bg-body);
}

/* Dark mode */
:root.dark-mode {
    --kla-primary: var(--primary);
    --kla-background: var(--tertiary-5);
    --kla-text: var(--text);
}
CSS;
    return \$css . "\n" . \$customCSS;
});</code></pre>

                <h4><?php esc_html_e('2FA Page Integration', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('Use a separate filter for the 2FA management page:', 'keyless-auth'); ?></p>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px;"><code>&lt;?php
add_filter('chrmrtns_kla_2fa_custom_css_variables', function($css) {
    return \$css . ':root { --kla-primary: var(--my-theme-primary); }';
});</code></pre>

                <h4><?php esc_html_e('Available CSS Variables', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('You can override any of these variables:', 'keyless-auth'); ?></p>
                <ul style="column-count: 2; column-gap: 20px;">
                    <li><code>--kla-primary</code></li>
                    <li><code>--kla-primary-hover</code></li>
                    <li><code>--kla-primary-active</code></li>
                    <li><code>--kla-primary-light</code></li>
                    <li><code>--kla-success</code></li>
                    <li><code>--kla-success-hover</code></li>
                    <li><code>--kla-error</code></li>
                    <li><code>--kla-error-light</code></li>
                    <li><code>--kla-warning</code></li>
                    <li><code>--kla-warning-light</code></li>
                    <li><code>--kla-text</code></li>
                    <li><code>--kla-text-light</code></li>
                    <li><code>--kla-border</code></li>
                    <li><code>--kla-border-light</code></li>
                    <li><code>--kla-background</code></li>
                    <li><code>--kla-background-alt</code></li>
                </ul>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Security Settings', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Additional security options to harden your WordPress installation.', 'keyless-auth'); ?></p>

                <h3><?php esc_html_e('Disable XML-RPC', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('WordPress includes an XML-RPC interface (xmlrpc.php) that allows remote access to your site. While useful for some features, it\'s often targeted by attackers for brute force attacks.', 'keyless-auth'); ?></p>

                <p><strong><?php esc_html_e('When to disable XML-RPC:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('You don\'t use Jetpack or similar plugins that require XML-RPC', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You don\'t use WordPress mobile apps', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You don\'t need pingbacks or trackbacks', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You want to reduce your site\'s attack surface', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('When to keep XML-RPC enabled:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('You use Jetpack for stats, security, or other features', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You use WordPress mobile apps to manage your site', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You have third-party integrations that require XML-RPC', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('Where to configure:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options → Security Settings → Disable XML-RPC', 'keyless-auth'); ?></p>

                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Security Tip:', 'keyless-auth'); ?></strong> <?php esc_html_e('If you use REST API instead of XML-RPC, you can safely disable XML-RPC. Modern WordPress features use the REST API, which is more secure.', 'keyless-auth'); ?></p>
                </div>

                <h3><?php esc_html_e('Disable Application Passwords', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Application Passwords are special passwords used for authenticating to REST API and XML-RPC endpoints without using your main account password. Introduced in WordPress 5.6, they provide secure programmatic access.', 'keyless-auth'); ?></p>

                <p><strong><?php esc_html_e('When to disable Application Passwords:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('You don\'t use REST API authentication', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You don\'t use WordPress mobile apps', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You don\'t have CI/CD pipelines or automated deployments', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You don\'t use third-party integrations requiring API access', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You want maximum security and don\'t need programmatic access', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('When to keep Application Passwords enabled:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('You use WordPress mobile apps to manage your site', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You have automated scripts or tools that access your site via REST API', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You use third-party services that require API authentication', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You have CI/CD pipelines that deploy to WordPress', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Your 2FA is enabled and users need API access', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('Where to configure:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options → Security Settings → Disable Application Passwords', 'keyless-auth'); ?></p>

                <div class="notice notice-warning inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Important:', 'keyless-auth'); ?></strong> <?php esc_html_e('Disabling Application Passwords will break REST API and XML-RPC authentication. If you have 2FA enabled, this will prevent all programmatic access as regular passwords are blocked by 2FA.', 'keyless-auth'); ?></p>
                </div>

                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Recovery Note:', 'keyless-auth'); ?></strong> <?php esc_html_e('If you get locked out, you can always deactivate the Keyless Auth plugin via FTP to regain access and disable this setting.', 'keyless-auth'); ?></p>
                </div>

                <h3><?php esc_html_e('Prevent User Enumeration', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('User enumeration is a technique attackers use to discover valid usernames on your WordPress site. Once they have usernames, they can launch targeted brute force attacks. This feature blocks all common enumeration methods.', 'keyless-auth'); ?></p>

                <p><strong><?php esc_html_e('What this feature blocks:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><strong><?php esc_html_e('REST API User Endpoints:', 'keyless-auth'); ?></strong> <?php esc_html_e('Blocks /wp-json/wp/v2/users and /wp-json/wp/v2/users/{id} for non-logged-in users', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Author Archives:', 'keyless-auth'); ?></strong> <?php esc_html_e('Redirects author archive pages and ?author=N queries to homepage', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Login Error Messages:', 'keyless-auth'); ?></strong> <?php esc_html_e('Removes specific error messages that reveal whether username exists', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Comment Author Classes:', 'keyless-auth'); ?></strong> <?php esc_html_e('Removes comment-author-{username} CSS classes from comments', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('oEmbed Data:', 'keyless-auth'); ?></strong> <?php esc_html_e('Removes author name and URL from oEmbed responses', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('When to enable:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('You want to prevent username discovery attacks', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You don\'t need public author archives', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You want maximum security against brute force attacks', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Your site is a business/corporate site without author profiles', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('When to keep disabled:', 'keyless-auth'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('You run a multi-author blog with author profiles', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('You need author archives for SEO or navigation', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Third-party tools need access to user data via REST API', 'keyless-auth'); ?></li>
                </ul>

                <p><strong><?php esc_html_e('Where to configure:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options → Security Settings → Prevent User Enumeration', 'keyless-auth'); ?></p>

                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Security Tip:', 'keyless-auth'); ?></strong> <?php esc_html_e('Combine with strong passwords or magic link authentication for best security. User enumeration prevention makes brute force attacks significantly harder.', 'keyless-auth'); ?></p>
                </div>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Troubleshooting', 'keyless-auth'); ?></h2>
                <dl>
                    <dt><strong><?php esc_html_e('Emails not being sent?', 'keyless-auth'); ?></strong></dt>
                    <dd><?php esc_html_e('Check your SMTP settings and test with the built-in email tester. Make sure your hosting provider allows email sending.', 'keyless-auth'); ?></dd>

                    <dt><strong><?php esc_html_e('Login links not working?', 'keyless-auth'); ?></strong></dt>
                    <dd><?php esc_html_e('Verify that tokens haven\'t expired (10 minute limit) and check that the link hasn\'t been used already.', 'keyless-auth'); ?></dd>

                    <dt><strong><?php esc_html_e('Users not receiving emails?', 'keyless-auth'); ?></strong></dt>
                    <dd><?php esc_html_e('Check spam folders and verify the user\'s email address is correct. Consider configuring DKIM/SPF records.', 'keyless-auth'); ?></dd>
                </dl>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Advanced Configuration', 'keyless-auth'); ?></h2>
                <h3><?php esc_html_e('Developer Functions', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('For developers, these functions are available:', 'keyless-auth'); ?></p>
                <ul>
                    <li><code>do_shortcode('[keyless-auth]')</code> - <?php esc_html_e('Display login form in templates', 'keyless-auth'); ?></li>
                    <li><code>do_shortcode('[keyless-auth-2fa]')</code> - <?php esc_html_e('Display 2FA setup interface in templates', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('Custom Admin Notices', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('For developers extending Keyless Auth, you can create dismissible admin notifications using the Notices class:', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('Basic Usage', 'keyless-auth'); ?></h4>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>use Chrmrtns\KeylessAuth\Core\Notices;

// Create a simple info notice
new Notices(
    'my_plugin_notice',           // Unique ID
    '&lt;p&gt;Your custom message here!&lt;/p&gt;', // HTML message
    'notice notice-info',         // CSS classes
    '',                           // Start date (optional)
    ''                            // End date (optional)
);</code></pre>

                <h4><?php esc_html_e('Advanced Example with Date Range', 'keyless-auth'); ?></h4>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>// Show notice only between specific dates
new Notices(
    'holiday_promo',
    '&lt;p&gt;&lt;strong&gt;Holiday Special:&lt;/strong&gt; Premium features 50% off!&lt;/p&gt;',
    'notice notice-success',
    '2025-12-01',  // Show starting Dec 1
    '2025-12-31'   // Hide after Dec 31
);</code></pre>

                <h4><?php esc_html_e('Available Notice Styles', 'keyless-auth'); ?></h4>
                <ul>
                    <li><code>notice notice-info</code> - <?php esc_html_e('Blue informational notice', 'keyless-auth'); ?></li>
                    <li><code>notice notice-success</code> - <?php esc_html_e('Green success notice', 'keyless-auth'); ?></li>
                    <li><code>notice notice-warning</code> - <?php esc_html_e('Yellow warning notice', 'keyless-auth'); ?></li>
                    <li><code>notice notice-error</code> - <?php esc_html_e('Red error notice', 'keyless-auth'); ?></li>
                    <li><code>updated</code> - <?php esc_html_e('Legacy green success style', 'keyless-auth'); ?></li>
                </ul>

                <h4><?php esc_html_e('Available Hooks', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('The Notices class provides action hooks for custom functionality:', 'keyless-auth'); ?></p>
                <ul>
                    <li><code>{notice_id}_before_notification_displayed</code> - <?php esc_html_e('Fires before notice is shown', 'keyless-auth'); ?></li>
                    <li><code>{notice_id}_notification_displayed</code> - <?php esc_html_e('Fires after notice is shown to admin', 'keyless-auth'); ?></li>
                    <li><code>{notice_id}_after_notification_displayed</code> - <?php esc_html_e('Fires after notice display logic completes', 'keyless-auth'); ?></li>
                    <li><code>{notice_id}_before_notification_dismissed</code> - <?php esc_html_e('Fires when user clicks dismiss', 'keyless-auth'); ?></li>
                    <li><code>{notice_id}_after_notification_dismissed</code> - <?php esc_html_e('Fires after dismiss is saved', 'keyless-auth'); ?></li>
                </ul>

                <p><?php esc_html_e('You can also filter the message content:', 'keyless-auth'); ?></p>
                <ul>
                    <li><code>{notice_id}_notification_message</code> - <?php esc_html_e('Modify the notice HTML before display', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('Database Tables', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Keyless Auth creates these custom tables for optimal performance:', 'keyless-auth'); ?></p>
                <ul>
                    <li><code>wp_chrmrtns_kla_login_logs</code> - <?php esc_html_e('Login attempt tracking', 'keyless-auth'); ?></li>
                    <li><code>wp_chrmrtns_kla_mail_logs</code> - <?php esc_html_e('Email sending logs', 'keyless-auth'); ?></li>
                    <li><code>wp_chrmrtns_kla_login_tokens</code> - <?php esc_html_e('Secure token storage', 'keyless-auth'); ?></li>
                    <li><code>wp_chrmrtns_kla_user_devices</code> - <?php esc_html_e('Device fingerprinting', 'keyless-auth'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
