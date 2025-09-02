# Passwordless Auth

**Enhanced passwordless authentication with modular architecture, customizable email templates, and improved security.**

![Version](https://img.shields.io/badge/version-2.0.2-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-3.9%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)

## 🚀 Description

**Forget passwords. Let your users log in with a secure magic link sent to their email — fast, stylish, and hassle-free.** Includes customizable email templates, SMTP support, full logging, and a beautiful WYSIWYG editor.

## ✨ New Features in v2.0.2

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

1. Upload the `passwordless-auth` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Passwordless Auth > PA Settings** to configure templates and colors
4. Create a page and use the shortcode `[chrmrtns-passwordless-auth]`

## 🎮 Usage

Simply add the shortcode to any page or widget:

```
[chrmrtns-passwordless-auth]
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

## 🔄 Changelog

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