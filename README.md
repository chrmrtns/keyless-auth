# Keyless Auth - Login without Passwords

**Secure keyless authentication allowing users to login without passwords via email magic links. Enhanced with customizable templates and improved security.**

![Version](https://img.shields.io/badge/version-2.0.11-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-3.9%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)

## 🚀 Description

**Forget passwords. Let your users log in with a secure magic link sent to their email — fast, stylish, and hassle-free.** Includes customizable email templates, SMTP support, full logging, and a beautiful WYSIWYG editor.

## ✨ New Features in v2.0.11

* **📧 Critical SMTP Fix** - Fixed sender email not being used, emails now properly send from configured SMTP address
* **📝 Fixed Mail Logging** - Resolved post type name length issue preventing mail logs from being saved
* **🔧 Fixed wp-config.php Instructions** - Restored missing JavaScript for credential storage toggle display  
* **🐛 Fixed Fatal Errors** - Resolved multiple undefined function errors in Mail Logger page
* **🔍 Enhanced Diagnostics** - Added diagnostic information to help troubleshoot mail logging issues

## 🔐 Features in v2.0.10

* **🛡️ WordPress.org Plugin Check Compliance** - Resolved all input validation and sanitization warnings
* **🔒 Enhanced Security** - Fixed wp_unslash() issues and removed insecure duplicate form processing
* **⚡ Improved Code Quality** - Eliminated security vulnerabilities in POST data handling
* **🧹 Code Cleanup** - Removed redundant save_settings() method that bypassed security checks

## 🔧 Features in v2.0.9

* **🏷️ WordPress.org Ready** - Complete rebrand to "Keyless Auth" for WordPress.org compliance
* **🔧 Enhanced Prefixes** - All functions/classes use unique "chrmrtns_kla_" prefixes
* **🛡️ Security Hardening** - Improved nonce verification with proper sanitization
* **⚡ Performance Optimized** - Converted inline JS/CSS to proper wp_enqueue system
* **📋 Code Compliance** - Full WordPress.org Plugin Check compliance
* **🎯 Simplified Shortcode** - New [keyless-auth] shortcode (was [chrmrtns-passwordless-auth])

## 🔒 Features in v2.0.8

* **🔒 Security Improvements** - Enhanced output escaping compliance with esc_html_e() and wp_kses()
* **🎨 Template Preview Security** - Email template previews use controlled HTML allowlists
* **🖱️ Button Text Colors** - Fixed button text color controls to prevent blue hover text issues
* **🛡️ WordPress.org Compliance** - Comprehensive escaping improvements for enhanced security

## 🛡️ Features in v2.0.7

* **🛡️ WordPress.org Compliance** - Full Plugin Check compliance for WordPress.org submission
* **🔒 Security Hardening** - Enhanced output escaping and input validation
* **⚡ Performance Optimized** - Improved database queries and conditional debug logging
* **📋 Code Quality** - Complete adherence to WordPress coding and security standards
* **🔐 Enhanced Protection** - Advanced CSRF and timing attack mitigation

## 🔧 Features in v2.0.6

* **🔧 Fixed Placeholder Token Rendering** - Button backgrounds now display correctly in custom templates
* **📝 WYSIWYG-Safe Placeholders** - Changed from {{PLACEHOLDER}} to [PLACEHOLDER] format to prevent editor corruption
* **🎨 Better Email Structure** - Full-width gradient background with 600px content area for professional appearance
* **✅ Reliable Color Replacement** - Template placeholders are properly replaced with actual colors in all scenarios

## ✨ Features in v2.0.5

* **📝 Two-Field Email Template System** - Separate WYSIWYG body content from optional CSS styles
* **🎨 Enhanced Template Editor** - Body content uses inline styles, CSS styles go in head section
* **🔧 WYSIWYG Compatibility** - No more editor corruption of HTML structure or CSS classes
* **📐 2x2 Grid Preview Layout** - Template previews now display in compact grid instead of vertical stack
* **🎯 Advanced Customization** - Choose inline-only styles OR use CSS classes with separate stylesheet field

## 🔐 Features in v2.0.4

* **🔐 Secure Credential Storage** - Choose between database or wp-config.php storage for SMTP credentials
* **🛡️ Enhanced Security** - wp-config.php option keeps sensitive credentials outside the web root
* **⚙️ Flexible Configuration** - Toggle between storage methods with clear visual indicators

## 🎯 Features in v2.0.3

* **🔗 Login Link Reliability** - Fixed critical issue where login links weren't processing correctly
* **⚙️ Enhanced Hook System** - Improved WordPress hook integration for better compatibility
* **🚀 Streamlined Code** - Removed debug logging for production-ready performance

## 🎯 Features in v2.0.2

* **🏷️ Custom Sender Names** - Force custom "From" names for all emails with toggle control
* **📊 Login Success Tracking** - Dynamic counter showing total successful passwordless logins
* **🔧 Enhanced Mail Logging** - Fixed compatibility issues with other SMTP plugins

## 🎯 Key Features

### 🏗️ **Modular Architecture**
Complete code refactoring with clean, maintainable class structure

### 📧 **SMTP Configuration** 
Full SMTP support for reliable email delivery with major providers

### 📝 **Email Logging & Monitoring**
Track and monitor all emails sent from WordPress

### 🎨 **Visual Email Editor**
WYSIWYG editor with HTML support for custom templates

### 🎨 **Advanced Color Controls**
Support for hex, RGB, HSL, and HSLA color formats

### 👀 **Template Previews**
Live preview of email templates before selection

### 🔗 **Link Color Customization**
Separate color controls for buttons and text links

### 🔒 **Enhanced Security**
Comprehensive nonce verification and input sanitization

## 🔧 How It Works

1. **User enters email/username** instead of password
2. **Secure token generated** and stored with enhanced validation
3. **Beautifully styled email sent** with login button
4. **User clicks button** and is automatically logged in
5. **Token expires** after 10 minutes for maximum security

## 📥 Installation

1. Upload the `keyless-auth` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Keyless Auth > Templates** to configure templates and colors
4. Create a page and use the shortcode `[keyless-auth]`

## 🎮 Usage

Simply add the shortcode to any page or widget:

```
[keyless-auth]
```

## ⚙️ Configuration

### Email Templates
- Choose from predefined templates (German, English, Simple)
- Create custom HTML templates with WYSIWYG editor
- Live preview before applying changes

### SMTP Settings
- Configure SMTP for reliable email delivery
- Support for Gmail, Outlook, Mailgun, SendGrid, and more
- Automatic port configuration for SSL/TLS
- Custom sender name control
- Connection testing tools

### Mail Logging
- Monitor all emails sent from WordPress
- View complete email history with timestamps
- Preview email content for troubleshooting
- Automatic log cleanup with size limits

## 🔒 Security Features

- **Secure token generation** using `wp_hash()` with user ID, timestamp, and salt
- **Timing attack protection** with `hash_equals()`
- **Token expiration** - 10 minutes maximum
- **One-time use** tokens automatically deleted after use
- **Enhanced input sanitization** for all form fields
- **Comprehensive nonce verification** for all admin actions

## 🎨 Customization

### Colors
- Button colors with hover states
- Link colors with hover states
- Support for multiple color formats (hex, RGB, HSL, HSLA)
- Color picker and manual input synchronization

### Templates
Available placeholders for custom templates:
- `{{TO}}` - Recipient email address
- `{{LOGIN_URL}}` - Login URL
- `{{BUTTON_COLOR}}` - Button color
- `{{BUTTON_HOVER_COLOR}}` - Button hover color
- `{{LINK_COLOR}}` - Link color
- `{{LINK_HOVER_COLOR}}` - Link hover color

## ❓ FAQ

### Is this secure?
Yes. Tokens are created using `wp_hash()` based on user ID, timestamp, and WordPress salt. Tokens expire after 10 minutes and can only be used once.

### Can I customize email templates?
Absolutely! Go to **Passwordless Auth > PA Settings** to choose from predefined templates or create custom HTML templates using the WYSIWYG editor.

### How do I configure SMTP?
Go to **Passwordless Auth > SMTP** to configure settings for providers like Gmail, Outlook, Mailgun, SendGrid, and more. Includes automatic port configuration and connection testing.

### Can I track sent emails?
Yes! The Mail Logs feature monitors all emails with timestamps, recipients, subjects, and content preview. Perfect for troubleshooting delivery issues.

### Can I customize the sender name?
Absolutely! In SMTP settings, enable "Force From Name" and set a custom sender name for professional, branded emails.

### Can I store SMTP credentials securely?
Yes! Choose between database storage or wp-config.php storage. For maximum security, use wp-config.php by adding:
```php
define('CHRMRTNS_PA_SMTP_USERNAME', 'your-email@example.com');
define('CHRMRTNS_PA_SMTP_PASSWORD', 'your-smtp-password');
```

## 🔄 Changelog

### v2.0.11
- **FIX:** SMTP sender email not being used - Added missing `$phpmailer->From` to properly authenticate emails
- **FIX:** Mail logging post type registration - Fixed post type name length issue (shortened from 22 to 17 characters)
- **FIX:** wp-config.php instructions not displaying - Restored JavaScript and added inline fallback for credential storage toggle
- **FIX:** Fatal errors on Mail Logger page - Fixed multiple `esc_attresc_html_e()` typos causing page crashes
- **FIX:** Plugin initialization timing - Changed from 'init' to 'plugins_loaded' hook for better component loading
- **IMPROVEMENT:** Added diagnostic information box to Mail Logs page for troubleshooting

### v2.0.10
- **SECURITY:** WordPress.org Plugin Check compliance - Fixed all input validation and sanitization warnings
- **SECURITY:** Enhanced POST data handling - Added wp_unslash() before all sanitization functions
- **SECURITY:** Removed duplicate save_settings() method - Eliminated insecure form processing that bypassed nonce verification
- **IMPROVEMENT:** $_SERVER validation - Added proper isset() checks for superglobal access
- **IMPROVEMENT:** Code cleanup - Removed redundant form processing methods to prevent security gaps

### v2.0.9
- **BREAKING:** Plugin renamed to "Keyless Auth - Login without Passwords" for WordPress.org compliance  
- **BREAKING:** Plugin slug changed to "keyless-auth" (old: "passwordless-auth")
- **BREAKING:** Text domain changed to "keyless-auth" (old: "passwordless-auth")
- **IMPROVEMENT:** All prefixes updated to "chrmrtns_kla_" for uniqueness and compliance
- **IMPROVEMENT:** Nonce verification enhanced with proper sanitization (wp_unslash + sanitize_text_field)
- **IMPROVEMENT:** Converted all inline JavaScript/CSS to proper wp_enqueue system
- **IMPROVEMENT:** Removed WordPress.org directory assets from plugin ZIP
- **IMPROVEMENT:** Enhanced WordPress.org Plugin Check compliance
- **IMPROVEMENT:** Shortcode changed to [keyless-auth] (old: [chrmrtns-passwordless-auth])

### v2.0.8
- **IMPROVEMENT:** Enhanced output escaping compliance - All user-facing content now uses proper WordPress escaping functions
- **IMPROVEMENT:** Template preview security - Email template previews now use wp_kses with controlled HTML allowlists
- **IMPROVEMENT:** Admin interface escaping - Form outputs and translations properly escaped with esc_html_e() and wp_kses()
- **IMPROVEMENT:** Email template escaping - All template rendering functions now use proper escaping for security
- **IMPROVEMENT:** Button text color functionality - Fixed button text color controls to prevent blue hover text issues
- **SECURITY:** WordPress.org Plugin Check compliance - Comprehensive escaping improvements for enhanced security

### v2.0.7
- **COMPLIANCE:** Full WordPress.org Plugin Check compliance - All security and coding standards met
- **FIX:** Output escaping - All user-facing content properly escaped for security
- **FIX:** Input validation - Enhanced nonce verification and superglobal sanitization
- **FIX:** Database queries - Optimized user meta queries for better performance
- **FIX:** Debug code - Conditional debug logging only when WP_DEBUG is enabled
- **IMPROVEMENT:** Code quality - Added comprehensive phpcs ignore comments for legitimate use cases
- **IMPROVEMENT:** Security hardening - Enhanced protection against timing attacks and CSRF
- **IMPROVEMENT:** WordPress standards - Full compliance with WordPress coding and security standards

### v2.0.6
- **FIX:** Fixed placeholder token rendering - Button backgrounds now display correctly in custom templates
- **IMPROVEMENT:** WYSIWYG-safe placeholders - Changed from {{PLACEHOLDER}} to [PLACEHOLDER] format to prevent editor corruption
- **IMPROVEMENT:** Better email structure - Full-width gradient background with 600px content area for professional appearance
- **IMPROVEMENT:** Reliable color replacement - Template placeholders are properly replaced with actual colors in all scenarios

### v2.0.5
- **NEW:** Two-field email template system - Separate WYSIWYG body content from optional CSS styles
- **NEW:** Enhanced template editor - Body content uses inline styles, CSS styles go in head section
- **IMPROVEMENT:** WYSIWYG compatibility - No more editor corruption of HTML structure or CSS classes
- **IMPROVEMENT:** 2x2 grid preview layout - Template previews now display in compact grid instead of vertical stack
- **IMPROVEMENT:** Advanced customization - Choose inline-only styles OR use CSS classes with separate stylesheet field

### v2.0.4
- **NEW:** Secure credential storage options - Choose between database or wp-config.php storage for SMTP credentials
- **NEW:** wp-config.php constants support - Use CHRMRTNS_PA_SMTP_USERNAME and CHRMRTNS_PA_SMTP_PASSWORD constants
- **IMPROVEMENT:** Enhanced security - Keep sensitive SMTP credentials outside the web root in wp-config.php
- **IMPROVEMENT:** Dynamic field toggles - Visual indicators show which storage method is active and if constants are defined
- **IMPROVEMENT:** Better credential management - Automatic detection and validation of wp-config.php constants

### v2.0.3
- **FIX:** Critical login link functionality - Fixed issue where login links weren't processing properly on some WordPress configurations
- **IMPROVEMENT:** Enhanced hook system - Changed from 'init' to 'wp_loaded' hook for better login link processing reliability
- **IMPROVEMENT:** Code optimization - Removed debug logging for cleaner, production-ready performance
- **FIX:** WordPress compatibility - Improved hook timing to ensure login links work across different hosting environments

### v2.0.2
- **NEW:** Custom sender name control with toggle checkbox
- **NEW:** Login success tracking with dynamic counter
- **NEW:** JavaScript field toggles for better UX
- **FIX:** Mail logging compatibility with other SMTP plugins
- **FIX:** Improved wp_mail filter handling
- **SECURITY:** Proper sanitization for custom sender names

### v2.0.1
- **NEW:** Modular class-based architecture
- **NEW:** Complete SMTP configuration system
- **NEW:** Email logging and monitoring system
- **NEW:** Test email functionality
- **SECURITY:** Enhanced nonce verification for all forms
- **IMPROVEMENT:** Professional admin interface

## 🤝 Contributing

Issues and pull requests are welcome on [GitHub](https://github.com/chrmrtns/passwordless-auth).

## 📄 License

This plugin is licensed under the [GPL v2](https://www.gnu.org/licenses/gpl-2.0.html) or later.

## 👨‍💻 Author

**Chris Martens**
- GitHub: [@chrmrtns](https://github.com/chrmrtns)
- Plugin URI: [https://github.com/chrmrtns/passwordless-auth](https://github.com/chrmrtns/passwordless-auth)

---

⭐ **If this plugin helps you, please consider giving it a star on GitHub!**