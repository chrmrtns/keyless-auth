<?php
/**
 * Email templates functionality for Passwordless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Email_Templates {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor is intentionally empty - methods are called as needed
    }
    
    /**
     * Get email template content
     */
    public function get_email_template($to, $login_url) {
        $template = get_option('chrmrtns_email_template', 'default');
        $button_color = get_option('chrmrtns_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_button_hover_color', '#0056b3');
        $link_color = get_option('chrmrtns_link_color', '#007bff');
        $link_hover_color = get_option('chrmrtns_link_hover_color', '#0056b3');
        
        switch ($template) {
            case 'german':
                return $this->get_german_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
            case 'simple':
                return $this->get_simple_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
            case 'custom':
                return $this->get_custom_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
            default:
                return $this->get_default_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
        }
    }
    
    /**
     * Default email template
     */
    private function get_default_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
        $site_name = get_bloginfo('name');
        return sprintf('
            <html>
            <head>
                <style>
                    .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .login-button { display: inline-block; background-color: %s; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 20px 0; }
                    .login-button:hover { background-color: %s; }
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
        ', $button_color, $button_hover_color, $link_color, $link_hover_color, esc_html($site_name), esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url), esc_html($site_name));
    }
    
    /**
     * German email template
     */
    private function get_german_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
        $site_name = get_bloginfo('name');
        return sprintf('
            <html>
            <head>
                <style>
                    .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; }
                    .content-box { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .login-button { display: inline-block; background-color: %s; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; font-size: 16px; }
                    .login-button:hover { background-color: %s; }
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
        ', $button_color, $button_hover_color, $link_color, $link_hover_color, esc_html($site_name), esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url), esc_html($site_name));
    }
    
    /**
     * Simple email template
     */
    private function get_simple_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
        $site_name = get_bloginfo('name');
        return sprintf('
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px; }
                    .login-button { display: inline-block; background-color: %s; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin: 15px 0; }
                    .login-button:hover { background-color: %s; }
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
        ', $button_color, $button_hover_color, $link_color, $link_hover_color, esc_html($site_name), esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url));
    }
    
    /**
     * Custom email template
     */
    private function get_custom_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
        $custom_body = get_option('chrmrtns_custom_email_body', '');
        $custom_styles = get_option('chrmrtns_custom_email_styles', '');
        $site_name = get_bloginfo('name');
        
        // If no custom body exists, provide default inline-styled content
        if (empty($custom_body)) {
            $custom_body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px;">
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h1 style="color: #333; text-align: center; margin-bottom: 10px;">Welcome Back!</h1>
        <div style="text-align: center; margin-bottom: 20px;">
            <span style="background: #e8f5e8; color: #2d5016; padding: 8px 12px; border-radius: 15px; font-size: 12px; display: inline-block;">🔒 Secure Login</span>
        </div>
        <p style="font-size: 16px;">Hello <strong>{{TO}}</strong>,</p>
        <p>Your secure login link for ' . esc_html($site_name) . ' is ready. Click the button below to access your account instantly:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{LOGIN_URL}}" style="display: inline-block; background-color: {{BUTTON_COLOR}}; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">🚀 Access Your Account</a>
        </div>
        <p style="font-size: 14px; color: #666; text-align: center;">Or copy this link: <br><a href="{{LOGIN_URL}}" style="color: {{LINK_COLOR}}; text-decoration: none; word-break: break-all;">{{LOGIN_URL}}</a></p>
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
        
        // Replace placeholders
        $html = str_replace('{{TO}}', esc_html($to), $html);
        $html = str_replace('{{LOGIN_URL}}', esc_url($login_url), $html);
        $html = str_replace('{{BUTTON_COLOR}}', $button_color, $html);
        $html = str_replace('{{BUTTON_HOVER_COLOR}}', $button_hover_color, $html);
        $html = str_replace('{{LINK_COLOR}}', $link_color, $html);
        $html = str_replace('{{LINK_HOVER_COLOR}}', $link_hover_color, $html);
        
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
        // Debug output
        error_log('CHRMRTNS: render_settings_page called');
        
        // Handle form submission directly here
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chrmrtns_settings_nonce'])) {
            error_log('CHRMRTNS: POST request detected in render_settings_page');
            if (wp_verify_nonce($_POST['chrmrtns_settings_nonce'], 'chrmrtns_settings_save')) {
                error_log('CHRMRTNS: Nonce verified, processing form submission');
                $this->save_settings();
                // Continue rendering the page with success message
            } else {
                error_log('CHRMRTNS: Nonce verification failed');
                add_settings_error('chrmrtns_settings', 'nonce_failed', __('Security check failed. Please try again.', 'chrmrtns-passwordless-auth'), 'error');
            }
        }
        
        echo '<!-- CHRMRTNS: Settings page is loading -->';
        ?>
        <div class="chrmrtns-badge"></div>
        <h1><?php _e('Email Template Settings', 'chrmrtns-passwordless-auth'); ?></h1>
        
        <?php settings_errors('chrmrtns_settings'); ?>
        
        <p><?php _e('Customize the appearance and content of your passwordless login emails.', 'chrmrtns-passwordless-auth'); ?></p>
        
        <form id="chrmrtns_settings_form" method="post" action="<?php echo admin_url('admin.php?page=chrmrtns-passwordless-auth-settings'); ?>">
            <?php wp_nonce_field('chrmrtns_settings_save', 'chrmrtns_settings_nonce'); ?>
            
            <h2><?php _e('Email Template', 'chrmrtns-passwordless-auth'); ?></h2>
            <?php $this->render_template_selection(); ?>
            
            <h2><?php _e('Color Settings', 'chrmrtns-passwordless-auth'); ?></h2>
            <?php $this->render_color_settings(); ?>
            
            <h2><?php _e('Custom Template Editor', 'chrmrtns-passwordless-auth'); ?></h2>
            <?php $this->render_custom_template_editor(); ?>
            
            <?php submit_button(__('Save Settings', 'chrmrtns-passwordless-auth')); ?>
        </form>
        
        <?php $this->render_template_help(); ?>
        <?php $this->enqueue_scripts(); ?>
        <?php
    }
    
    /**
     * Render template selection
     */
    private function render_template_selection() {
        $current_template = get_option('chrmrtns_email_template', 'default');
        $templates = array(
            'default' => __('Default Template', 'chrmrtns-passwordless-auth'),
            'german' => __('German Template', 'chrmrtns-passwordless-auth'),
            'simple' => __('Simple Template', 'chrmrtns-passwordless-auth'),
            'custom' => __('Custom Template', 'chrmrtns-passwordless-auth')
        );
        
        // 2x2 Grid container
        echo '<div class="chrmrtns-template-selection" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px;">';
        
        foreach ($templates as $template_key => $template_name) {
            $checked = ($current_template === $template_key) ? 'checked' : '';
            
            // Each template in its own grid cell
            echo '<div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #fff;">';
            echo '<label style="display: block; margin-bottom: 10px; font-weight: bold; cursor: pointer;">';
            echo '<input type="radio" name="chrmrtns_email_template" value="' . esc_attr($template_key) . '" ' . $checked . '>';
            echo ' ' . esc_html($template_name);
            echo '</label>';
            
            // Show preview
            echo '<div class="template-preview" style="border: 1px solid #ccc; height: 200px; width: 100%;">';
            echo $this->get_template_preview($template_key);
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
        $button_color = get_option('chrmrtns_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_button_hover_color', '#0056b3');
        $link_color = get_option('chrmrtns_link_color', '#007bff');
        $link_hover_color = get_option('chrmrtns_link_hover_color', '#0056b3');
        $preview_url = '#';
        $preview_email = 'user@example.com';
        
        switch ($template_key) {
            case 'german':
                $preview_content = $this->get_german_template($preview_email, $preview_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
                break;
            case 'simple':
                $preview_content = $this->get_simple_template($preview_email, $preview_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
                break;
            case 'custom':
                $preview_content = $this->get_custom_template($preview_email, $preview_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
                break;
            default:
                $preview_content = $this->get_default_template($preview_email, $preview_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
                break;
        }
        
        return '<iframe srcdoc="' . esc_attr($preview_content) . '" style="width: 100%; height: 100%; border: none;"></iframe>';
    }
    
    /**
     * Render color settings
     */
    private function render_color_settings() {
        $button_color = get_option('chrmrtns_button_color', '#007bff');
        $button_hover_color = get_option('chrmrtns_button_hover_color', '#0056b3');
        $link_color = get_option('chrmrtns_link_color', '#007bff');
        $link_hover_color = get_option('chrmrtns_link_hover_color', '#0056b3');
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Button Color', 'chrmrtns-passwordless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_button_color_picker" value="<?php echo esc_attr($button_color); ?>" />
                    <input type="text" name="chrmrtns_button_color" id="chrmrtns_button_color_text" value="<?php echo esc_attr($button_color); ?>" placeholder="e.g. #007bff, rgb(0,123,255), hsl(211,100%,50%)" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Button Hover Color', 'chrmrtns-passwordless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_button_hover_color_picker" value="<?php echo esc_attr($button_hover_color); ?>" />
                    <input type="text" name="chrmrtns_button_hover_color" id="chrmrtns_button_hover_color_text" value="<?php echo esc_attr($button_hover_color); ?>" placeholder="e.g. #0056b3, rgba(0,86,179,0.9)" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Link Color', 'chrmrtns-passwordless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_link_color_picker" value="<?php echo esc_attr($link_color); ?>" />
                    <input type="text" name="chrmrtns_link_color" id="chrmrtns_link_color_text" value="<?php echo esc_attr($link_color); ?>" placeholder="e.g. #007bff, rgb(0,123,255)" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Link Hover Color', 'chrmrtns-passwordless-auth'); ?></th>
                <td>
                    <input type="color" id="chrmrtns_link_hover_color_picker" value="<?php echo esc_attr($link_hover_color); ?>" />
                    <input type="text" name="chrmrtns_link_hover_color" id="chrmrtns_link_hover_color_text" value="<?php echo esc_attr($link_hover_color); ?>" placeholder="e.g. #0056b3, rgba(0,86,179,0.9)" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render custom template editor
     */
    private function render_custom_template_editor() {
        $custom_body = get_option('chrmrtns_custom_email_body', '');
        
        // If empty, provide the beautiful demo template
        if (empty($custom_body)) {
            $site_name = get_bloginfo('name');
            $custom_body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px;">
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <h1 style="color: #333; text-align: center; margin-bottom: 10px;">Welcome Back!</h1>
        <div style="text-align: center; margin-bottom: 20px;">
            <span style="background: #e8f5e8; color: #2d5016; padding: 8px 12px; border-radius: 15px; font-size: 12px; display: inline-block;">🔒 Secure Login</span>
        </div>
        <p style="font-size: 16px;">Hello <strong>{{TO}}</strong>,</p>
        <p>Your secure login link for ' . esc_html($site_name) . ' is ready. Click the button below to access your account instantly:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{LOGIN_URL}}" style="display: inline-block; background-color: {{BUTTON_COLOR}}; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">🚀 Access Your Account</a>
        </div>
        <p style="font-size: 14px; color: #666; text-align: center;">Or copy this link: <br><a href="{{LOGIN_URL}}" style="color: {{LINK_COLOR}}; text-decoration: none; word-break: break-all;">{{LOGIN_URL}}</a></p>
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
        <div id="chrmrtns_custom_template_section" style="<?php echo get_option('chrmrtns_email_template', 'default') !== 'custom' ? 'display: none;' : ''; ?>">
            <h4><?php _e('Email Body Content', 'chrmrtns-passwordless-auth'); ?></h4>
            <p><?php _e('Create your email body content using the WYSIWYG editor below. Use inline styles for best email client compatibility.', 'chrmrtns-passwordless-auth'); ?></p>
            <?php
            wp_editor($custom_body, 'chrmrtns_custom_email_body', array(
                'textarea_name' => 'chrmrtns_custom_email_body',
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
                ),
                'quicktags' => array(
                    'buttons' => 'em,strong,link,block,del,ins,img,ul,ol,li,code,more,close'
                )
            ));
            ?>
            
            <h4 style="margin-top: 25px;"><?php _e('Custom CSS Styles (Optional)', 'chrmrtns-passwordless-auth'); ?></h4>
            <p><?php _e('Add custom CSS that will be placed in the &lt;head&gt; section. This is for advanced users who want to use CSS classes instead of inline styles.', 'chrmrtns-passwordless-auth'); ?></p>
            <?php
            $custom_styles = get_option('chrmrtns_custom_email_styles', '');
            ?>
            <textarea name="chrmrtns_custom_email_styles" id="chrmrtns_custom_email_styles" rows="8" cols="50" style="width: 100%; font-family: monospace;" placeholder="/* Add your custom CSS here */&#10;.login-button {&#10;    background-color: {{BUTTON_COLOR}};&#10;    color: white;&#10;    padding: 15px 30px;&#10;    border-radius: 25px;&#10;}"><?php echo esc_textarea($custom_styles); ?></textarea>
            
            <p class="description">
                <?php _e('The final email will have this structure: &lt;html&gt;&lt;head&gt;&lt;style&gt;[your CSS]&lt;/style&gt;&lt;/head&gt;&lt;body&gt;[your content]&lt;/body&gt;&lt;/html&gt;. Use placeholders like {{TO}}, {{LOGIN_URL}}, {{BUTTON_COLOR}}, etc.', 'chrmrtns-passwordless-auth'); ?>
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
            <h3><?php _e('Template Placeholders', 'chrmrtns-passwordless-auth'); ?></h3>
            <p><?php _e('Use these placeholders in your custom template:', 'chrmrtns-passwordless-auth'); ?></p>
            <ul>
                <li><code>{{TO}}</code> - <?php _e('Recipient email address', 'chrmrtns-passwordless-auth'); ?></li>
                <li><code>{{LOGIN_URL}}</code> - <?php _e('Login URL', 'chrmrtns-passwordless-auth'); ?></li>
                <li><code>{{BUTTON_COLOR}}</code> - <?php _e('Button color', 'chrmrtns-passwordless-auth'); ?></li>
                <li><code>{{BUTTON_HOVER_COLOR}}</code> - <?php _e('Button hover color', 'chrmrtns-passwordless-auth'); ?></li>
                <li><code>{{LINK_COLOR}}</code> - <?php _e('Link color', 'chrmrtns-passwordless-auth'); ?></li>
                <li><code>{{LINK_HOVER_COLOR}}</code> - <?php _e('Link hover color', 'chrmrtns-passwordless-auth'); ?></li>
            </ul>
            <h4><?php _e('Example HTML:', 'chrmrtns-passwordless-auth'); ?></h4>
            <pre style="background: white; padding: 15px; border: 1px solid #ddd; overflow-x: auto;"><code>&lt;html&gt;
&lt;head&gt;
    &lt;style&gt;
        .login-button { 
            background-color: {{BUTTON_COLOR}}; 
            color: white; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .login-button:hover { background-color: {{BUTTON_HOVER_COLOR}}; }
        a { color: {{LINK_COLOR}}; }
        a:hover { color: {{LINK_HOVER_COLOR}}; }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h2&gt;Login Request&lt;/h2&gt;
    &lt;p&gt;Hello {{TO}}!&lt;/p&gt;
    &lt;p&gt;&lt;a href="{{LOGIN_URL}}" class="login-button"&gt;Log In&lt;/a&gt;&lt;/p&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts for the settings page
     */
    private function enqueue_scripts() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Color picker synchronization
            $('.wp-color-picker').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    $(this).siblings('input[type="text"]').val(color);
                }
            });
            
            // Text input to color picker synchronization
            $('input[id$="_color_text"]').on('input', function() {
                var colorValue = $(this).val();
                var colorPicker = $(this).siblings('input[type="color"]');
                
                // Only update color picker if it's a valid hex color
                if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(colorValue)) {
                    colorPicker.val(colorValue);
                }
            });
            
            // Show/hide custom template editor based on selection
            $('input[name="chrmrtns_email_template"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#chrmrtns_custom_template_section').show();
                } else {
                    $('#chrmrtns_custom_template_section').hide();
                }
            });
            
            // Trigger initial show/hide
            var currentTemplate = $('input[name="chrmrtns_email_template"]:checked').val();
            if (currentTemplate === 'custom') {
                $('#chrmrtns_custom_template_section').show();
            } else {
                $('#chrmrtns_custom_template_section').hide();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        // Debug logging
        error_log('CHRMRTNS: save_settings called');
        error_log('CHRMRTNS: POST data: ' . print_r($_POST, true));
        
        // Save the template type
        $template_type = sanitize_text_field($_POST['chrmrtns_email_template']);
        error_log('CHRMRTNS: Saving template type: ' . $template_type);
        update_option('chrmrtns_email_template', $template_type);
        
        // Verify it was saved
        $saved_template = get_option('chrmrtns_email_template');
        error_log('CHRMRTNS: Verified saved template: ' . $saved_template);
        
        // Always save custom email body if provided (even if not currently selected)
        if (isset($_POST['chrmrtns_custom_email_body'])) {
            $custom_body = $this->sanitize_email_html($_POST['chrmrtns_custom_email_body']);
            update_option('chrmrtns_custom_email_body', $custom_body);
            error_log('CHRMRTNS: Saved custom email body length: ' . strlen($custom_body));
        }
        
        // Always save custom email styles if provided
        if (isset($_POST['chrmrtns_custom_email_styles'])) {
            $custom_styles = wp_strip_all_tags($_POST['chrmrtns_custom_email_styles']);
            update_option('chrmrtns_custom_email_styles', $custom_styles);
            error_log('CHRMRTNS: Saved custom email styles length: ' . strlen($custom_styles));
        }
        
        // Save color settings
        update_option('chrmrtns_button_color', $this->sanitize_color($_POST['chrmrtns_button_color']));
        update_option('chrmrtns_button_hover_color', $this->sanitize_color($_POST['chrmrtns_button_hover_color']));
        update_option('chrmrtns_link_color', $this->sanitize_color($_POST['chrmrtns_link_color']));
        update_option('chrmrtns_link_hover_color', $this->sanitize_color($_POST['chrmrtns_link_hover_color']));
        
        error_log('CHRMRTNS: Settings saved successfully - no redirect needed');
        
        // Don't redirect - just show success message
        add_settings_error('chrmrtns_settings', 'settings_saved', __('Settings saved successfully.', 'chrmrtns-passwordless-auth'), 'updated');
    }
}