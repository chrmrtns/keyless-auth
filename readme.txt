=== Keyless Auth - Login without Passwords ===

Contributors: chrmrtns
Donate link: https://paypal.me/chrmrtns
Tags: secure-login, smtp, 2fa, passwordless, authentication
Requires at least: 3.9
Tested up to: 6.8
Stable tag: 2.6.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Secure, passwordless authentication for WordPress. Your users login via magic email links – no passwords to remember or forget.


== Description ==

Transform your WordPress login experience with passwordless authentication. Users simply enter their email address and receive a secure magic link – click to login instantly. It's more secure than weak passwords and infinitely more user-friendly.

= Why Choose Passwordless Login? =

* **Enhanced Security**: No more weak, reused, or compromised passwords
* **Better User Experience**: One click instead of remembering complex passwords
* **Reduced Support**: Eliminate "forgot password" requests
* **Modern Authentication**: Enterprise-grade security used by Slack, Medium, and others

= Quick Start =

1. Install and activate the plugin
2. Create a new page and add the shortcode `[keyless-auth]`
3. Configure email templates in **Keyless Auth → Templates**
4. Done! Users can now login passwordlessly

= Core Features =

**Ready to Use**
* **Magic Link Authentication** – Secure, one-time login links via email
* **Two-Factor Authentication (2FA)** – Complete TOTP support with Google Authenticator
* **Role-Based 2FA** – Require 2FA for specific user roles (admins, editors, etc.)
* **Custom 2FA Setup URLs** – Direct users to branded frontend 2FA setup pages
* **SMTP Integration** – Reliable email delivery through your mail server
* **Email Templates** – Professional, customizable login emails
* **Mail Logging** – Track all sent emails with delivery status
* **Custom Database Tables** – Scalable architecture with dedicated audit logs

**Advanced Security**
* **Token Security**: 10-minute expiration, single-use tokens
* **Audit Logging**: IP addresses, device types, login attempts
* **Emergency Mode**: Grace period system with admin controls
* **Secure Storage**: SMTP credentials in wp-config.php option

**Customization**
* **WYSIWYG Email Editor**: Full HTML support with live preview
* **Advanced Color Controls**: Hex, RGB, HSL color formats
* **Template System**: German, English, and custom templates
* **Branding Options**: Custom sender names and professional styling

= Installation & Setup =

**Basic Installation**
1. WordPress Admin → Plugins → Add New
2. Search for "Keyless Auth"
3. Install and activate
4. Add [keyless-auth] shortcode to any page

**SMTP Configuration (Recommended)**
1. Navigate to Keyless Auth → SMTP
2. Configure your email provider (Gmail, Outlook, SendGrid, etc.)
3. Test email delivery
4. Save settings

**Two-Factor Authentication Setup**
1. Go to Keyless Auth → Options
2. Enable "Two-Factor Authentication"
3. Select required user roles
4. Users scan QR code with authenticator app

= Email Templates =

**Template Options**
* **German Professional**: Sleek German-language template
* **English Simple**: Clean, minimalist design
* **Custom HTML**: Create your own with WYSIWYG editor

**Customization Features**
* Full HTML and CSS support
* Color picker for buttons and links
* Responsive email design
* Live template preview
* Placeholder system for dynamic content

= Security & Compliance =

**Token Security**
* Generated using WordPress security standards
* Based on user ID, timestamp, and wp-config.php salt
* 10-minute expiration with single-use enforcement
* Secure database storage with automatic cleanup

**Two-Factor Authentication**
* TOTP-based system compatible with Google Authenticator, Authy
* Role-based requirements for granular control
* Grace period system for smooth user transitions
* Custom verification forms with professional styling

**Database Architecture**
* Custom tables for optimal performance
* Comprehensive audit logging
* Device tracking and IP monitoring
* Automatic maintenance and cleanup routines

= SMTP & Email Delivery =

**Supported Providers**
* Gmail / Google Workspace
* Outlook / Microsoft 365
* Mailgun, SendGrid, Amazon SES
* Any SMTP-compatible service

**Advanced Email Features**
* Message-ID domain alignment for deliverability
* SPF/DKIM/DMARC compliance
* Custom sender names and addresses
* Bulk email log management
* Delivery status tracking

**Secure Credential Storage**
Store SMTP credentials securely in wp-config.php:

`define('CHRMRTNS_KLA_SMTP_USERNAME', 'your-email@example.com');`
`define('CHRMRTNS_KLA_SMTP_PASSWORD', 'your-smtp-password');`

= WordPress Integration =

**Login Page Integration**
* Optional magic login field on wp-login.php
* Seamless integration with existing login flow
* Toggle control for easy enable/disable
* Clean, responsive form styling

**Shortcode Usage**
Use `[keyless-auth]` anywhere: pages, posts, widgets, or custom templates.

= Developer Features =

**Hooks & Filters**

Customize login redirect:
`add_filter('wpa_after_login_redirect', 'custom_redirect_function');`

Modify email headers:
`add_filter('wpa_email_headers', 'custom_email_headers');`

Change token expiration:
`add_filter('wpa_change_link_expiration', 'custom_expiration_time');`

**Modular Architecture**
* Clean, organized class structure
* Separated concerns for easy maintenance
* WordPress coding standards compliance
* Extensive documentation and comments

= Requirements =

* **WordPress**: 3.9 or higher (tested up to 6.8)
* **PHP**: 7.4 or higher
* **Email Delivery**: SMTP recommended for reliability

**Note**: Keyless Auth complements WordPress's default login system – it doesn't replace it.

**Developed by Chris Martens | Based on the original Passwordless Login plugin by Cozmoslabs**


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

= Why did you create this plugin? What inspired Keyless Auth? =

	Great question! We built Keyless Auth out of real-world frustration with password complexity and user experience challenges. The story involves too many plugins, security concerns, and the quest for the perfect balance between usability and security. Read the full story behind the plugin's creation: [How Many Plugins Are Too Many? Just One More: Why We Built Keyless Auth](https://chris-martens.com/blog/how-many-plugins-are-too-many-just-one-more-why-we-built-keyless-auth/)

= I can't find a question similar to my issue; Where can I find support? =

	For plugin support, please use the WordPress.org support forum for Keyless Auth. For bug reports and feature requests, you can also visit our GitHub repository. For support with the original Passwordless Login functionality, visit http://www.cozmoslabs.com.


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
= 2.6.3 =
* PERFORMANCE: CSS files now load conditionally only when shortcodes are used (saves ~15KB on pages without login forms)
* PERFORMANCE: 2FA CSS and JS now load conditionally only when [keyless-auth-2fa] shortcode is used (saves additional ~38KB)
* PERFORMANCE: CSS no longer loads on every page globally, only when [keyless-auth], [keyless-auth-full], or [keyless-auth-2fa] shortcodes are rendered
* PERFORMANCE: wp-login.php integration still loads CSS automatically when enabled
* NEW: Dark Mode Behavior setting in Options page - control how forms appear in dark mode
* NEW: Three dark mode options: Auto (default, respects system + theme), Light Only (force light), Dark Only (force dark)
* NEW: Separate CSS files for light-only and dark-only modes (forms-enhanced-light.css, forms-enhanced-dark.css)
* ENHANCEMENT: Better performance for sites with many pages without login forms (total savings: ~53KB per page)
* ENHANCEMENT: Admin can now force light or dark theme regardless of user system preferences
* COMPATIBILITY: Dark mode setting works with all major WordPress themes and block themes

= 2.6.2 =
* FIX: Replaced hardcoded colors in style-front-end.css with CSS variables for proper dark mode support
* FIX: Added max-width (400px) to .chrmrtns-box for consistent message box width matching form width
* NEW: Added shortcode customization parameters to [keyless-auth]: button_text, description, label
* IMPROVEMENT: Alert/success/error boxes now support dark mode and can be customized via CSS variables
* ENHANCEMENT: Shortcode now allows custom button text, field labels, and description text for better branding

= 2.6.1 =
* FIX: Dark mode CSS variable inheritance - fixed --kla-primary-light not defined for dark mode causing light backgrounds in 2FA info boxes
* FIX: Replaced all remaining hardcoded colors in 2fa-frontend.css with CSS variables for proper dark mode support
* FIX: Secondary button hover states now use CSS variables instead of hardcoded light blue colors
* FIX: Copy button styling now uses CSS variables for proper theme adaptation
* FIX: Notice sections (.chrmrtns-2fa-notice) now use CSS variables instead of hardcoded #f0f6fc
* IMPROVEMENT: Added cache busters to CSS file enqueues (forms-enhanced.css .4, 2fa-frontend.css .2) to force browser refresh
* IMPROVEMENT: All CSS variables now properly cascade from :root level for easy theme customization via WPCodeBox or custom CSS
* COMPATIBILITY: CSS variables can now be easily overridden using custom CSS snippets for complete color control

= 2.6.0 =
* NEW: Enhanced CSS system using CSS custom properties for consistent theming across all forms
* NEW: Block theme compatibility - forms now work perfectly with Twenty Twenty-Five and other block themes
* NEW: Professional blue color scheme (#0073aa) aligned with WordPress admin UI standards
* NEW: Dark mode support with automatic color adjustments based on system preferences
* NEW: High contrast mode support for improved accessibility
* NEW: Reduced motion support for users with motion sensitivity
* IMPROVEMENT: Higher CSS specificity without using !important rules for better maintainability
* IMPROVEMENT: Responsive mobile-first design with proper touch targets (16px minimum on mobile)
* IMPROVEMENT: Enhanced accessibility with proper focus states, ARIA support, and keyboard navigation
* IMPROVEMENT: Added wrapper classes (chrmrtns-kla-form-wrapper) for better style isolation
* FIX: Form styling conflicts with block themes completely resolved
* FIX: Input field styling now consistent across all WordPress themes
* FIX: Button hover, active, and focus states properly styled with visual feedback
* FIX: Checkbox styling enhanced with custom SVG checkmark
* COMPATIBILITY: Full responsive design optimized for mobile devices
* ACCESSIBILITY: Comprehensive accessibility improvements including focus indicators and screen reader support

= 2.5.0 =
* NEW: Added redirect parameter support to [keyless-auth] shortcode - now supports custom redirects like [keyless-auth-full]
* NEW: Enhanced shortcode documentation in admin help with comprehensive usage examples and options
* FIX: Fixed critical wp-login.php redirect interference preventing standard password login from working
* FIX: Resolved password login issues in [keyless-auth-full] shortcode caused by form submission conflicts
* FIX: Fixed WordPress coding standards violations - added proper phpcs:ignore comments for nonce verification warnings
* IMPROVEMENT: Enhanced form submission handling to prevent conflicts between magic link and standard WordPress login
* IMPROVEMENT: Updated admin help documentation with detailed shortcode options and usage examples
* IMPROVEMENT: Better hook timing using 'init' instead of 'template_redirect' for improved WordPress compatibility
* IMPROVEMENT: Enhanced wp-login.php redirect logic to preserve POST requests while redirecting GET requests
* SECURITY: Improved form identification system to prevent cross-form processing interference
* COMPATIBILITY: Both [keyless-auth] and [keyless-auth-full] now fully support password and magic link authentication

= 2.4.2 =
* RESTORED: Full 2FA authentication functionality - all hooks and methods reactivated
* NEW: Magic login integration on wp-login.php with clean form positioning in footer
* NEW: Immediate email notifications when 2FA is enabled or roles are configured to require 2FA
* NEW: Resend button in mail logs for troubleshooting email delivery issues
* NEW: Fix Pending Status button to resolve stuck email log statuses
* FIX: Resolved username field jumping issue that was causing 2FA validation errors
* FIX: Fixed SMTP mail logging false positive - now properly tracks pending/sent/failed status
* FIX: Fixed mail logs "Clear All Logs" button not working due to missing nonce verification
* FIX: Fixed magic login redirecting to 2FA when user is still in grace period
* FIX: Restored custom 2FA verification form with better styling (own page, not wp-login.php)
* FIX: Fixed PHP fatal errors - corrected undefined method calls in 2FA verification
* FIX: Optimized 2FA notification emails for better inbox delivery - removed spam trigger words
* FIX: Updated 2FA email template to use login page URL instead of admin panel direct links
* FIX: Removed broken emoji display in email templates that appeared as corrupted characters
* IMPROVEMENT: Clean magic login form styling with proper spacing and responsive design
* IMPROVEMENT: Spam-filter-friendly 2FA email content with softened language and removed trigger words
* IMPROVEMENT: Email notifications now sent immediately when 2FA settings change (system enabled, roles added, user role changed)
* SECURITY: Fixed all WordPress coding standards warnings - proper nonce verification, input sanitization, and translator comments
* SECURITY: Enhanced email template security with better content sanitization
* COMPATIBILITY: Both normal login and magic login work seamlessly without conflicts
* PERFORMANCE: Optimized 2FA verification flow with proper token cleanup and database operations

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
* NEW: wp-config.php constants support - Use CHRMRTNS_KLA_SMTP_USERNAME and CHRMRTNS_KLA_SMTP_PASSWORD constants
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