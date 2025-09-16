# Contributing to Keyless Auth

Thank you for your interest in contributing to Keyless Auth! This guide will help you understand the codebase and contribute effectively.

## 🏗️ Architecture Overview

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
│   ├── admin-script.js                 # Admin JavaScript
│   ├── admin-style.css                 # Admin CSS
│   └── style-front-end.css            # Frontend styles
└── languages/
    └── passwordless-login.pot         # Translation template
```

## 🔧 Development Guidelines

### Naming Conventions
- **Plugin Prefix:** `chrmrtns_kla_` (all functions, options, classes)
- **Class Prefix:** `Chrmrtns_KLA_` (e.g., Chrmrtns_KLA_Core)
- **Constants:** `CHRMRTNS_KLA_VERSION`, `CHRMRTNS_KLA_PLUGIN_DIR`, etc.
- **Text Domain:** `keyless-auth`
- **Shortcode:** `[keyless-auth]`

### Database Options
All plugin options use the `chrmrtns_kla_` prefix:
- `chrmrtns_kla_email_template` - Selected email template
- `chrmrtns_kla_custom_email_body` - Custom email HTML
- `chrmrtns_kla_button_color` - Button color
- `chrmrtns_kla_smtp_settings` - SMTP configuration
- `chrmrtns_kla_mail_logging_enabled` - Enable/disable logging

### User Meta Keys
- `chrmrtns_kla_login_token` - Temporary login token
- `chrmrtns_kla_login_token_expiration` - Token expiration timestamp

## 🔒 Security Requirements

### Input Validation
- Always use `wp_verify_nonce()` with `wp_unslash()` and `sanitize_text_field()`
- Sanitize all user input before processing
- Validate email addresses with `is_email()`

### Output Escaping
- Use `esc_html_e()` for translatable text
- Use `esc_attr()` for HTML attributes
- Use `esc_url()` for URLs
- Use `wp_kses()` for HTML content with controlled tags

### Example:
```php
// Nonce verification
if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'action')) {
    return;
}

// Output escaping
echo esc_html_e('Text', 'keyless-auth');
echo '<a href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
```

## 🚀 Getting Started

1. **Clone the repository:**
   ```bash
   git clone https://github.com/chrmrtns/keyless-auth.git
   ```

2. **Install in WordPress:**
   - Copy the plugin folder to `wp-content/plugins/`
   - Activate through WordPress admin

3. **Development Setup:**
   - Enable `WP_DEBUG` in wp-config.php for development
   - Use WordPress Coding Standards for PHP

## 📝 Making Changes

### Before Contributing
1. Check existing issues and pull requests
2. Test your changes thoroughly
3. Ensure WordPress Plugin Check compliance
4. Update documentation if needed

### Code Style
- Follow WordPress Coding Standards
- Use proper escaping and sanitization
- Add inline documentation for complex functions
- Keep functions focused and single-purpose

### Testing Checklist
- [ ] Shortcode `[keyless-auth]` displays correctly
- [ ] Email sends with magic link
- [ ] Login link works and expires properly
- [ ] SMTP settings save and function
- [ ] Mail logging captures emails
- [ ] Admin interface works correctly

## 🐛 Reporting Issues

When reporting issues, please include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Error messages (if any)

## 📤 Submitting Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes with clear messages
4. Push to your fork
5. Submit a pull request with:
   - Clear description of changes
   - Reference to related issues
   - Testing confirmation

## 📄 License

This plugin is licensed under GPL v2 or later. By contributing, you agree that your contributions will be licensed under the same license.

## 📞 Contact

- **GitHub Issues:** [Report bugs or request features](https://github.com/chrmrtns/keyless-auth/issues)
- **Author:** Chris Martens (@chrmrtns)

Thank you for helping make Keyless Auth better!