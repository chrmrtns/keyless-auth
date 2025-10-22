<?php
/**
 * Email templates functionality for Keyless Auth
 * 
 * @since 2.0.1
 */



namespace Chrmrtns\KeylessAuth\Email;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


class Templates {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor is intentionally empty - methods are called as needed
    }

    /**
     * Save template settings
     *
     * Note: Nonce verification is performed in render_settings_page() before calling this method
     */
    public function save_template_settings() {
        // Check if reset_custom_template is set
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
        if (isset($_POST['reset_custom_template'])) {
            delete_option('chrmrtns_kla_custom_email_styles');
            delete_option('chrmrtns_kla_custom_email_html');
            update_option('chrmrtns_kla_email_template', 'default');
            add_settings_error('chrmrtns_kla_settings', 'settings_updated', esc_html__('Custom template has been reset successfully.', 'keyless-auth'), 'updated');
            return;
        }

        // Save template selection
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
        if (isset($_POST['chrmrtns_kla_email_template'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
            $template = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_email_template']));
            update_option('chrmrtns_kla_email_template', $template);
        }

        // Save color settings
        $color_fields = array(
            'chrmrtns_kla_button_color',
            'chrmrtns_kla_button_hover_color',
            'chrmrtns_kla_button_text_color',
            'chrmrtns_kla_button_hover_text_color',
            'chrmrtns_kla_link_color',
            'chrmrtns_kla_link_hover_color'
        );

        foreach ($color_fields as $field) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
            if (isset($_POST[$field])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
                $color = sanitize_text_field(wp_unslash($_POST[$field]));
                update_option($field, $color);
            }
        }

        // Save custom email styles
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
        if (isset($_POST['chrmrtns_kla_custom_email_styles'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
            $custom_styles = wp_kses_post(wp_unslash($_POST['chrmrtns_kla_custom_email_styles']));
            update_option('chrmrtns_kla_custom_email_styles', $custom_styles);
        }

        // Save custom email HTML if present
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
        if (isset($_POST['chrmrtns_kla_custom_email_html'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in render_settings_page() before calling this method
            $custom_html = wp_kses_post(wp_unslash($_POST['chrmrtns_kla_custom_email_html']));
            update_option('chrmrtns_kla_custom_email_html', $custom_html);
        }

        add_settings_error('chrmrtns_kla_settings', 'settings_updated', esc_html__('Settings saved successfully.', 'keyless-auth'), 'updated');
    }

    /**
     * Get email template content
     */
    public function get_email_template($to, $login_url) {
        $template = get_option('chrmrtns_kla_email_template', 'default');
        $button_color = get_option('chrmrtns_kla_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_kla_button_hover_color', '#0056b3');
        $button_text_color = get_option('chrmrtns_kla_button_text_color', '#ffffff');
        $button_hover_text_color = get_option('chrmrtns_kla_button_hover_text_color', '#ffffff');
        $link_color = get_option('chrmrtns_kla_link_color', '#007bff');
        $link_hover_color = get_option('chrmrtns_kla_link_hover_color', '#0056b3');
        
        switch ($template) {
            case 'german':
                return $this->get_german_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
            case 'simple':
                return $this->get_simple_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
            case 'custom':
                return $this->get_custom_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
            default:
                return $this->get_default_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
        }
    }
    
    /**
     * Default email template
     */
    private function get_default_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color) {
        $site_name = get_bloginfo('name');
        return sprintf('
            <html>
            <head>
                <style>
                    .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .login-button { display: inline-block; background-color: %s; color: %s; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 20px 0; }
                    .login-button:hover { background-color: %s; color: %s; }
                    a { color: %s; }
                    a:hover { color: %s; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <h2>Login to %s</h2>
                    <p>Hello %s!</p>
                    <p>You requested a passwordless login. Click the button below to log in:</p>
                    <a href="%s" class="login-button">Log In Now</a>
                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p><a href="%s">%s</a></p>
                    <p>This link will expire in 10 minutes for security reasons.</p>
                    <p>Best regards,<br>%s Team</p>
                </div>
            </body>
            </html>
        ', $button_color, $button_text_color, $button_hover_color, $button_hover_text_color, $link_color, $link_hover_color, esc_html($site_name), esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url), esc_html($site_name));
    }
    
    /**
     * German email template
     */
    private function get_german_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color) {
        $site_name = get_bloginfo('name');
        return sprintf('
            <html>
            <head>
                <style>
                    .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
                    .content-box { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .login-button { display: inline-block; background-color: %s; color: %s; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; font-size: 16px; }
                    .login-button:hover { background-color: %s; color: %s; }
                    a { color: %s; }
                    a:hover { color: %s; }
                    .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
                    .security-note { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="content-box">
                        <h2 style="color: #333; margin-bottom: 20px;">Passwortlose Anmeldung bei %s</h2>
                        <p>Guten Tag %s!</p>
                        <p>Sie haben eine passwortlose Anmeldung angefordert. Klicken Sie auf den untenstehenden Button, um sich sicher anzumelden:</p>
                        <center>
                            <a href="%s" class="login-button">Jetzt sicher anmelden</a>
                        </center>
                        <p>Falls der Button nicht funktioniert, können Sie alternativ diesen Link kopieren und in Ihrem Browser einfügen:</p>
                        <p style="word-break: break-all;"><a href="%s">%s</a></p>
                        <div class="security-note">
                            <strong>Sicherheitshinweis:</strong> Dieser Link ist aus Sicherheitsgründen nur 10 Minuten gültig. Nach der Anmeldung wird der Link automatisch ungültig.
                        </div>
                        <div class="footer">
                            <p>Mit freundlichen Grüßen,<br>Ihr %s Team</p>
                            <p style="font-size: 12px; color: #999;">Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese Nachricht.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ', $button_color, $button_text_color, $button_hover_color, $button_hover_text_color, $link_color, $link_hover_color, esc_html($site_name), esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url), esc_html($site_name));
    }
    
    /**
     * Simple email template
     */
    private function get_simple_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color) {
        $site_name = get_bloginfo('name');
        return sprintf('
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px; }
                    .login-button { display: inline-block; background-color: %s; color: %s; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin: 15px 0; }
                    .login-button:hover { background-color: %s; color: %s; }
                    a { color: %s; }
                    a:hover { color: %s; }
                    hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
                </style>
            </head>
            <body>
                <h3>Quick Login - %s</h3>
                <hr>
                <p>Hi %s,</p>
                <p>Your login link is ready:</p>
                <p><a href="%s" class="login-button">→ Click to Login</a></p>
                <p style="color: #666; font-size: 14px;">Expires in 10 minutes</p>
                <hr>
                <p style="font-size: 12px; color: #999;">Link: <a href="%s">%s</a></p>
            </body>
            </html>
        ', $button_color, $button_text_color, $button_hover_color, $button_hover_text_color, $link_color, $link_hover_color, esc_html($site_name), esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url));
    }
    
    /**
     * Custom email template
     */
    private function get_custom_template($to, $login_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color) {
        $custom_body = get_option('chrmrtns_kla_custom_email_body', '');
        $custom_styles = get_option('chrmrtns_kla_custom_email_styles', '');
        $site_name = get_bloginfo('name');
        
        // If no custom body exists, provide default inline-styled content
        if (empty($custom_body)) {
            $custom_body = '<div style="font-family: Arial, sans-serif; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px;">
    <div style="background: white; max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h1 style="color: #333; text-align: center; margin-bottom: 10px;">Welcome Back!</h1>
        <div style="text-align: center; margin-bottom: 20px;">
            <span style="background: #e8f5e8; color: #2d5016; padding: 8px 12px; border-radius: 15px; font-size: 12px; display: inline-block;">🔒 Secure Login</span>
        </div>
        <p style="font-size: 16px;">Hello <strong>[TO]</strong>,</p>
        <p>Your secure login link for ' . esc_html($site_name) . ' is ready. Click the button below to access your account instantly:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="[LOGIN_URL]" style="display: inline-block; background-color: [BUTTON_COLOR]; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">🚀 Access Your Account</a>
        </div>
        <p style="font-size: 14px; color: #666; text-align: center;">Or copy this link: <br><a href="[LOGIN_URL]" style="color: [LINK_COLOR]; text-decoration: none; word-break: break-all;">[LOGIN_URL]</a></p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #856404;">
                <strong>⏱️ Security Notice:</strong> This link expires in 10 minutes and can only be used once for your security.
            </p>
        </div>
        <p style="font-size: 12px; color: #999; text-align: center; margin-top: 20px;">
            This email was automatically generated. Please do not reply to this message.
        </p>
    </div>
</div>';
        }
        
        // Build the complete HTML structure
        $html = '<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Request</title>';
        
        // Add custom styles if provided
        if (!empty($custom_styles)) {
            $html .= '
    <style>
        ' . $custom_styles . '
    </style>';
        }
        
        $html .= '
</head>
<body>
    ' . $custom_body . '
</body>
</html>';
        
        // Ensure colors have default values if empty
        $button_color = $button_color ?: '#007bff';
        $button_hover_color = $button_hover_color ?: '#0056b3';
        $link_color = $link_color ?: '#007bff';
        $link_hover_color = $link_hover_color ?: '#0056b3';
        
        // Replace placeholders - case insensitive to handle common variations

        $replacements = array(
            '/\[TO\]/i' => esc_html($to),
            '/\[LOGIN_URL\]/i' => esc_url($login_url),
            '/\[BUTTON_COLOR\]/i' => $button_color,
            '/\[BUTTON_HOVER_COLOR\]/i' => $button_hover_color,
            '/\[BUTTON_TEXT_COLOR\]/i' => $button_text_color,
            '/\[BUTTON_HOVER_TEXT_COLOR\]/i' => $button_hover_text_color,
            '/\[LINK_COLOR\]/i' => $link_color,
            '/\[LINK_HOVER_COLOR\]/i' => $link_hover_color,
            // Handle lowercase variations (TinyMCE converts within CSS style attributes)
            '/\[button_color\]/i' => $button_color,
            '/\[button_text_color\]/i' => $button_text_color,
            '/\[button_hover_color\]/i' => $button_hover_color,
            '/\[button_hover_text_color\]/i' => $button_hover_text_color,
            '/\[link_color\]/i' => $link_color,
            '/\[link_hover_color\]/i' => $link_hover_color,
            // Handle common typos
            '/\[buttton_color\]/i' => $button_color, // Triple 't' typo
        );

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        
        return $html;
    }
    
    /**
     * Sanitize email HTML
     */
    public function sanitize_email_html($html) {
        $allowed_tags = array(
            'html' => array(),
            'head' => array(),
            'body' => array(),
            'style' => array(),
            'div' => array('class' => array(), 'style' => array()),
            'p' => array('class' => array(), 'style' => array()),
            'h1' => array('class' => array(), 'style' => array()),
            'h2' => array('class' => array(), 'style' => array()),
            'h3' => array('class' => array(), 'style' => array()),
            'h4' => array('class' => array(), 'style' => array()),
            'h5' => array('class' => array(), 'style' => array()),
            'h6' => array('class' => array(), 'style' => array()),
            'a' => array('href' => array(), 'class' => array(), 'style' => array(), 'target' => array()),
            'strong' => array('class' => array(), 'style' => array()),
            'em' => array('class' => array(), 'style' => array()),
            'br' => array(),
            'hr' => array('class' => array(), 'style' => array()),
            'table' => array('class' => array(), 'style' => array(), 'cellpadding' => array(), 'cellspacing' => array(), 'border' => array()),
            'tr' => array('class' => array(), 'style' => array()),
            'td' => array('class' => array(), 'style' => array(), 'colspan' => array(), 'rowspan' => array()),
            'th' => array('class' => array(), 'style' => array(), 'colspan' => array(), 'rowspan' => array()),
            'thead' => array('class' => array(), 'style' => array()),
            'tbody' => array('class' => array(), 'style' => array()),
            'ul' => array('class' => array(), 'style' => array()),
            'ol' => array('class' => array(), 'style' => array()),
            'li' => array('class' => array(), 'style' => array()),
            'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'style' => array(), 'width' => array(), 'height' => array()),
            'span' => array('class' => array(), 'style' => array())
        );
        
        return wp_kses($html, $allowed_tags);
    }
    
    /**
     * Sanitize color input
     */
    public function sanitize_color($color) {
        $color = sanitize_text_field($color);
        
        if (empty($color)) {
            return '';
        }
        
        // Check for hex color
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }
        
        // Check for RGB/RGBA
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+)?\s*\)$/i', $color)) {
            return $color;
        }
        
        // Check for HSL/HSLA
        if (preg_match('/^hsla?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*(,\s*[\d.]+)?\s*\)$/i', $color)) {
            return $color;
        }
        
        // Try WordPress sanitize_hex_color as fallback
        $hex_color = sanitize_hex_color($color);
        if ($hex_color) {
            return $hex_color;
        }
        
        // Return empty if no valid format found
        return '';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {

        // Handle form submission
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chrmrtns_kla_settings_nonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_settings_nonce'])), 'chrmrtns_kla_settings_save')) {
                $this->save_template_settings();
            } else {
                add_settings_error('chrmrtns_kla_settings', 'nonce_failed', esc_html__('Security check failed. Please try again.', 'keyless-auth'), 'error');
            }
        }
        
        echo '<!-- CHRMRTNS: Settings page is loading -->';
        ?>
        <h1 class="chrmrtns-header">
            <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
            <?php esc_html_e('Email Template Settings', 'keyless-auth'); ?>
        </h1>
        
        <?php settings_errors('chrmrtns_kla_settings'); ?>

        <?php if (isset($_GET['template_reset'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Template Reset Complete:</strong> All email template settings have been reset to defaults. The template selection is now "Default Template".</p>
            </div>
        <?php endif; ?>
        
        <p><?php esc_html_e('Customize the appearance and content of your passwordless login emails.', 'keyless-auth'); ?></p>
        
        <form id="chrmrtns_kla_settings_form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-settings')); ?>">
            <?php wp_nonce_field('chrmrtns_kla_settings_save', 'chrmrtns_kla_settings_nonce'); ?>
            
            <h2><?php esc_html_e('Email Template', 'keyless-auth'); ?></h2>
            <?php $this->render_template_selection(); ?>
            
            <h2><?php esc_html_e('Color Settings', 'keyless-auth'); ?></h2>
            <?php $this->render_color_settings(); ?>
            
            <h2><?php esc_html_e('Custom Template Editor', 'keyless-auth'); ?></h2>
            <?php $this->render_custom_template_editor(); ?>
            
            <div style="display: flex; gap: 15px; align-items: center; margin-top: 20px;">
                <div>
                    <?php submit_button(esc_html__('Save Settings', 'keyless-auth'), 'primary', 'submit', false); ?>
                </div>
                </form>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-settings')); ?>" style="margin: 0;">
                    <?php wp_nonce_field('chrmrtns_kla_settings_save', 'chrmrtns_kla_settings_nonce'); ?>
                    <input type="hidden" name="reset_custom_template" value="1">
                    <div>
                        <?php submit_button(esc_html__('Reset Custom Template', 'keyless-auth'), 'secondary', 'reset_template', false, array('onclick' => 'return confirm("Are you sure you want to reset the custom template? This will delete all custom content.");')); ?>
                    </div>
                </form>
            </div>
        
        <?php $this->render_template_help(); ?>
        <?php $this->enqueue_scripts(); ?>
        <?php
    }
    
    /**
     * Render template selection
     */
    private function render_template_selection() {
        $current_template = get_option('chrmrtns_kla_email_template', 'default');
        $templates = array(
            'default' => esc_html__('Default Template', 'keyless-auth'),
            'german' => esc_html__('German Template', 'keyless-auth'),
            'simple' => esc_html__('Simple Template', 'keyless-auth'),
            'custom' => esc_html__('Custom Template', 'keyless-auth')
        );
        
        // 2x2 Grid container
        echo '<div class="chrmrtns-template-selection" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px;">';
        
        foreach ($templates as $template_key => $template_name) {
            $checked = ($current_template === $template_key) ? 'checked' : '';
            
            // Each template in its own grid cell
            echo '<div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #fff;">';
            echo '<label style="display: block; margin-bottom: 10px; font-weight: bold; cursor: pointer;">';
            echo '<input type="radio" name="chrmrtns_kla_email_template" value="' . esc_attr($template_key) . '" ' . esc_attr($checked) . '>';
            echo ' ' . esc_html($template_name);
            echo '</label>';
            
            // Show preview
            echo '<div class="template-preview" style="border: 1px solid #ccc; height: 200px; width: 100%;">';
            echo wp_kses(
                $this->get_template_preview($template_key),
                array(
                    'iframe' => array(
                        'srcdoc' => array(),
                        'style' => array(),
                        'width' => array(),
                        'height' => array(),
                        'border' => array()
                    ),
                    'div' => array('style' => array(), 'class' => array()),
                    'p' => array('style' => array(), 'class' => array()),
                    'span' => array('style' => array(), 'class' => array()),
                    'strong' => array(),
                    'em' => array(),
                    'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array(), 'style' => array()),
                    'br' => array(),
                    'ul' => array('class' => array()),
                    'ol' => array('class' => array()),
                    'li' => array('class' => array()),
                    'img' => array('src' => array(), 'alt' => array(), 'style' => array(), 'width' => array(), 'height' => array()),
                    'table' => array('style' => array(), 'class' => array(), 'border' => array()),
                    'tr' => array(),
                    'td' => array('style' => array(), 'colspan' => array(), 'rowspan' => array()),
                    'th' => array(),
                    'h1' => array('style' => array(), 'class' => array()),
                    'h2' => array('style' => array(), 'class' => array()),
                    'h3' => array('style' => array(), 'class' => array()),
                    'h4' => array('style' => array(), 'class' => array()),
                    'hr' => array('style' => array())
                )
            );
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Get template preview
     */
    private function get_template_preview($template_key) {
        // Get the specific template for preview
        $button_color = get_option('chrmrtns_kla_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_kla_button_hover_color', '#0056b3');
        $button_text_color = get_option('chrmrtns_kla_button_text_color', '#ffffff');
        $button_hover_text_color = get_option('chrmrtns_kla_button_hover_text_color', '#ffffff');
        $link_color = get_option('chrmrtns_kla_link_color', '#007bff');
        $link_hover_color = get_option('chrmrtns_kla_link_hover_color', '#0056b3');
        $preview_url = '#';
        $preview_email = 'user@example.com';
        
        switch ($template_key) {
            case 'german':
                $preview_content = $this->get_german_template($preview_email, $preview_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
                break;
            case 'simple':
                $preview_content = $this->get_simple_template($preview_email, $preview_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
                break;
            case 'custom':
                $preview_content = $this->get_custom_template($preview_email, $preview_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
                break;
            default:
                $preview_content = $this->get_default_template($preview_email, $preview_url, $button_color, $button_hover_color, $button_text_color, $button_hover_text_color, $link_color, $link_hover_color);
                break;
        }
        
        return '<iframe srcdoc="' . htmlspecialchars($preview_content, ENT_QUOTES) . '" style="width: 100%; height: 100%; border: none;"></iframe>';
    }
    
    /**
     * Render color settings
     */
    private function render_color_settings() {
        $button_color = get_option('chrmrtns_kla_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_kla_button_hover_color', '#0056b3');
        $button_text_color = get_option('chrmrtns_kla_button_text_color', '#ffffff');
        $button_hover_text_color = get_option('chrmrtns_kla_button_hover_text_color', '#ffffff');
        $link_color = get_option('chrmrtns_kla_link_color', '#007bff');
        $link_hover_color = get_option('chrmrtns_kla_link_hover_color', '#0056b3');
        
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Button Color', 'keyless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_kla_button_color_picker" value="<?php echo esc_attr($button_color); ?>" />
                    <input type="text" name="chrmrtns_kla_button_color" id="chrmrtns_kla_button_color_text" value="<?php echo esc_attr($button_color); ?>" placeholder="e.g. #007bff, rgb(0,123,255), hsl(211,100%,50%)" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Button Hover Color', 'keyless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_kla_button_hover_color_picker" value="<?php echo esc_attr($button_hover_color); ?>" />
                    <input type="text" name="chrmrtns_kla_button_hover_color" id="chrmrtns_kla_button_hover_color_text" value="<?php echo esc_attr($button_hover_color); ?>" placeholder="e.g. #0056b3, rgba(0,86,179,0.9)" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Button Text Color', 'keyless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_kla_button_text_color_picker" value="<?php echo esc_attr($button_text_color); ?>" />
                    <input type="text" name="chrmrtns_kla_button_text_color" id="chrmrtns_kla_button_text_color_text" value="<?php echo esc_attr($button_text_color); ?>" placeholder="e.g. #ffffff, white" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Button Hover Text Color', 'keyless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_kla_button_hover_text_color_picker" value="<?php echo esc_attr($button_hover_text_color); ?>" />
                    <input type="text" name="chrmrtns_kla_button_hover_text_color" id="chrmrtns_kla_button_hover_text_color_text" value="<?php echo esc_attr($button_hover_text_color); ?>" placeholder="e.g. #ffffff, white" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Link Color', 'keyless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_kla_link_color_picker" value="<?php echo esc_attr($link_color); ?>" />
                    <input type="text" name="chrmrtns_kla_link_color" id="chrmrtns_kla_link_color_text" value="<?php echo esc_attr($link_color); ?>" placeholder="e.g. #007bff, rgb(0,123,255)" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Link Hover Color', 'keyless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_kla_link_hover_color_picker" value="<?php echo esc_attr($link_hover_color); ?>" />
                    <input type="text" name="chrmrtns_kla_link_hover_color" id="chrmrtns_kla_link_hover_color_text" value="<?php echo esc_attr($link_hover_color); ?>" placeholder="e.g. #0056b3, rgba(0,86,179,0.9)" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render custom template editor
     */
    private function render_custom_template_editor() {
        $custom_body = get_option('chrmrtns_kla_custom_email_body', '');


        // Only provide placeholder content for DISPLAY PURPOSES if completely empty
        // This does NOT auto-save the content - just shows a helpful starting template
        $editor_content = $custom_body;
        if (empty($custom_body)) {
            $site_name = get_bloginfo('name');
            $editor_content = '<div style="font-family: Arial, sans-serif; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px;">
    <div style="background: white; max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h1 style="color: #333; text-align: center; margin-bottom: 10px;">Welcome Back!</h1>
        <div style="text-align: center; margin-bottom: 20px;">
            <span style="background: #e8f5e8; color: #2d5016; padding: 8px 12px; border-radius: 15px; font-size: 12px; display: inline-block;">🔒 Secure Login</span>
        </div>
        <p style="font-size: 16px;">Hello <strong>[TO]</strong>,</p>
        <p>Your secure login link for ' . esc_html($site_name) . ' is ready. Click the button below to access your account instantly:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="[LOGIN_URL]" style="display: inline-block; background-color: [BUTTON_COLOR]; color: [BUTTON_TEXT_COLOR]; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">🚀 Access Your Account</a>
        </div>
        <p style="font-size: 14px; color: #666; text-align: center;">Or copy this link: <br><a href="[LOGIN_URL]" style="color: [LINK_COLOR]; text-decoration: none; word-break: break-all;">[LOGIN_URL]</a></p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #856404;">
                <strong>⏱️ Security Notice:</strong> This link expires in 10 minutes and can only be used once for your security.
            </p>
        </div>
        <p style="font-size: 12px; color: #999; text-align: center; margin-top: 20px;">
            This email was automatically generated. Please do not reply to this message.
        </p>
    </div>
</div>';
        }
        ?>
        <div id="chrmrtns_kla_custom_template_section" style="<?php echo get_option('chrmrtns_kla_email_template', 'default') !== 'custom' ? 'display: none;' : ''; ?>">
            <h4><?php esc_html_e('Email Body Content', 'keyless-auth'); ?></h4>
            <p><?php esc_html_e('Create your email body content using the WYSIWYG editor below. Use inline styles for best email client compatibility.', 'keyless-auth'); ?></p>
            <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 12px; margin: 15px 0; border-radius: 4px;">
                <p style="margin: 0; font-size: 14px;"><strong>💡 Tip:</strong> When switching between Visual and Text modes, the editor may convert placeholders within CSS style attributes to lowercase (e.g., <code>[BUTTON_COLOR]</code> becomes <code>[button_color]</code>). This is normal behavior and both formats work correctly in your emails.</p>
            </div>
            <?php if (empty($custom_body)): ?>
                <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;">
                    <strong>Note:</strong> This is a starter template. Edit it as needed and click "Save Settings" to store your changes.
                </p>
            <?php endif; ?>
            <?php
            wp_editor($editor_content, 'chrmrtns_kla_custom_email_body', array(
                'textarea_name' => 'chrmrtns_kla_custom_email_body',
                'media_buttons' => false,
                'textarea_rows' => 12,
                'teeny' => false,
                'dfw' => false,
                'tinymce' => array(
                    'resize' => true,
                    'wordpress_adv_hidden' => false,
                    'add_unload_trigger' => false,
                    'relative_urls' => false,
                    'remove_script_host' => false,
                    'convert_urls' => false,
                    'verify_html' => false,
                    'cleanup' => false,
                    'force_p_newlines' => false,
                    'forced_root_block' => false,
                    'entity_encoding' => 'raw'
                ),
                'quicktags' => array(
                    'buttons' => 'em,strong,link,block,del,ins,img,ul,ol,li,code,more,close'
                )
            ));
            ?>
            
            <h4 style="margin-top: 25px;"><?php esc_html_e('Custom CSS Styles (Optional)', 'keyless-auth'); ?></h4>
            <p><?php echo wp_kses(
                __('Add custom CSS that will be placed in the &lt;head&gt; section. This is for advanced users who want to use CSS classes instead of inline styles.', 'keyless-auth'),
                array()
            ); ?></p>
            <?php
            $custom_styles = get_option('chrmrtns_kla_custom_email_styles', '');
            ?>
            <textarea name="chrmrtns_kla_custom_email_styles" id="chrmrtns_kla_custom_email_styles" rows="8" cols="50" style="width: 100%; font-family: monospace;" placeholder="/* Add your custom CSS here */&#10;.login-button {&#10;    background-color: [BUTTON_COLOR];&#10;    color: white;&#10;    padding: 15px 30px;&#10;    border-radius: 25px;&#10;}"><?php echo esc_textarea($custom_styles); ?></textarea>
            
            <p class="description">
                <?php echo wp_kses(
                    __('The final email will have this structure: &lt;html&gt;&lt;head&gt;&lt;style&gt;[your CSS]&lt;/style&gt;&lt;/head&gt;&lt;body&gt;[your content]&lt;/body&gt;&lt;/html&gt;. Use placeholders like [TO], [LOGIN_URL], [BUTTON_COLOR], etc.', 'keyless-auth'),
                    array()
                ); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render template help
     */
    private function render_template_help() {
        ?>
        <div class="chrmrtns-template-help" style="margin-top: 30px; background: #f9f9f9; padding: 20px; border-radius: 5px;">
            <h3><?php esc_html_e('Template Placeholders', 'keyless-auth'); ?></h3>
            <p><?php esc_html_e('Use these placeholders in your custom template:', 'keyless-auth'); ?></p>
            <ul>
                <li><code>[TO]</code> - <?php esc_html_e('Recipient email address', 'keyless-auth'); ?></li>
                <li><code>[LOGIN_URL]</code> - <?php esc_html_e('Login URL', 'keyless-auth'); ?></li>
                <li><code>[BUTTON_COLOR]</code> - <?php esc_html_e('Button color', 'keyless-auth'); ?></li>
                <li><code>[BUTTON_HOVER_COLOR]</code> - <?php esc_html_e('Button hover color', 'keyless-auth'); ?></li>
                <li><code>[LINK_COLOR]</code> - <?php esc_html_e('Link color', 'keyless-auth'); ?></li>
                <li><code>[LINK_HOVER_COLOR]</code> - <?php esc_html_e('Link hover color', 'keyless-auth'); ?></li>
            </ul>
            <h4><?php esc_html_e('Example HTML:', 'keyless-auth'); ?></h4>
            <pre style="background: white; padding: 15px; border: 1px solid #ddd; overflow-x: auto;"><code>&lt;html&gt;
&lt;head&gt;
    &lt;style&gt;
        .login-button { 
            background-color: [BUTTON_COLOR]; 
            color: white; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .login-button:hover { background-color: [BUTTON_HOVER_COLOR]; }
        a { color: [LINK_COLOR]; }
        a:hover { color: [LINK_HOVER_COLOR]; }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h2&gt;Login Request&lt;/h2&gt;
    &lt;p&gt;Hello [TO]!&lt;/p&gt;
    &lt;p&gt;&lt;a href="[LOGIN_URL]" class="login-button"&gt;Log In&lt;/a&gt;&lt;/p&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
        </div>
        <?php
    }
    
    /**
     * Get 2FA notification email template
     *
     * @param string $to User email
     * @param string $user_name User display name
     * @param int $grace_days Grace period remaining
     * @param string $setup_url URL to setup 2FA
     * @return string Email HTML content
     */
    public function get_2fa_notification_template($to, $user_name, $grace_days, $setup_url) {
        $site_name = get_bloginfo('name');
        $site_url = get_site_url();

        $button_color = get_option('chrmrtns_kla_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_kla_button_hover_color', '#0056b3');
        $button_text_color = get_option('chrmrtns_kla_button_text_color', '#ffffff');

        $content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html__('Account Security Setup', 'keyless-auth') . ' - ' . esc_html($site_name) . '</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: ' . esc_attr($button_color) . ';
            color: ' . esc_attr($button_text_color) . ';
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: ' . esc_attr($button_hover_color) . ';
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .urgent-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .steps {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">[Security] ' . esc_html($site_name) . '</div>
            <h1 class="title">' . esc_html__('Account Security Setup', 'keyless-auth') . '</h1>
        </div>

        <div class="content">
            <p>' . sprintf(
                /* translators: %s: user display name */
                esc_html__('Hello %s,', 'keyless-auth'),
                esc_html($user_name)
            ) . '</p>

            <p>' . esc_html__('We are updating account settings for all users. Your account needs additional verification setup to continue accessing your dashboard.', 'keyless-auth') . '</p>';

        if ($grace_days > 0) {
            if ($grace_days <= 3) {
                $content .= '<div class="urgent-box">
                    <strong>' . esc_html__('Setup Reminder', 'keyless-auth') . '</strong><br>
                    ' . sprintf(
                        /* translators: %d: number of days remaining */
                        esc_html__('Please complete account verification within %d days to maintain access.', 'keyless-auth'),
                        $grace_days
                    ) . '
                </div>';
            } elseif ($grace_days <= 7) {
                $content .= '<div class="warning-box">
                    <strong>' . esc_html__('Setup Reminder', 'keyless-auth') . '</strong><br>
                    ' . sprintf(
                        /* translators: %d: number of days remaining */
                        esc_html__('Please complete account verification within %d days.', 'keyless-auth'),
                        $grace_days
                    ) . '
                </div>';
            } else {
                $content .= '<div class="info-box">
                    <strong>' . esc_html__('Setup Needed', 'keyless-auth') . '</strong><br>
                    ' . sprintf(
                        /* translators: %d: number of days remaining */
                        esc_html__('Please complete account verification within %d days.', 'keyless-auth'),
                        $grace_days
                    ) . '
                </div>';
            }
        }

        $content .= '<div class="steps">
                <h3>' . esc_html__('Setup Steps:', 'keyless-auth') . '</h3>
                <ol>
                    <li>' . esc_html__('Download a verification app (Google Authenticator, Authy, or similar)', 'keyless-auth') . '</li>
                    <li>' . esc_html__('Click the button below to access your account settings', 'keyless-auth') . '</li>
                    <li>' . esc_html__('Scan the QR code with your verification app', 'keyless-auth') . '</li>
                    <li>' . esc_html__('Enter the 6-digit code to complete setup', 'keyless-auth') . '</li>
                    <li>' . esc_html__('Save your backup codes safely', 'keyless-auth') . '</li>
                </ol>
            </div>

            <div class="button-container">
                <a href="' . esc_url($setup_url) . '" class="button">' . esc_html__('Login to Setup', 'keyless-auth') . '</a>
            </div>

            <p><strong>' . esc_html__('Why is this important?', 'keyless-auth') . '</strong></p>
            <p>' . esc_html__('Account verification helps protect your data by adding a second step to the login process. This keeps your account safe even if someone knows your password.', 'keyless-auth') . '</p>

            <p>' . esc_html__('If you have any questions or need assistance, please contact our support team.', 'keyless-auth') . '</p>
        </div>

        <div class="footer">
            <p>' . sprintf(
                /* translators: 1: site name, 2: site URL */
                esc_html__('This email was sent from %1$s (%2$s)', 'keyless-auth'),
                esc_html($site_name),
                esc_url($site_url)
            ) . '</p>
            <p>' . esc_html__('This is an automated message. Please do not reply to this email.', 'keyless-auth') . '</p>
        </div>
    </div>
</body>
</html>';

        return $content;
    }

    /**
     * Enqueue scripts for the settings page
     */
    private function enqueue_scripts() {
        // Scripts are now properly enqueued in class-chrmrtns-kla-admin.php
        // via the enqueue_admin_scripts() method
    }
}
