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
                    <h2>Passwordless Login</h2>
                    <p>Hello %s!</p>
                    <p>You requested a passwordless login. Click the button below to log in:</p>
                    <a href="%s" class="login-button">Log In</a>
                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p><a href="%s">%s</a></p>
                    <p>This link will expire in 10 minutes for security reasons.</p>
                    <p>Best regards,<br>Your Website Team</p>
                </div>
            </body>
            </html>
        ', $button_color, $button_hover_color, $link_color, $link_hover_color, esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url));
    }
    
    /**
     * German email template
     */
    private function get_german_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
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
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="content-box">
                        <h2 style="color: #333; margin-bottom: 20px;">Passwortloser Login</h2>
                        <p>Hallo %s!</p>
                        <p>Sie haben einen passwortlosen Login angefordert. Klicken Sie auf den Button unten, um sich anzumelden:</p>
                        <a href="%s" class="login-button">Jetzt Anmelden</a>
                        <p>Falls der Button nicht funktioniert, können Sie diesen Link in Ihren Browser kopieren:</p>
                        <p><a href="%s">%s</a></p>
                        <p><strong>Wichtiger Hinweis:</strong> Dieser Link ist aus Sicherheitsgründen nur 10 Minuten gültig.</p>
                        <div class="footer">
                            <p>Mit freundlichen Grüßen,<br>Ihr Website-Team</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ', $button_color, $button_hover_color, $link_color, $link_hover_color, esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url));
    }
    
    /**
     * Simple email template
     */
    private function get_simple_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
        return sprintf('
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .login-button { display: inline-block; background-color: %s; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; margin: 15px 0; }
                    .login-button:hover { background-color: %s; }
                    a { color: %s; }
                    a:hover { color: %s; }
                </style>
            </head>
            <body>
                <h3>Login Request</h3>
                <p>Hello %s,</p>
                <p>Click here to log in: <a href="%s" class="login-button">Log In</a></p>
                <p>Link expires in 10 minutes.</p>
                <p>Direct link: <a href="%s">%s</a></p>
            </body>
            </html>
        ', $button_color, $button_hover_color, $link_color, $link_hover_color, esc_html($to), esc_url($login_url), esc_url($login_url), esc_html($login_url));
    }
    
    /**
     * Custom email template
     */
    private function get_custom_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color) {
        $custom_body = get_option('chrmrtns_custom_email_body', '');
        
        if (empty($custom_body)) {
            return $this->get_default_template($to, $login_url, $button_color, $button_hover_color, $link_color, $link_hover_color);
        }
        
        // Replace placeholders
        $custom_body = str_replace('{{TO}}', esc_html($to), $custom_body);
        $custom_body = str_replace('{{LOGIN_URL}}', esc_url($login_url), $custom_body);
        $custom_body = str_replace('{{BUTTON_COLOR}}', $button_color, $custom_body);
        $custom_body = str_replace('{{BUTTON_HOVER_COLOR}}', $button_hover_color, $custom_body);
        $custom_body = str_replace('{{LINK_COLOR}}', $link_color, $custom_body);
        $custom_body = str_replace('{{LINK_HOVER_COLOR}}', $link_hover_color, $custom_body);
        
        return $custom_body;
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
        ?>
        <div class="chrmrtns-badge"></div>
        <h1><?php _e('Email Template Settings', 'chrmrtns-passwordless-auth'); ?></h1>
        <p><?php _e('Customize the appearance and content of your passwordless login emails.', 'chrmrtns-passwordless-auth'); ?></p>
        
        <form id="chrmrtns_settings_form" method="post" action="">
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
        
        echo '<div class="chrmrtns-template-selection">';
        foreach ($templates as $template_key => $template_name) {
            $checked = ($current_template === $template_key) ? 'checked' : '';
            echo '<label style="display: block; margin: 10px 0;">';
            echo '<input type="radio" name="chrmrtns_email_template" value="' . esc_attr($template_key) . '" ' . $checked . '>';
            echo ' ' . esc_html($template_name);
            echo '</label>';
            
            // Show preview
            echo '<div class="template-preview" style="margin: 10px 0 20px 20px; border: 1px solid #ddd; height: 200px; width: 100%; max-width: 600px;">';
            echo $this->get_template_preview($template_key);
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Get template preview
     */
    private function get_template_preview($template_key) {
        $preview_content = $this->get_email_template('user@example.com', '#');
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
        ?>
        <div id="chrmrtns_custom_template_section" style="<?php echo get_option('chrmrtns_email_template', 'default') !== 'custom' ? 'display: none;' : ''; ?>">
            <p><?php _e('Create your own custom email template using HTML. Use the placeholders shown in the help section below.', 'chrmrtns-passwordless-auth'); ?></p>
            <?php
            wp_editor($custom_body, 'chrmrtns_custom_email_body', array(
                'textarea_name' => 'chrmrtns_custom_email_body',
                'media_buttons' => false,
                'textarea_rows' => 15,
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
        });
        </script>
        <?php
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        update_option('chrmrtns_email_template', sanitize_text_field($_POST['chrmrtns_email_template']));
        update_option('chrmrtns_custom_email_body', $this->sanitize_email_html($_POST['chrmrtns_custom_email_body']));
        update_option('chrmrtns_button_color', $this->sanitize_color($_POST['chrmrtns_button_color']));
        update_option('chrmrtns_button_hover_color', $this->sanitize_color($_POST['chrmrtns_button_hover_color']));
        update_option('chrmrtns_link_color', $this->sanitize_color($_POST['chrmrtns_link_color']));
        update_option('chrmrtns_link_hover_color', $this->sanitize_color($_POST['chrmrtns_link_hover_color']));
        
        wp_redirect(admin_url('admin.php?page=chrmrtns-passwordless-auth-settings&settings-updated=true'));
        exit;
    }
}