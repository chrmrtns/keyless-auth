=== Keyless Auth - Login without Passwords ===

Contributors: chrmrtns, sareiodata, cozmoslabs
Donate link: https://paypal.me/chrmrtns
Tags: passwordless, login, authentication, security, email
Requires at least: 3.9
Tested up to: 6.8
Stable tag: 2.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Secure keyless authentication - users login via email magic links without passwords. Customizable templates and enhanced security.


== Description ==

**Keyless Auth - Login without Passwords** allows users to securely login to your WordPress site without remembering passwords. Simply enter their email, and they receive a magic login link - secure, fast, and user-friendly.

**🔧 PATCH v2.4.1 STATUS:** This is a stability patch that temporarily disables 2FA authentication hooks to resolve login conflicts. Emergency mode, grace period notifications (with beautiful colors!), and core passwordless login functionality are fully operational. Complete 2FA authentication will be restored in v2.4.2.

**🔐 Feature Overview**

**✅ Ready:**
• Passwordless Login via Email – secure, simple, password-free
• Emergency Mode & Grace Period System – enhanced with colorful notifications and proper admin controls
• Token Expiry + Security Rules – one-time login links with expiration and abuse protection
• SMTP Integration – send emails via your own mail server
• Simple Mail Log – track when and to whom login links were sent
• Email Templates – customize your login email content
• Basic Email Designer – quick styling options directly in the dashboard

**🔧 Temporarily Disabled (v2.4.1):**
• Two-Factor Authentication (2FA) – authentication hooks disabled to resolve login conflicts
• Role-Based 2FA Requirements – feature paused pending authentication hook fixes
• Comprehensive User Management – 2FA user management temporarily unavailable

**🛠 In Progress:**
• Role-Based Token Redirects – redirect users based on their role after login
• Webhook Support – trigger external actions after login (e.g., automation tools)
• Simple CSS Styling – easily adjust button & container styles

**🧠 Planned:**
• BricksBuilder Login Element – full BricksBuilder integration
• Visual Email Designer (Bricks-Based) – design login emails visually with Bricks
• White-Label / Branding Removal – perfect for agencies & white-label solutions
• REST API – access login functionality via secure API endpoints
• KLA Companion App (PWA) – receive login links in an app instead of email
• Login Audit Log – comprehensive tracking of all login attempts with IP addresses, device types, and security insights
• Telegram Support – receive login links via Telegram Bot

**🚀 Major Update v2.4.0:**
* **🔐 Two-Factor Authentication (2FA)** - Complete TOTP-based 2FA system with QR code setup and secure token generation
* **👥 Role-Based 2FA Requirements** - Configure specific user roles to require 2FA authentication
* **🔧 2FA User Management** - Dedicated admin page to search and manage users with 2FA enabled
* **🔒 Enhanced Magic Link Security** - Magic links now properly integrate with 2FA verification flow
* **⚙️ Customizable Login URLs** - Configure custom login page and post-login redirect URLs
* **🎯 Timezone Fix** - Resolved token expiration issues caused by UTC/local timezone mismatches

**🚀 Latest Patch v2.3.1:**
* **🎨 Fixed Admin Interface Consistency** - Resolved header styling issues on Options and Help pages
* **🔧 Enhanced CSS Loading** - Admin styles and JavaScript now properly loaded on all admin pages
* **📐 Logo Display Improvements** - Consistent 40x40px logo sizing across all admin interfaces

**🚀 Major Features v2.3.0:**
* **🔐 WordPress Login Integration** - Added optional magic login field to wp-login.php with toggle control
* **⚙️ Enhanced Options Screen** - New dedicated Options page with feature toggles and controls
* **📖 Comprehensive Help System** - New Help & Instructions page with getting started guide and troubleshooting
* **🛠️ Admin Interface Improvements** - Better organized settings with clear navigation and user guidance

**🔧 Security Patch v2.2.1:**
* **🔒 WordPress.org Plugin Check Compliance** - Fixed all remaining security warnings and database query issues
* **🛡️ Enhanced Database Security** - Added comprehensive phpcs annotations for legitimate direct database operations
* **⚙️ Improved Code Quality** - Fixed timezone-dependent date functions and SQL preparation warnings
* **📝 Better Documentation** - Clear explanations for security exceptions and database operations

**🚀 Major Update in v2.2.0:**
* **🗄️ Custom Database Tables** - Migrated from wp_options to dedicated database tables for scalability
* **📊 Enhanced Login Audit Log** - Comprehensive logging with IP addresses, device types, and user agents
* **⚡ Performance Improvements** - Optimized database queries and reduced wp_posts table bloat
* **🔒 Advanced Token Management** - Secure token storage with attempt tracking and automatic cleanup
* **📧 Enhanced Mail Logging** - Improved email tracking with status monitoring and delivery insights
* **🔄 Backwards Compatibility** - Seamless upgrade path with legacy system fallbacks
* **🛡️ Security Enhancements** - Better audit trails and login attempt monitoring

**🔧 Fixes in v2.1.1:**
* **🏷️ Consistent Branding** - All "Passwordless Authentication" references updated to "Keyless Auth"
* **🔒 Updated Security Nonces** - Changed from passwordless_login_request to keyless_login_request
* **📧 Fixed SMTP Test Emails** - Test emails now properly show "Keyless Auth" branding
* **📁 Correct Installation Path** - Documentation now references correct "keyless-auth" folder
* **📝 Fixed Menu References** - Updated from "PA Settings" to proper "Templates" menu name
* **🔗 Updated Repository URLs** - All GitHub links now point to correct keyless-auth repository
* **🌐 Clean Translation Template** - Regenerated keyless-auth.pot with only current strings
* **🧹 Removed Legacy Strings** - Cleaned up obsolete translation references from original fork

**✨ New Features in v2.1.0:**
* **📧 Optional From Email Field** - Added optional "From Email" field in SMTP settings for flexible sender configuration
* **⚙️ Enhanced SMTP Flexibility** - Support scenarios where SMTP authentication email differs from desired sender email
* **📬 Maintained Deliverability** - Proper Message-ID domain alignment for SPF/DKIM/DMARC compliance preserved
* **🔄 Backwards Compatible** - Empty From Email field defaults to SMTP username, ensuring existing installations work unchanged

**✨ Features from v2.0.12:**
* **🔗 Settings Link Added** - Direct settings link in WordPress plugin list for easier access
* **📧 Fixed Mail Logs View Button** - View Content button now properly displays email content
* **🎯 Improved Admin JavaScript** - Added missing functions for mail logs interaction
* **🔄 SMTP Cache Management** - Added "Clear SMTP Cache" button to resolve configuration issues when settings aren't updating
* **📧 Enhanced Email Deliverability** - Message-ID domain now matches authenticated sender for better SPF/DKIM/DMARC alignment
* **🛠️ Automatic Cache Clearing** - SMTP settings now automatically clear cache when saved to ensure fresh configuration
* **☑️ Bulk Delete Mail Logs** - Select multiple mail logs with checkboxes and delete them in one action
* **✅ Select All Checkbox** - Quickly select/deselect all mail logs for bulk operations

**Features in v2.0.11:**
* **📧 Critical SMTP Fix** - Fixed sender email not being used, emails now properly send from configured SMTP address
* **📝 Fixed Mail Logging** - Resolved post type name length issue preventing mail logs from being saved
* **🔧 Fixed wp-config.php Instructions** - Restored missing JavaScript for credential storage toggle display  
* **🐛 Fixed Fatal Errors** - Resolved multiple undefined function errors in Mail Logger page
* **🔍 Enhanced Diagnostics** - Added diagnostic information to help troubleshoot mail logging issues

**Features in v2.0.10:**
* **🛡️ WordPress.org Plugin Check Compliance** - Resolved all input validation and sanitization warnings
* **🔒 Enhanced Security** - Fixed wp_unslash() issues and removed insecure duplicate form processing
* **⚡ Improved Code Quality** - Eliminated security vulnerabilities in POST data handling
* **🧹 Code Cleanup** - Removed redundant save_settings() method that bypassed security checks

**Features in v2.0.9:**
* **🏷️ WordPress.org Ready** - Complete rebrand to "Keyless Auth" for WordPress.org compliance
* **🔧 Enhanced Prefixes** - All functions/classes use unique "chrmrtns_kla_" prefixes
* **🛡️ Security Hardening** - Improved nonce verification with proper sanitization
* **⚡ Performance Optimized** - Converted inline JS/CSS to proper wp_enqueue system
* **📋 Code Compliance** - Full WordPress.org Plugin Check compliance
* **🎯 Simplified Shortcode** - New [keyless-auth] shortcode

**Features in v2.0.8:**
* **🔒 Security Improvements** - Enhanced output escaping compliance with esc_html_e() and wp_kses()
* **🎨 Template Preview Security** - Email template previews use controlled HTML allowlists
* **🖱️ Button Text Colors** - Fixed button text color controls to prevent blue hover text issues
* **🛡️ WordPress.org Compliance** - Comprehensive escaping improvements for enhanced security

**Features in v2.0.7:**
* **🛡️ WordPress.org Compliance** - Full Plugin Check compliance for WordPress.org submission
* **🔒 Security Hardening** - Enhanced output escaping and input validation
* **⚡ Performance Optimized** - Improved database queries and conditional debug logging
* **📋 Code Quality** - Complete adherence to WordPress coding and security standards
* **🔐 Enhanced Protection** - Advanced CSRF and timing attack mitigation

**Features in v2.0.6:**
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
* **🔗 Login Link Reliability** - Fixed critical issue where login links weren't processing correctly
* **🔌 Enhanced Hook System** - Improved WordPress hook integration for better compatibility
* **🧹 Streamlined Code** - Removed debug logging for production-ready performance

**Features in v2.0.2:**
* **👤 Custom Sender Names** - Force custom "From" names for all emails with toggle control
* **📊 Login Success Tracking** - Dynamic counter showing total successful passwordless logins
* **📧 Enhanced Mail Logging** - Fixed compatibility issues with other SMTP plugins

**Features in v2.0.1:**
* **🏗️ Modular Architecture** - Complete code refactoring with clean, maintainable class structure
* **📨 SMTP Configuration** - Full SMTP support for reliable email delivery with major providers
* **📋 Email Logging & Monitoring** - Track and monitor all emails sent from WordPress
* **🎨 Visual Email Editor** - WYSIWYG editor with HTML support for custom templates
* **🎨 Advanced Color Controls** - Support for hex, RGB, HSL, and HSLA color formats
* **👁️ Template Previews** - Live preview of email templates before selection
* **🔗 Link Color Customization** - Separate color controls for buttons and text links
* **🎛️ Enhanced Menu Structure** - Dedicated top-level admin menu with subpages
* **💼 Professional Email Templates** - Styled German and Simple English templates
* **🔒 Enhanced Security** - Comprehensive nonce verification and input sanitization

**How it works:**

* Instead of asking users for a password when they try to log in to your website, we simply ask them for their username or email
* The plugin creates a temporary authorization token and saves it securely with enhanced validation
* Then we send the user a beautifully styled email with a login button
* The user clicks the button and is automatically logged in
* Tokens expire after 10 minutes and can only be used once for maximum security

You can use the shortcode [keyless-auth] in a page or widget.

NOTE:

Keyless Auth does not replace the default login functionality in WordPress.


== Installation ==

1. Upload the keyless-auth folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Keyless Auth > Templates to configure email templates and colors
1. Create a new page and use the shortcode [keyless-auth]

== Frequently Asked Questions ==

= Is this secure? =

	Yes. The token is created using wp_hash and it's based on the user id, the current time and the salt in wp-config.php

= Couldn't anyone login if they have that link? =

	The token expires after 10 minutes and can only be used once. If people have access to that link it's supposed they have access to your email, in which case it's as safe as the default login, since they could reset their passwords.

= Isn't it more complicated they just entering a password? =

	Weak passwords are used every day by users. There are also people who use the same password across various services and websites. By using the Keyless Auth plugin your users will have one less password to worry about.

= How do I customize the email templates? =

	Go to Keyless Auth > Templates in your WordPress admin. You can choose from predefined templates (German or English) or create your own custom HTML template using the built-in WYSIWYG editor with full HTML and CSS support.

= Can I change the colors in the emails? =

	Yes! In the settings page, you can customize button colors, button hover colors, and link colors. Supports multiple color formats including hex (#007bff), RGB (rgb(0,123,255)), HSL (hsl(211,100%,50%)), and HSLA colors. You can use either color pickers or enter color codes manually.

= Does the custom email editor support HTML? =

	Absolutely! The custom template editor includes a full WYSIWYG editor with HTML support. You can create rich email templates with custom styling, different font sizes, colors, tables, and more. Switch between visual and HTML editing modes as needed.

= How do I configure SMTP for reliable email delivery? =

	Go to Keyless Auth > SMTP in your WordPress admin. You can configure SMTP settings for providers like Gmail, Outlook, Mailgun, SendGrid, and more. The plugin includes automatic port configuration for SSL/TLS, connection testing, and debug tools to ensure your emails are delivered reliably.

= Can I track what emails are being sent? =

	Yes! The Mail Logs feature allows you to monitor all emails sent from your WordPress site. You can view complete email history with timestamps, recipients, subjects, and even preview the full email content. This is perfect for troubleshooting delivery issues and monitoring your site's email activity.

= Can I customize the sender name in emails? =

	Absolutely! In the SMTP settings, you can enable "Force From Name" and set a custom sender name like "Your Website Name" instead of just showing the email address. This makes emails look more professional and branded. The feature includes a toggle control so you can easily enable or disable it.

= Can I store SMTP credentials securely outside the database? =

	Yes! Version 2.0.4 introduces secure credential storage options. You can choose to store SMTP username and password in wp-config.php instead of the database. This is more secure as wp-config.php is typically outside the web root. Simply add these constants to your wp-config.php:
	
	`define('CHRMRTNS_PA_SMTP_USERNAME', 'your-email@example.com');`
	`define('CHRMRTNS_PA_SMTP_PASSWORD', 'your-smtp-password');`
	
	Then select "Store in wp-config.php" in the SMTP settings.

= How do I set up Two-Factor Authentication (2FA)? =

	Go to Keyless Auth > Options in your WordPress admin and enable "Two-Factor Authentication". You can then configure which user roles require 2FA and manage individual users who have 2FA enabled. Users can set up 2FA by visiting their profile page where they'll see a QR code to scan with Google Authenticator, Authy, or any TOTP-compatible app.

= Can I require 2FA for specific user roles only? =

	Yes! In the 2FA settings, you can select which user roles should be required to use 2FA. For example, you might require 2FA for Administrators and Editors while leaving it optional for Subscribers. Users in required roles will be prompted to set up 2FA on their next login.

= How does 2FA work with magic links? =

	When 2FA is enabled, the magic link authentication flow is enhanced for security. If a user has 2FA enabled (or their role requires it), they'll first receive the magic link email, but after clicking the link, they'll be prompted to enter their 2FA code before being logged in. This prevents 2FA bypass vulnerabilities.

= What's different from the original Passwordless Login plugin? =

	This enhanced version includes: modular class-based architecture, complete Two-Factor Authentication system, SMTP configuration, email logging & monitoring, WYSIWYG email editor, visual template previews, advanced color controls (hex/RGB/HSL), separate button and link colors, dedicated admin menu, enhanced security with comprehensive nonce verification, HTML email support, and professional styling options.

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
8. 2FA active status - User dashboard showing active Two-Factor Authentication with backup codes and management options
9. 2FA setup process - QR code generation for easy setup with Google Authenticator, Authy, or similar TOTP apps
10. 2FA admin options - Administrative interface for configuring role-based 2FA requirements and user management
11. Custom link settings - Options page for configuring custom login and post-login redirect URLs


== Changelog ==
= 2.4.1 =
* PATCH: Temporarily disabled 2FA authentication hooks to resolve login conflicts - emergency mode and grace period functionality fully operational
* IMPROVEMENT: Enhanced grace period notices with dynamic colors and emojis based on urgency (red for <3 days, yellow for 4-7 days, blue for 8+ days)
* FIX: Removed all debug code to comply with WordPress.org Plugin Check requirements
* FIX: Fixed timezone function warnings by removing development date() calls
* FIX: Removed .DS_Store hidden files for full WordPress.org compliance
* FIX: Implemented proper singleton pattern to prevent multiple class instantiation
* STABILITY: Clean, production-ready code with all WordPress.org compliance issues resolved
* NOTE: Full 2FA authentication functionality will be restored in v2.4.2 with proper conflict resolution
= 2.4.0 =
* NEW: Complete Two-Factor Authentication (2FA) system with TOTP support using Google Authenticator, Authy, or similar apps
* NEW: QR code generation for easy 2FA setup with automatic secret key generation
* NEW: Role-based 2FA requirements - configure specific user roles to require 2FA authentication
* NEW: Dedicated 2FA user management page with search functionality and detailed user statistics
* NEW: Customizable login and post-login redirect URLs for enhanced user experience
* SECURITY: Enhanced magic link security - proper 2FA integration prevents authentication bypass vulnerability
* CRITICAL: Token expiration timezone issue - fixed UTC/local timezone mismatch causing premature token expiry
* FIX: 2FA users page header rendering - consistent styling across all admin pages
* IMPROVEMENT: Comprehensive session management for 2FA verification flow
* IMPROVEMENT: Frontend and admin context compatibility for 2FA verification forms
* IMPROVEMENT: Clean admin interface with removal of duplicate user management sections

= 2.3.1 =
* FIX: Fixed inconsistent header styling on Options and Help admin pages
* FIX: Admin CSS and JavaScript now properly loaded on all admin pages
* IMPROVEMENT: Consistent 40x40px logo display across all admin interfaces

= 2.3.0 =
* NEW: WordPress Login Integration - Added optional magic login field to wp-login.php with enable/disable toggle
* NEW: Options Settings Page - Dedicated Options page for enabling/disabling wp-login.php integration and other features
* NEW: Help & Instructions Page - Comprehensive help system with getting started guide, security features overview, and troubleshooting
* IMPROVEMENT: Enhanced admin menu structure with clearer navigation between Templates, Options, and Help sections
* IMPROVEMENT: Better user onboarding with step-by-step instructions and common issue solutions
* IMPROVEMENT: Streamlined settings organization for easier plugin configuration and management

= 2.2.1 =
* SECURITY: WordPress.org Plugin Check compliance - Fixed all remaining security warnings and database query issues
* FIX: Database query preparation - Added proper phpcs annotations for legitimate direct database operations
* FIX: Timezone-dependent functions - Changed date() to gmdate() for consistency across timezones
* IMPROVEMENT: Enhanced code documentation - Clear explanations for security exceptions in custom database operations
* IMPROVEMENT: Database operation safety - Comprehensive phpcs disable comments for custom table management

= 2.2.0 =
* MAJOR: Custom database architecture - Migrated from wp_options storage to dedicated database tables for better scalability
* NEW: Login audit log table - Comprehensive tracking of login attempts with IP addresses, device types, user agents, and timestamps
* NEW: Enhanced mail logs table - Advanced email tracking with status monitoring, SMTP responses, and delivery insights
* NEW: Secure token storage table - Dedicated table for login tokens with automatic expiration and cleanup
* NEW: Device tracking table - Foundation for future 2FA and companion app features
* NEW: Webhook logs table - Infrastructure for future webhook support and external integrations
* IMPROVEMENT: Performance optimization - Reduced database overhead by moving high-volume data out of wp_posts and wp_options
* IMPROVEMENT: Enhanced security - Better audit trails with detailed login attempt monitoring and device fingerprinting
* IMPROVEMENT: Backwards compatibility - Automatic detection and fallback to legacy systems for seamless upgrades
* IMPROVEMENT: Database indexing - Optimized queries with proper indexes for better performance at scale
* IMPROVEMENT: Automatic cleanup - Built-in maintenance routines for expired tokens and old log entries
* DEVELOPER: Modular database class - Clean separation of database operations with comprehensive error handling
* DEVELOPER: Migration system - Automatic database version management and upgrade handling

= 2.1.1 =
* FIX: Replaced all "Passwordless Authentication" references with "Keyless Auth" for consistent branding
* FIX: Updated nonce names from passwordless_login_request to keyless_login_request
* FIX: Changed SMTP test email subject/message to use "Keyless Auth" branding
* FIX: Updated installation instructions to reference correct "keyless-auth" folder name
* FIX: Fixed menu references from "PA Settings" to "Templates" in documentation
* FIX: Updated GitHub repository URLs from passwordless-auth to keyless-auth
* IMPROVEMENT: Regenerated translation template (keyless-auth.pot) with current strings only
* IMPROVEMENT: Removed obsolete translation strings from original fork

= 2.1.0 =
* NEW: Optional "From Email" field in SMTP settings - Allows specifying a different sender email address when SMTP username differs from desired sender
* IMPROVEMENT: Enhanced SMTP configuration flexibility - Supports scenarios where SMTP authentication uses one email but sender should appear as another
* IMPROVEMENT: Maintains proper email deliverability with Message-ID domain alignment for SPF/DKIM/DMARC compliance
* IMPROVEMENT: Backwards compatible - If From Email field is empty, uses SMTP username as before

= 2.0.12 =
* FIX: Added plugin action links for quick settings access from WordPress plugin list
* FIX: Mail logs "View Content" button functionality - Added missing JavaScript functions
* IMPROVEMENT: Enhanced admin JavaScript with global scope functions for mail logs interaction
* IMPROVEMENT: Fixed settings link URL to use correct "keyless-auth" slug instead of internal prefix
* NEW: SMTP cache management - Added "Clear SMTP Cache" button to resolve configuration issues
* IMPROVEMENT: Enhanced email deliverability - Message-ID domain now matches authenticated sender for better SPF/DKIM/DMARC alignment
* IMPROVEMENT: Automatic cache clearing - SMTP settings now automatically clear cache when saved
* NEW: Bulk delete mail logs - Added checkbox selection system with "Select All" for bulk operations
* IMPROVEMENT: WordPress-style bulk actions dropdown for familiar mail log management experience

= 2.0.11 =
* FIX: SMTP sender email not being used - Added missing $phpmailer->From to properly authenticate emails
* FIX: Mail logging post type registration - Fixed post type name length issue (shortened chrmrtns_kla_mail_logs to chrmrtns_kla_logs)
* FIX: wp-config.php instructions not displaying - Restored JavaScript and added inline fallback for credential storage toggle
* FIX: Fatal errors on Mail Logger page - Fixed multiple esc_attresc_html_e() typos causing page crashes
* FIX: Plugin initialization timing - Changed from 'init' to 'plugins_loaded' hook for better component loading
* IMPROVEMENT: Added diagnostic information box to Mail Logs page for troubleshooting
= 2.0.10 =
* SECURITY: WordPress.org Plugin Check compliance - Fixed all input validation and sanitization warnings
* SECURITY: Enhanced POST data handling - Added wp_unslash() before all sanitization functions
* SECURITY: Removed duplicate save_settings() method - Eliminated insecure form processing that bypassed nonce verification
* IMPROVEMENT: $_SERVER validation - Added proper isset() checks for superglobal access
* IMPROVEMENT: Code cleanup - Removed redundant form processing methods to prevent security gaps
= 2.0.9 =
* BREAKING: Plugin renamed to "Keyless Auth - Login without Passwords" for WordPress.org compliance  
* BREAKING: Plugin slug changed to "keyless-auth" (old: "passwordless-auth")
* BREAKING: Text domain changed to "keyless-auth" (old: "passwordless-auth")
* IMPROVEMENT: All prefixes updated to "chrmrtns_kla_" for uniqueness and compliance
* IMPROVEMENT: Nonce verification enhanced with proper sanitization (wp_unslash + sanitize_text_field)
* IMPROVEMENT: Converted all inline JavaScript/CSS to proper wp_enqueue system
* IMPROVEMENT: Removed WordPress.org directory assets from plugin ZIP
* IMPROVEMENT: Enhanced WordPress.org Plugin Check compliance
* IMPROVEMENT: Shortcode changed to [keyless-auth] (old: [chrmrtns-passwordless-auth])
* NOTE: This is a complete rebrand required for WordPress.org submission - all functionality remains identical

= 2.0.8 =
* IMPROVEMENT: Enhanced output escaping compliance - All user-facing content now uses proper WordPress escaping functions
* IMPROVEMENT: Template preview security - Email template previews now use wp_kses with controlled HTML allowlists
* IMPROVEMENT: Admin interface escaping - Form outputs and translations properly escaped with esc_html_e() and wp_kses()
* IMPROVEMENT: Email template escaping - All template rendering functions now use proper escaping for security
* IMPROVEMENT: Button text color functionality - Fixed button text color controls to prevent blue hover text issues
* SECURITY: WordPress.org Plugin Check compliance - Comprehensive escaping improvements for enhanced security

= 2.0.7 =
* COMPLIANCE: Full WordPress.org Plugin Check compliance - All security and coding standards met
* FIX: Output escaping - All user-facing content properly escaped for security
* FIX: Input validation - Enhanced nonce verification and superglobal sanitization
* FIX: Database queries - Optimized user meta queries for better performance
* FIX: Debug code - Conditional debug logging only when WP_DEBUG is enabled
* IMPROVEMENT: Code quality - Added comprehensive phpcs ignore comments for legitimate use cases
* IMPROVEMENT: Security hardening - Enhanced protection against timing attacks and CSRF
* IMPROVEMENT: WordPress standards - Full compliance with WordPress coding and security standards

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