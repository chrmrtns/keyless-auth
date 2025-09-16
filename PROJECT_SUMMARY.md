# Keyless Auth Plugin - Project Summary

## Plugin Overview
**Name:** Keyless Auth - Login without Passwords  
**Version:** 2.0.12  
**Slug:** keyless-auth  
**Text Domain:** keyless-auth  
**Author:** Chris Martens (@chrmrtns)  
**WordPress.org Username:** chrmrtns  
**GitHub:** https://github.com/chrmrtns/keyless-auth  
**Donate:** https://paypal.me/chrmrtns  

## What This Plugin Does
Passwordless authentication plugin for WordPress that allows users to login via email magic links instead of passwords. Users enter their email/username, receive a secure login link valid for 10 minutes, and click to automatically login.

## Key Technical Details

### File Structure
```
keyless-auth/
├── keyless-auth.php                    # Main plugin file
├── includes/
│   ├── class-chrmrtns-kla-core.php     # Core functionality & shortcode
│   ├── class-chrmrtns-kla-admin.php    # Admin interface & settings
│   ├── class-chrmrtns-kla-smtp.php     # SMTP configuration
│   ├── class-chrmrtns-kla-mail-logger.php  # Email logging functionality
│   └── class-chrmrtns-kla-email-templates.php  # Email template management
├── assets/
│   ├── admin-script.js                 # Admin JavaScript (enqueued)
│   ├── admin-style.css                 # Admin CSS (enqueued)
│   ├── style-front-end.css            # Frontend styles
│   ├── icon-256x256.png               # Plugin icon for WordPress.org
│   ├── banner-772x250.png             # Header banner for WordPress.org
│   └── screenshot-*.png (1-7)         # Plugin screenshots
├── inc/
│   └── chrmrtns.class.notices.php     # Admin notices system
├── languages/
│   └── passwordless-login.pot         # Translation template
├── readme.txt                          # WordPress.org readme
└── README.md                          # GitHub readme
```

### Important Constants & Prefixes
- **Plugin Prefix:** `chrmrtns_kla_` (all functions, options, classes)
- **Class Prefix:** `Chrmrtns_KLA_` (e.g., Chrmrtns_KLA_Core)
- **Constants:** `CHRMRTNS_KLA_VERSION`, `CHRMRTNS_KLA_PLUGIN_DIR`, `CHRMRTNS_KLA_PLUGIN_URL`
- **Shortcode:** `[keyless-auth]`
- **Post Type:** `chrmrtns_kla_logs` (17 chars - WordPress limit is 20)
- **Text Domain:** `keyless-auth`

### Key Features Implemented
1. **Passwordless Login:** Email-based authentication with secure tokens
2. **SMTP Support:** Full SMTP configuration for reliable email delivery
3. **Email Templates:** WYSIWYG editor with customizable templates
4. **Mail Logging:** Track all emails sent (post type: chrmrtns_kla_logs)
5. **Color Customization:** Button/link colors with hover states
6. **Security:** Token expiration (10 min), one-time use, timing attack protection
7. **wp-config.php Support:** Optional credential storage in wp-config.php

### Latest Changes in v2.0.12 (September 16, 2025)
1. **Settings Link:** Added plugin action links filter for direct settings access from WordPress plugin list
2. **Mail Logs Fix:** Added missing JavaScript functions `chrmrtnsShowEmailContent()` and `chrmrtnsHideEmailContent()`
3. **Admin Improvements:** Fixed settings link URL to use correct "keyless-auth" slug

**⚠️ PENDING:** SVN deployment to WordPress.org repository still needed (tags/2.0.12)

### Previous Fixes in v2.0.11
1. **SMTP Fix:** Added `$phpmailer->From = $phpmailer->Username;` to properly authenticate
2. **Mail Logging Fix:** Shortened post type name from 22 to 17 characters
3. **JavaScript Fix:** Restored wp-config.php instructions display functionality
4. **Fatal Error Fixes:** Fixed multiple `esc_attresc_html_e()` typos in mail logger

### WordPress.org Compliance
- **Nonce Verification:** All forms use `wp_verify_nonce()` with `wp_unslash()` and `sanitize_text_field()`
- **Output Escaping:** Uses `esc_html_e()`, `esc_attr()`, `esc_url()`, `wp_kses()` throughout
- **No Inline JS/CSS:** All scripts/styles moved to separate files with `wp_enqueue_script/style`
- **Unique Prefix:** Uses 4+ character prefix `chrmrtns_kla_` for uniqueness
- **Plugin Check:** Passes all WordPress.org Plugin Check requirements

### Admin Menu Structure
```
Keyless Auth (main menu)
├── Templates (Email template settings)
├── SMTP (SMTP configuration)
└── Mail Logs (Email logging viewer)
```

### Database Options
- `chrmrtns_kla_email_template` - Selected template
- `chrmrtns_kla_custom_email_body` - Custom email HTML
- `chrmrtns_kla_button_color` - Button color
- `chrmrtns_kla_button_hover_color` - Button hover color
- `chrmrtns_kla_link_color` - Link color
- `chrmrtns_kla_link_hover_color` - Link hover color
- `chrmrtns_kla_smtp_settings` - SMTP configuration array
- `chrmrtns_kla_mail_logging_enabled` - Enable/disable logging
- `chrmrtns_kla_mail_log_size_limit` - Max number of logs to keep

### User Meta Keys
- `chrmrtns_kla_login_token` - Temporary login token
- `chrmrtns_kla_login_token_expiration` - Token expiration timestamp

### WordPress.org Submission Details
- **SVN Repository:** https://plugins.svn.wordpress.org/keyless-auth/
- **Plugin Page:** https://wordpress.org/plugins/keyless-auth/
- **Author Username:** chrmrtns (not christianmartens)
- **Required Assets:** icon-256x256.png, banner-772x250.png, screenshot-1.png through screenshot-7.png

### Development Notes
1. **Hooks:** Plugin initializes on `plugins_loaded` (not `init`) for better compatibility
2. **Mail Logging:** Uses custom post type with 17-char limit for name
3. **SMTP:** Requires `$phpmailer->From` to match authenticated sender
4. **JavaScript:** Admin JS handles color pickers, template toggles, SMTP settings
5. **Security:** All user input sanitized, all output escaped, nonces verified

### Testing Checklist
- [ ] Shortcode `[keyless-auth]` displays login form
- [ ] Email sends with magic link
- [ ] Login link works and expires after 10 minutes
- [ ] SMTP settings save and work
- [ ] Mail logging captures emails
- [ ] Email templates display correctly
- [ ] Color customization works
- [ ] wp-config.php credential storage toggle works

### Common Issues & Solutions
1. **Mail not sending:** Check SMTP settings, ensure From email matches SMTP username
2. **Mail logs not saving:** Post type name must be under 20 chars
3. **JavaScript not working:** Ensure admin-script.js is enqueued properly
4. **Fatal errors:** Check for typos in escaping functions (esc_attr vs esc_attresc_html_e)

### Git & SVN Commands
```bash
# GitHub
git add .
git commit -m "message"
git push origin main

# WordPress.org SVN
cd keyless-auth-svn
svn add [files]
svn commit --username chrmrtns -m "message"
```

## Important URLs
- **GitHub:** https://github.com/chrmrtns/keyless-auth
- **WordPress.org:** https://wordpress.org/plugins/keyless-auth/
- **Support:** https://wordpress.org/support/plugin/keyless-auth/
- **Donate:** https://paypal.me/chrmrtns

## Version History
- **2.0.12:** Settings link in plugin list, mail logs view button fix (SVN deployment pending)
- **2.0.11:** Critical SMTP/logging fixes, JS restoration
- **2.0.10:** WordPress.org compliance fixes
- **2.0.9:** Complete rebrand to Keyless Auth
- **2.0.8:** Enhanced output escaping
- **2.0.7:** Full Plugin Check compliance
- **2.0.6:** Fixed placeholder token rendering
- **2.0.5:** Two-field email template system

## 📋 TODO for Tomorrow
- [ ] Deploy v2.0.12 to WordPress.org SVN repository (create tags/2.0.12)
- [ ] Update stable tag in SVN trunk
- [ ] Verify deployment on WordPress.org plugin page

---
*Last Updated: September 16, 2025*
*This summary allows future AI assistants to understand the plugin structure without reading all code files.*