=== Passwordless Auth ===

Contributors: chrmrtns, sareiodata, cozmoslabs
Donate link: https://github.com/chrmrtns/passwordless-auth
Tags: passwordless login, passwordless, front-end login, login shortcode, custom login form, login without password, passwordless authentication, security, email templates, smtp, mail logging, modular architecture
Requires at least: 3.9
Tested up to: 6.7.1
Stable tag: 2.0.6


Enhanced passwordless authentication with modular architecture, customizable email templates, and improved security.


== Description ==

**Forget passwords. Let your users log in with a secure magic link sent to their email — fast, stylish, and hassle-free. Includes customizable email templates, SMTP support, full logging, and a beautiful WYSIWYG editor.

**New Features in v2.0.6:**
* **🔧 Fixed Placeholder Token Rendering** - Button backgrounds now display correctly in custom templates
* **📝 WYSIWYG-Safe Placeholders** - Changed from {{PLACEHOLDER}} to [PLACEHOLDER] format to prevent editor corruption
* **🎨 Better Email Structure** - Full-width gradient background with 600px content area for professional appearance
* **✅ Reliable Color Replacement** - Template placeholders are properly replaced with actual colors in all scenarios

**Features in v2.0.5:**
* **✨ Two-Field Email Template System** - Separate WYSIWYG body content from optional CSS styles
* **🎨 Enhanced Template Editor** - Body content uses inline styles, CSS styles go in head section
* **🔧 WYSIWYG Compatibility** - No more editor corruption of HTML structure or CSS classes
* **📐 2x2 Grid Preview Layout** - Template previews now display in compact grid instead of vertical stack
* **🎯 Advanced Customization** - Choose inline-only styles OR use CSS classes with separate stylesheet field

**Features in v2.0.4:**
* **🔐 Secure Credential Storage** - Choose between database or wp-config.php storage for SMTP credentials
* **🛡️ Enhanced Security** - wp-config.php option keeps sensitive credentials outside the web root
* **⚙️ Flexible Configuration** - Toggle between storage methods with clear visual indicators

**Features in v2.0.3:**
* **Login Link Reliability** - Fixed critical issue where login links weren't processing correctly
* **Enhanced Hook System** - Improved WordPress hook integration for better compatibility
* **Streamlined Code** - Removed debug logging for production-ready performance

**Features in v2.0.2:**
* **Custom Sender Names** - Force custom "From" names for all emails with toggle control
* **Login Success Tracking** - Dynamic counter showing total successful passwordless logins
* **Enhanced Mail Logging** - Fixed compatibility issues with other SMTP plugins

**Features in v2.0.1:**
* **Modular Architecture** - Complete code refactoring with clean, maintainable class structure
* **SMTP Configuration** - Full SMTP support for reliable email delivery with major providers
* **Email Logging & Monitoring** - Track and monitor all emails sent from WordPress
* **Visual Email Editor** - WYSIWYG editor with HTML support for custom templates
* **Advanced Color Controls** - Support for hex, RGB, HSL, and HSLA color formats
* **Template Previews** - Live preview of email templates before selection
* **Link Color Customization** - Separate color controls for buttons and text links
* **Enhanced Menu Structure** - Dedicated top-level admin menu with subpages
* **Professional Email Templates** - Styled German and Simple English templates
* **Enhanced Security** - Comprehensive nonce verification and input sanitization

This is how it works:

* Instead of asking users for a password when they try to log in to your website, we simply ask them for their username or email
* The plugin creates a temporary authorization token and saves it securely with enhanced validation
* Then we send the user a beautifully styled email with a login button
* The user clicks the button and is automatically logged in
* Tokens expire after 10 minutes and can only be used once for maximum security

You can use the shortcode [chrmrtns-passwordless-auth] in a page or widget.

If you're looking to create front-end user registration and profile forms we recommend [Profile Builder](https://www.cozmoslabs.com/wordpress-profile-builder/). 

NOTE:

Passwordless Authentication does not replace the default login functionality in WordPress.


== Installation ==

1. Upload the passwordless-auth folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Passwordless Auth > PA Settings to configure email templates and colors
1. Create a new page and use the shortcode [chrmrtns-passwordless-auth]

== Frequently Asked Questions ==

= Is this secure? =

	Yes. The token is created using wp_hash and it's based on the user id, the current time and the salt in wp-config.php

= Couldn't anyone login if they have that link? =

	The token expires after 10 minutes and can only be used once. If people have access to that link it's supposed they have access to your email, in which case it's as safe as the default login, since they could reset their passwords.

= Isn't it more complicated they just entering a password? =

	Weak passwords are used every day by users. There are also people who use the same password across various services and websites. By using the Passwordless Login plugin your users will have one less password to worry about.

= But what if my users don't want to login every time via their email?  =

	You can extend the auth cookie expiration to something like 1 month or 3 months (this can be changed by using the wpa_change_link_expiration filter). Also, you can offer Passwordless Login as an alternative login system and enforce stronger passwords on registration using <a href="http://wordpress.org/plugins/profile-builder/">Profile Builder plugin.</a>

= How do I customize the email templates? =

	Go to Passwordless Auth > PA Settings in your WordPress admin. You can choose from predefined templates (German or English) or create your own custom HTML template using the built-in WYSIWYG editor with full HTML and CSS support.

= Can I change the colors in the emails? =

	Yes! In the settings page, you can customize button colors, button hover colors, and link colors. Supports multiple color formats including hex (#007bff), RGB (rgb(0,123,255)), HSL (hsl(211,100%,50%)), and HSLA colors. You can use either color pickers or enter color codes manually.

= Does the custom email editor support HTML? =

	Absolutely! The custom template editor includes a full WYSIWYG editor with HTML support. You can create rich email templates with custom styling, different font sizes, colors, tables, and more. Switch between visual and HTML editing modes as needed.

= How do I configure SMTP for reliable email delivery? =

	Go to Passwordless Auth > SMTP in your WordPress admin. You can configure SMTP settings for providers like Gmail, Outlook, Mailgun, SendGrid, and more. The plugin includes automatic port configuration for SSL/TLS, connection testing, and debug tools to ensure your emails are delivered reliably.

= Can I track what emails are being sent? =

	Yes! The Mail Logs feature allows you to monitor all emails sent from your WordPress site. You can view complete email history with timestamps, recipients, subjects, and even preview the full email content. This is perfect for troubleshooting delivery issues and monitoring your site's email activity.

= Can I customize the sender name in emails? =

	Absolutely! In the SMTP settings, you can enable "Force From Name" and set a custom sender name like "Your Website Name" instead of just showing the email address. This makes emails look more professional and branded. The feature includes a toggle control so you can easily enable or disable it.

= Can I store SMTP credentials securely outside the database? =

	Yes! Version 2.0.4 introduces secure credential storage options. You can choose to store SMTP username and password in wp-config.php instead of the database. This is more secure as wp-config.php is typically outside the web root. Simply add these constants to your wp-config.php:
	
	`define('CHRMRTNS_PA_SMTP_USERNAME', 'your-email@example.com');`
	`define('CHRMRTNS_PA_SMTP_PASSWORD', 'your-smtp-password');`
	
	Then select "Store in wp-config.php" in the SMTP settings.

= What's different from the original Passwordless Login plugin? =

	This enhanced version includes: modular class-based architecture, SMTP configuration, email logging & monitoring, WYSIWYG email editor, visual template previews, advanced color controls (hex/RGB/HSL), separate button and link colors, dedicated admin menu, enhanced security with comprehensive nonce verification, HTML email support, and professional styling options.

= What does the modular architecture mean for developers? =

	In v2.0.1, we completely refactored the plugin from a single 1868-line file into clean, organized classes. Each functionality (authentication, SMTP, mail logging, email templates, admin interface) is now in its own dedicated class file. This makes the code much easier to maintain, extend, and debug. Developers can now easily customize specific features without affecting others.

= I can't find a question similar to my issue; Where can I find support? =

	For support with the original functionality, visit http://www.cozmoslabs.com. For issues specific to this enhanced version, please check the GitHub repository.


== Screenshots ==

1. Front-end login form - Clean, simple passwordless authentication interface
2. Main admin dashboard - Overview with shortcode info and success counter
3. Email template settings - Choose from predefined templates or create custom ones
4. WYSIWYG email editor - Full HTML support for custom email templates
5. SMTP configuration - Secure credential storage with wp-config.php option
6. Mail logs - Track all sent emails with timestamps and preview
7. Test email functionality - Verify SMTP settings with one-click testing
8. Email preview - See exactly how your login emails will look


== Changelog ==
= 2.0.6 =
* FIX: Placeholder token rendering - Button backgrounds now display correctly in custom email templates
* FIX: WYSIWYG editor corruption - Changed placeholder format from {{PLACEHOLDER}} to [PLACEHOLDER] to prevent corruption
* IMPROVEMENT: Email template structure - Full-width gradient background with centered 600px content area
* IMPROVEMENT: Color replacement reliability - Enhanced placeholder replacement with proper fallback handling
* IMPROVEMENT: Better email client compatibility - Optimized HTML structure for professional email appearance

= 2.0.5 =
* NEW: Two-field email template system - Separate WYSIWYG body content from optional CSS styles
* NEW: Enhanced template editor - Body content editor with inline styles, separate CSS styles field
* NEW: 2x2 grid preview layout - Template previews now display in compact grid format
* IMPROVEMENT: WYSIWYG compatibility - No more editor corruption of HTML structure or CSS classes  
* IMPROVEMENT: Advanced email customization - Choose between inline-only styles or CSS classes with stylesheet
* IMPROVEMENT: Better email structure - Automatic HTML document assembly with proper head/meta tags for email clients
* IMPROVEMENT: Flexible template creation - Users can work with familiar WYSIWYG tools while maintaining email compatibility

= 2.0.4 =
* NEW: Secure credential storage options - Choose between database or wp-config.php storage for SMTP credentials
* NEW: wp-config.php constants support - Use CHRMRTNS_PA_SMTP_USERNAME and CHRMRTNS_PA_SMTP_PASSWORD constants
* IMPROVEMENT: Enhanced security - Keep sensitive SMTP credentials outside the web root in wp-config.php
* IMPROVEMENT: Dynamic field toggles - Visual indicators show which storage method is active and if constants are defined
* IMPROVEMENT: Better credential management - Automatic detection and validation of wp-config.php constants

= 2.0.3 =
* FIX: Critical login link functionality - Fixed issue where login links weren't processing properly on some WordPress configurations
* IMPROVEMENT: Enhanced hook system - Changed from 'init' to 'wp_loaded' hook for better login link processing reliability
* IMPROVEMENT: Code optimization - Removed debug logging for cleaner, production-ready performance
* FIX: WordPress compatibility - Improved hook timing to ensure login links work across different hosting environments

= 2.0.2 =
* NEW: Custom sender name control - Force custom "From" names for all emails with toggle checkbox
* NEW: Login success tracking - Dynamic counter showing total successful passwordless logins in admin
* NEW: JavaScript field toggles for better UX in SMTP settings
* FIX: Mail logging compatibility - Fixed fatal error with other SMTP plugins (Fluent SMTP, etc.)
* FIX: Improved wp_mail filter handling for better plugin compatibility
* IMPROVEMENT: Enhanced admin dashboard with live success counter display
* SECURITY: Added proper sanitization for custom sender names

= 2.0.1 =
* NEW: Modular class-based architecture - Complete refactoring from 1868 lines to organized class structure
* NEW: Complete SMTP configuration system with support for major email providers
* NEW: Email logging and monitoring system to track all sent emails
* NEW: Test email functionality for SMTP configuration validation
* NEW: Advanced dashboard with feature overview and quick access buttons
* SECURITY: Enhanced nonce verification for all form submissions (SMTP settings, mail logs)
* SECURITY: Added comprehensive input sanitization and capability checks
* IMPROVEMENT: Moved plugin logo positioning to prevent overlap with notifications
* IMPROVEMENT: Enhanced admin interface with dedicated top-level menu structure
* IMPROVEMENT: Added comprehensive email management capabilities
* IMPROVEMENT: Updated FAQ section with SMTP and email logging information
* DEVELOPER: Clean separation of concerns with dedicated classes for each functionality

= 2.0.0 =
* NEW: Complete rebrand to "Passwordless Auth" with chrmrtns prefix by Chris Martens
* NEW: Dedicated top-level admin menu "Passwordless Auth" with PA Settings submenu
* NEW: Visual template selection with live previews of each email template
* NEW: WYSIWYG email editor with HTML support for custom templates
* NEW: Advanced color controls supporting hex, RGB, HSL, HSLA formats
* NEW: Separate color customization for buttons, button hover, and text links
* NEW: Enhanced HTML sanitization allowing email-safe tags and attributes
* NEW: Template help section with placeholder documentation and HTML examples
* NEW: Improved email templates with better styling and link color support
* NEW: Color picker and text input synchronization for flexible color entry
* REMOVED: Profile Builder promotional section completely eliminated
* SECURITY: Fixed timing attack vulnerability in token comparison
* SECURITY: Added proper REQUEST_URI validation in URL generation
* SECURITY: Consistent input sanitization using $_GET instead of $_REQUEST
* SECURITY: Added validation before user meta deletion
* SECURITY: Enhanced HTML sanitization for email content
* IMPROVEMENT: Updated all function prefixes from wpa_ to chrmrtns_
* IMPROVEMENT: Updated shortcode to [chrmrtns-passwordless-auth]
* IMPROVEMENT: Enhanced email styling with responsive design
* IMPROVEMENT: Better error handling and validation throughout
* IMPROVEMENT: Dynamic TinyMCE editor initialization for better compatibility

= 1.1.3 =
* Fix: XSS issue with the already logged in message. Thanks to Mat Rollings
* Fix: Added nonce check for the admin notice dismiss action
* Fix: Sanitize additional output
* Fix: A compatibility bug with Profile Builder when an after login redirect returned an empty string 

= 1.1.2 =
* Fix: issues with form being processed multiple times
* Fix: an issue regarding AV Link Protection
* Misc: added a filter over the headers of the email that is sent: wpa_email_headers
* Misc: added a filter to allow adding of extra email verification logic: wpa_email_verify_login

= 1.1.1 =
* Redirect after login based on Profile Builder Pro custom redirects.

= 1.1.0 =
* Fix create_function to anonymous function so it works with PHP 7.2
* Localize certain strings
* Add wpa_after_login_redirect filter so you can redirect users after login
* Change logo and banner

= 1.0.9 =
* Fixed a problem with admin approval error message

= 1.0.8 =
* Added compatibility with Admin Approval from Profile Builder

= 1.0.7 =
* Fix: Properly localize plugin again. Changed the text domain to be the same with the slug.

= 1.0.6 =
* Fix: Properly localize plugin.

= 1.0.5 =
* Fix: Fixed an issue with the Email Content Type. Now we are using the wp_mail_content_type filter to set this.
* Plugin security improvements.

= 1.0.4 =
* Fix: Remove email 'from' filter. Should use wp_mail_from filter.
* Added support for HTML inside the e-mail that gets sent.
* Added the wpa_change_link_expiration filter to be able to change the lifespan of the token.
* Added the wpa_change_form_label to be able to change the label for the login form. The label also changes automatically now based on the value of the Allow Users to * Login With option set in Profile Builder -> Manage Fields.
* Fix: Generating the url using add_query_args() function.

= 1.0.3 =
Fix: Minor readme change

= 1.0.2 =
Fix: Added require_once for the PasswordHash class

= 1.0.1 =
* Security fix: tokens are now hashed in the database.
* Security fix: sanitized the input fields data.
* Fix: no longer using transients. Now using user_meta with an expiration meta since transients are not to be trusted.
* Change: removed a br tag.

= 1.0 =
Initial version. Added a passwordless login form as a shortcode.