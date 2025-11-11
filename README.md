# Keyless Auth – Login without Passwords

**Secure, passwordless authentication for WordPress. Your users login via magic email links – no passwords to remember or forget.**

![Version](https://img.shields.io/badge/version-3.3.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.6%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)
[![WordPress.org Plugin](https://img.shields.io/badge/WordPress.org-Plugin-blue.svg)](https://wordpress.org/plugins/keyless-auth/)

## What is Keyless Auth?

Transform your WordPress login experience with passwordless authentication. Users simply enter their email address and receive a secure magic link – click to login instantly. It's more secure than weak passwords and infinitely more user-friendly.

### Why Choose Passwordless Login?

- **Enhanced Security**: No more weak, reused, or compromised passwords
- **Better User Experience**: One click instead of remembering complex passwords
- **Reduced Support**: Eliminate "forgot password" requests
- **Modern Authentication**: Enterprise-grade security used by Slack, Medium, and others

### The Story Behind Keyless Auth

Born from real-world frustration with password complexity and user experience challenges, Keyless Auth represents our commitment to making WordPress authentication both more secure and more user-friendly.

**Read the full story**: [How Many Plugins Are Too Many? Just One More: Why We Built Keyless Auth](https://chris-martens.com/blog/how-many-plugins-are-too-many-just-one-more-why-we-built-keyless-auth/)

## Core Features

### Ready to Use
- **Magic Link Authentication** – Secure, one-time login links via email
- **Two-Factor Authentication (2FA)** – Complete TOTP support with Google Authenticator
- **Role-Based 2FA** – Require 2FA for specific user roles (admins, editors, etc.)
- **Custom 2FA Setup URLs** – Direct users to branded frontend 2FA setup pages
- **SMTP Integration** – Reliable email delivery through your mail server
- **Email Templates** – Professional, customizable login emails
- **Mail Logging** – Track all sent emails with delivery status
- **Custom Database Tables** – Scalable architecture with dedicated audit logs

### Advanced Security
- **Token Security**: 10-minute expiration, single-use tokens
- **Audit Logging**: IP addresses, device types, login attempts
- **Emergency Mode**: Grace period system with admin controls
- **Secure Storage**: SMTP credentials in wp-config.php option

### Customization
- **WYSIWYG Email Editor**: Full HTML support with live preview
- **Advanced Color Controls**: Hex, RGB, HSL color formats
- **Template System**: German, English, and custom templates
- **Branding Options**: Custom sender names and professional styling

### Future Features
- **Enhanced Mail Log Management** – Additional filtering and search capabilities
- **Admin QR Generation** – Backend tools for managing user 2FA setups
- **Role-Based Redirects** – Automatic redirection based on user roles
- **REST API** – Secure API endpoints for external access
- **White-Label Solution** – Agency-ready with branding removal options

## Quick Start

1. Install and activate the plugin
2. Create a new page and add the shortcode `[keyless-auth]`
3. Configure email templates in **Keyless Auth → Templates**
4. Done! Users can now login passwordlessly

### SMTP Configuration (Recommended)
1. Navigate to Keyless Auth → SMTP
2. Configure your email provider (Gmail, Outlook, SendGrid, etc.)
3. Test email delivery
4. Save settings

### Two-Factor Authentication Setup
1. Go to Keyless Auth → Options
2. Enable "Two-Factor Authentication"
3. Select required user roles
4. Users scan QR code with authenticator app

## 🚀 Latest Updates

### v3.3.0 - Core Refactoring & Architecture Improvements (November 11, 2025)

* **📋 REQUIREMENT:** Minimum WordPress version increased from 3.9 to 5.6 for security and maintainability
* **🏗️ NEW:** UrlHelper class - Centralized URL manipulation (getCurrentPageUrl, buildMagicLinkUrl, validateRedirectUrl)
* **🎨 NEW:** MessageFormatter class - Unified message formatting system for success/error messages
* **⚡ NEW:** AssetLoader class - Dedicated CSS/JS asset loading with dark mode support
* **🔒 NEW:** SecurityManager class - Centralized security operations (token validation, user enumeration prevention, 2FA emergency disable)
* **📧 NEW:** EmailService class - Dedicated email operations (magic link generation, template integration)
* **📝 NEW:** LoginFormRenderer class - Dedicated form rendering (simple/full forms, status messages, Profile Builder integration)
* **✅ NEW:** TokenValidator class - Token validation and login processing (2FA integration, grace period handling)
* **🔐 NEW:** WpLoginIntegration class - wp-login.php integration (magic login field, submission handling, redirect logic)
* **⚡ IMPROVEMENT:** Reduced Core.php from 1,247 to 264 lines (79% reduction) by extracting 8 specialized classes
* **🏛️ IMPROVEMENT:** Better code organization following Single Responsibility Principle
* **🧪 IMPROVEMENT:** Enhanced testability with dependency injection pattern for service classes
* **👨‍💻 DEVELOPER:** Eight new classes available for theme/plugin integration
* **🐛 FIX:** Removed deprecated load_plugin_textdomain() call - WordPress.org handles translations automatically
* **✅ FIX:** Added proper phpcs:ignore comments for WordPress Plugin Check compliance
* **🔧 TECHNICAL:** Zero breaking changes - all functionality preserved, just better organized

**What This Means:**
- More maintainable codebase with clear separation of concerns
- Each class has a single, focused responsibility
- Better foundation for future features and enhancements
- Easier testing and debugging with modular architecture
- No impact on existing functionality - seamless upgrade
- WordPress Plugin Check passes with zero errors

### v3.0.5 - Help Page UX Enhancement (October 22, 2025)

* **✨ NEW:** Help page tabs - Added tabbed navigation for better organization of help content
* **🎨 UX:** Organized help content into 7 sections: Getting Started, Shortcodes, Two-Factor Auth, Customization, Security, Troubleshooting, and Advanced
* **🔧 TECHNICAL:** Implemented pure CSS tabs using :checked pseudo-selectors (no JavaScript required)
* **⚡ IMPROVEMENT:** Better content discoverability and navigation in admin help interface

**What This Means:**
- Help page now has organized tabs instead of one long scrolling page
- Easier to find specific help topics with clear section labels
- Pure CSS implementation for reliability and performance
- Better user experience when looking for documentation

### v3.0.4 - Critical Login Fix (October 22, 2025)

* **🐛 FIX:** Critical - Resolved login failure when both wp-login.php options enabled simultaneously
* **⚡ IMPROVEMENT:** Added mutual exclusion logic - redirect option automatically disables wp-login.php magic login
* **⚡ IMPROVEMENT:** Added admin warning notice explaining option conflicts with actionable guidance
* **⚡ IMPROVEMENT:** Enhanced help text clarifying incompatibility between the two wp-login.php options
* **🎨 UX:** Clear visual feedback when conflicting options are enabled
* **🔧 TECHNICAL:** wp-login.php integration hooks only fire when redirect is disabled

**What This Means:**
- Fixed the bug where enabling both "Enable Login on wp-login.php" and "Redirect wp-login.php" prevented all logins
- System now intelligently handles the conflict - redirect takes priority and disables the magic login field
- Clear yellow warning box appears explaining exactly why and how to fix it
- No more locked-out scenarios from conflicting settings

### v3.0.3 - Theme Integration & Customization (October 22, 2025)

* **🎨 NEW:** CSS variable customization hooks - `chrmrtns_kla_custom_css_variables` and `chrmrtns_kla_2fa_custom_css_variables` filters
* **🎨 NEW:** Theme integration system - Map plugin CSS variables to your theme's color system without `!important`
* **📖 NEW:** Comprehensive theme integration documentation in Help page with code examples
* **⚡ IMPROVEMENT:** Enhanced 2FA active page styling - Proper boxed containers matching setup page design
* **⚡ IMPROVEMENT:** Better CSS cascade order using `wp_add_inline_style()` for clean theme customization
* **🐛 FIX:** Email template settings save - Added proper PHPCS ignore comments for nonce verification
* **👨‍💻 DEVELOPER:** Filter hooks allow programmatic CSS variable overrides for automatic theme matching
* **👨‍💻 DEVELOPER:** Supports dark mode integration via filter hooks with theme-specific CSS variable mapping
* **📖 DOCUMENTATION:** Added "Theme Integration (Advanced)" section to Help page with basic and advanced examples

**What This Means:**
- Theme developers can seamlessly integrate Keyless Auth with their theme's color system
- No more `!important` needed for CSS customization - proper cascade order
- Clean PHP filter approach instead of messy CSS overrides
- Automatic dark mode support when mapping to theme variables
- Complete documentation with copy-paste ready examples in the admin Help page

### v3.0.2 - Critical 2FA Fixes (October 22, 2025)

* **🐛 FIX:** Critical - Fixed fatal error in 2FA magic link login flow (Core::get_instance() namespace confusion)
* **🐛 FIX:** Critical - Fixed incorrect get_redirect_url() method call (Admin vs OptionsPage class reference)
* **🐛 FIX:** WooCommerce compatibility - Changed 2FA verification hook from 'init' to 'template_redirect' to prevent cart warnings
* **🐛 FIX:** Database query - Fixed wpdb::prepare() called without placeholder in get_2fa_users() causing PHP notice
* **⚡ IMPROVEMENT:** Better timing for 2FA verification page rendering to ensure compatibility with WooCommerce and other plugins
* **🔧 TECHNICAL:** Added OptionsPage import to Core class for proper redirect URL handling
* **🔧 TECHNICAL:** Updated TwoFA Core class to use template_redirect hook for proper WordPress hook sequence

**What This Means:**
- Magic link login with 2FA now works correctly without fatal errors
- Custom redirect URLs properly function after 2FA verification
- WooCommerce sites no longer see "cart called incorrectly" warnings
- No more PHP notices when viewing 2FA users admin page
- Better plugin compatibility with improved hook timing

### v3.0.1 - WCAG 2.1 AA Accessibility Compliance (October 16, 2025)

* **♿ ACCESSIBILITY:** Full WCAG 2.1 Level AA compliance achieved
* **♿ ACCESSIBILITY:** Added ARIA live regions for error/success messages (role="alert", role="status")
* **♿ ACCESSIBILITY:** Added aria-label to all forms and interactive elements for screen readers
* **♿ ACCESSIBILITY:** Added aria-required="true" to required form inputs
* **♿ ACCESSIBILITY:** Added aria-describedby linking form inputs to helper text
* **♿ ACCESSIBILITY:** Added screen reader only text (.sr-only) utility class to all CSS files
* **♿ ACCESSIBILITY:** QR code containers now have proper role="img" and descriptive labels
* **♿ ACCESSIBILITY:** 2FA verification inputs include screen reader hints for code format
* **♿ ACCESSIBILITY:** Copy buttons now have contextual aria-label for better screen reader support
* **♿ ACCESSIBILITY:** All status indicators use aria-live for dynamic content announcements
* **⚡ IMPROVEMENT:** Enhanced keyboard navigation with visible focus states throughout
* **⚡ IMPROVEMENT:** Error messages now properly announced to assistive technology
* **⚡ IMPROVEMENT:** Loading states communicated to screen readers with appropriate ARIA
* **✅ COMPATIBILITY:** Admin page logos already compliant with proper alt attributes

**What This Means:**
- Plugin is now fully accessible to users with disabilities
- Screen readers properly announce all status changes and errors
- Keyboard navigation has clear context and focus indicators
- No visual changes - purely enhanced accessibility
- Complies with accessibility standards for government and enterprise use

### v3.0.0 - PSR-4 Refactoring & Critical Fixes (October 16, 2025)

* **🏗️ ARCHITECTURAL:** Complete refactoring to PSR-4 autoloading with namespaces
* **⚡ IMPROVEMENT:** Modern PHP class organization - `Chrmrtns\KeylessAuth` namespace
* **💡 IMPROVEMENT:** Better IDE support and code intelligence
* **📁 IMPROVEMENT:** Cleaner code structure organized by functionality
* **🔧 TECHNICAL:** Autoloader replaces manual class loading
* **📂 TECHNICAL:** Classes organized: Core/, Admin/, Email/, Security/TwoFA/
* **⚠️ BREAKING:** Internal class names changed (no impact on users, data preserved)
* **🔄 MAINTENANCE:** All database tables, options, and user data remain unchanged
* **✅ MAINTENANCE:** Seamless upgrade - no manual steps required
* **🐛 FIX:** Mail logging status tracking - Fixed critical bug where failed emails showed as "Sent"
* **🐛 FIX:** DNS validation for invalid email domains - Emails with non-existent domains now marked as "Failed"
* **🐛 FIX:** Database.php log_email() now returns actual insert_id instead of row count

**What This Means:**
- Modern, maintainable codebase following PSR-4 standards
- Better code organization for future development
- Fixed critical mail logging bug where SMTP failures weren't properly tracked
- Invalid email domains (typos like `.commm`) now properly detected and logged as failed
- All your data, settings, and functionality remain exactly the same

### v2.7.1 - Bug Fixes (October 13, 2025)

* **🐛 FIX:** User Enumeration Prevention now properly blocks `?author=N` queries before WordPress canonical redirect
* **🐛 FIX:** Misleading "2FA system is now active" message no longer appears when saving unrelated settings (XML-RPC, User Enumeration)
* **⚡ ENHANCEMENT:** Author queries now blocked earlier using `parse_request` hook (more reliable than `template_redirect`)
* **⚡ ENHANCEMENT:** Emergency mode message only displays when emergency mode checkbox is actually toggled
* **🔧 TECHNICAL:** Added `block_author_query_early()` function to intercept author queries before `redirect_canonical()` runs
* **🔧 TECHNICAL:** Fixed boolean type comparison in emergency mode setting change detection

**Why This Matters:**
- Sites with User Enumeration Prevention enabled now properly block `?author=1` queries
- No more confusing "2FA active" messages when enabling security features
- More reliable protection against username enumeration attacks

### v2.7.0 - Security Hardening (October 8, 2025)

* **🔒 NEW:** XML-RPC disable option for enhanced security - prevent brute force attacks via XML-RPC
* **🔒 NEW:** Application Passwords disable option - block REST API and XML-RPC authentication when not needed
* **🔒 NEW:** User Enumeration Prevention - comprehensive protection against username discovery attacks
* **🛡️ NEW:** Security Settings section in Options page with three hardening options
* **🛡️ SECURITY:** Block REST API user endpoints, author archives, login errors, and comment author classes
* **🛡️ SECURITY:** Option to disable WordPress XML-RPC interface to reduce attack surface
* **🛡️ SECURITY:** Option to disable Application Passwords for sites not requiring programmatic access
* **✨ ENHANCEMENT:** Admin can now easily harden WordPress without code or .htaccess modifications
* **📚 ENHANCEMENT:** Comprehensive Help page documentation for all security features
* **🔧 COMPATIBILITY:** All security options are optional and respect existing integrations
* **🔧 COMPATIBILITY:** Recovery via FTP deactivation if needed

**Security Control:** New "Security Settings" section in Options page lets you:
- **Disable XML-RPC** - Prevent brute force attacks and reduce attack surface
- **Disable Application Passwords** - Block programmatic authentication when not needed
- **Prevent User Enumeration** - Block REST API user endpoints, author archives, login errors, comment author classes, and oEmbed data

**User Enumeration Protection blocks:**
- REST API user endpoints (`/wp-json/wp/v2/users`)
- Author archives and `?author=N` queries
- Login error messages that reveal username validity
- Comment author CSS classes
- Author data in oEmbed responses

**Use Cases:**
- Sites not using Jetpack, mobile apps, or pingbacks can safely disable XML-RPC
- Sites without API integrations can disable Application Passwords
- Business/corporate sites without author profiles should enable User Enumeration Prevention
- All options include comprehensive help documentation

### v2.6.0 - Enhanced Form Styling for Block Themes (December 2024)
**Complete form styling overhaul for compatibility with block themes like Twenty Twenty-Five**

* **🎨 NEW:** Enhanced CSS system using CSS custom properties for consistent theming
* **🎨 NEW:** Block theme compatibility - forms now work perfectly with Twenty Twenty-Five and other block themes
* **🎨 NEW:** Professional blue color scheme (#0073aa) aligned with WordPress admin UI
* **🎨 NEW:** Dark mode support with automatic color adjustments
* **✨ IMPROVEMENT:** Higher CSS specificity without using !important rules
* **✨ IMPROVEMENT:** Responsive mobile-first design with proper touch targets
* **✨ IMPROVEMENT:** Accessibility improvements with proper focus states and ARIA support
* **✨ IMPROVEMENT:** Added wrapper classes for better style isolation
* **🔧 FIX:** Form styling conflicts with block themes resolved
* **🔧 FIX:** Input field styling now consistent across all themes
* **🔧 FIX:** Button hover and active states properly styled
* **📱 COMPATIBILITY:** Full responsive design for mobile devices
* **♿ ACCESSIBILITY:** High contrast mode support and reduced motion preferences

### v2.5.0 - Enhanced Shortcode & Login Fix (September 29, 2024)
**Enhanced shortcode functionality with redirect support and critical password login fixes**

* **🚀 NEW:** [keyless-auth] shortcode now supports redirect parameter like [keyless-auth-full]
* **📖 NEW:** Enhanced admin help documentation with comprehensive shortcode usage examples
* **🔧 FIX:** Fixed critical wp-login.php redirect preventing standard password login
* **🔧 FIX:** Resolved [keyless-auth-full] password login conflicts with magic link processing
* **🔒 SECURITY:** Fixed WordPress coding standards violations with proper phpcs annotations
* **⚡ IMPROVEMENT:** Better form handling prevents conflicts between authentication methods
* **📚 IMPROVEMENT:** Updated help system with detailed options and examples
* **🔄 COMPATIBILITY:** Both shortcodes now fully support password and magic link authentication

### v2.4.2 - Full Restoration Patch (September 25, 2024)
**Complete restoration of 2FA functionality with enhanced magic login integration and email improvements**

* **✅ RESTORED:** Full 2FA authentication functionality - all hooks and methods reactivated
* **🔧 NEW:** Magic login integration on wp-login.php with clean form positioning in footer
* **🔧 NEW:** Immediate email notifications when 2FA is enabled or roles are configured to require 2FA
* **🔧 NEW:** Resend button in mail logs for troubleshooting email delivery issues
* **🔧 NEW:** Fix Pending Status button to resolve stuck email log statuses
* **✅ FIX:** Resolved username field jumping issue that was causing 2FA validation errors
* **✅ FIX:** Fixed SMTP mail logging false positive - now properly tracks pending/sent/failed status
* **✅ FIX:** Fixed mail logs "Clear All Logs" button not working due to missing nonce verification
* **✅ FIX:** Fixed magic login redirecting to 2FA when user is still in grace period
* **✅ FIX:** Restored custom 2FA verification form with better styling (own page, not wp-login.php)
* **✅ FIX:** Fixed PHP fatal errors - corrected undefined method calls in 2FA verification
* **✅ FIX:** Optimized 2FA notification emails for better inbox delivery - removed spam trigger words
* **✅ FIX:** Updated 2FA email template to use login page URL instead of admin panel direct links
* **✅ FIX:** Removed broken emoji display in email templates that appeared as corrupted characters
* **🎨 IMPROVEMENT:** Clean magic login form styling with proper spacing and responsive design
* **🎨 IMPROVEMENT:** Spam-filter-friendly 2FA email content with softened language and removed trigger words
* **🎨 IMPROVEMENT:** Email notifications now sent immediately when 2FA settings change (system enabled, roles added, user role changed)
* **🛡️ SECURITY:** Fixed all WordPress coding standards warnings - proper nonce verification, input sanitization, and translator comments
* **🛡️ SECURITY:** Enhanced email template security with better content sanitization
* **📋 COMPATIBILITY:** Both normal login and magic login work seamlessly without conflicts
* **🚀 PERFORMANCE:** Optimized 2FA verification flow with proper token cleanup and database operations

### v2.4.1 - Stability Patch (September 25, 2024)
**Clean, production-ready patch focusing on stability and compliance**

* **🔧 PATCH:** Temporarily disabled 2FA authentication hooks to resolve login conflicts - emergency mode and grace period functionality fully operational
* **🎨 IMPROVEMENT:** Enhanced grace period notices with dynamic colors and emojis based on urgency (red for <3 days, yellow for 4-7 days, blue for 8+ days)
* **✅ FIX:** Removed all debug code to comply with WordPress.org Plugin Check requirements
* **✅ FIX:** Fixed timezone function warnings by removing development date() calls
* **✅ FIX:** Removed .DS_Store hidden files for full WordPress.org compliance
* **✅ FIX:** Implemented proper singleton pattern to prevent multiple class instantiation
* **🛡️ STABILITY:** Clean, production-ready code with all WordPress.org compliance issues resolved

### v2.6.3 - Performance & Dark Mode Control (October 6, 2025)

* **⚡ PERFORMANCE:** CSS files now load conditionally only when shortcodes are used (saves ~15KB on pages without login forms)
* **⚡ PERFORMANCE:** 2FA CSS and JS now load conditionally only when `[keyless-auth-2fa]` shortcode is used (saves additional ~38KB)
* **⚡ PERFORMANCE:** CSS/JS no longer loads on every page globally, only when shortcodes are rendered
* **⚡ PERFORMANCE:** wp-login.php integration still loads CSS automatically when enabled in Options
* **🌙 NEW:** Dark Mode Behavior setting in Options page - control how forms appear in dark mode
* **🎨 NEW:** Three dark mode options: Auto (default, respects system + theme), Light Only (force light), Dark Only (force dark)
* **📁 NEW:** Separate CSS files for light-only and dark-only modes (forms-enhanced-light.css, forms-enhanced-dark.css)
* **🚀 ENHANCEMENT:** Better performance for sites with many pages without login forms (total savings: ~53KB per page)
* **🎛️ ENHANCEMENT:** Admin can now force light or dark theme regardless of user system preferences
* **🔧 COMPATIBILITY:** Dark mode setting works with all major WordPress themes and block themes

**Admin Control:** New "Dark Mode Behavior" dropdown in Options page lets you choose:
- **Auto** (default) - Respects system preference and theme dark mode classes
- **Light Only** - Forces light theme, no dark mode
- **Dark Only** - Forces dark theme always

**Performance Impact:** Pages without any login/2FA shortcodes now load ~53KB less (15KB login CSS + 38KB 2FA CSS/JS)

### v2.6.2 - CSS Fixes & Shortcode Enhancements (October 3, 2025)

* **🎨 FIX:** Replaced hardcoded colors in style-front-end.css with CSS variables for proper dark mode support
* **📏 FIX:** Added max-width (400px) to `.chrmrtns-box` for consistent message box width
* **✨ NEW:** Added shortcode customization parameters: `button_text`, `description`, `label`
* **🌙 IMPROVEMENT:** Alert/success/error boxes now fully support dark mode
* **🔧 ENHANCEMENT:** Better branding control with customizable shortcode text

**Example**: `[keyless-auth button_text="Email login link" description="Secure passwordless access" label="Your Email"]`

### v2.6.1 - Dark Mode CSS Fixes (October 3, 2025)

* **🎨 FIX:** Dark mode CSS variable inheritance - fixed `--kla-primary-light` not defined for dark mode causing light backgrounds in 2FA info boxes
* **🌙 FIX:** Replaced all remaining hardcoded colors in 2fa-frontend.css with CSS variables for proper dark mode support
* **🔘 FIX:** Secondary button hover states now use CSS variables instead of hardcoded light blue colors
* **📋 FIX:** Copy button styling now uses CSS variables for proper theme adaptation
* **📢 FIX:** Notice sections (.chrmrtns-2fa-notice) now use CSS variables instead of hardcoded #f0f6fc
* **⚡ IMPROVEMENT:** Added cache busters to CSS file enqueues (forms-enhanced.css .4, 2fa-frontend.css .2) to force browser refresh
* **🎨 IMPROVEMENT:** All CSS variables now properly cascade from :root level for easy theme customization via WPCodeBox or custom CSS
* **🔧 COMPATIBILITY:** CSS variables can now be easily overridden using custom CSS snippets for complete color control

### v2.6.0 - Block Theme Compatibility & Dark Mode (October 2024)

* **🎨 NEW:** Enhanced CSS system using CSS custom properties for consistent theming across all forms
* **🧱 NEW:** Block theme compatibility - forms now work perfectly with Twenty Twenty-Five and other block themes
* **🔵 NEW:** Professional blue color scheme (#0073aa) aligned with WordPress admin UI standards
* **🌙 NEW:** Dark mode support with automatic color adjustments based on system preferences
* **♿ NEW:** High contrast mode support for improved accessibility
* **🎯 NEW:** Reduced motion support for users with motion sensitivity
* **📱 IMPROVEMENT:** Responsive mobile-first design with proper touch targets (16px minimum on mobile)
* **🔒 IMPROVEMENT:** Enhanced accessibility with proper focus states, ARIA support, and keyboard navigation
* **✅ FIX:** Form styling conflicts with block themes completely resolved
* **✅ FIX:** Input field styling now consistent across all WordPress themes
* **✅ FIX:** Button hover, active, and focus states properly styled with visual feedback

### v2.4.0 - Complete 2FA System (September 25, 2024)
**Successfully released September 25, 2024 with complete SVN deployment**

* **🔐 Two-Factor Authentication (2FA)** - Complete TOTP-based 2FA system with QR code setup and secure token generation
* **👥 Role-Based 2FA Requirements** - Configure specific user roles to require 2FA authentication
* **🔧 2FA User Management** - Dedicated admin page to search and manage users with 2FA enabled
* **🔒 Enhanced Magic Link Security** - Magic links now properly integrate with 2FA verification flow
* **⚙️ Customizable Login URLs** - Configure custom login page and post-login redirect URLs
* **🚨 Critical Timezone Fix** - Resolved token expiration issues caused by UTC/local timezone mismatches
* **📸 New Screenshots** - Added 4 new screenshots (8-11) showcasing complete 2FA functionality
* **🏗️ Asset Reorganization** - Moved all assets to organized structure (CSS, JS, screenshots)

**GitHub Release:** [v2.4.0](https://github.com/chrmrtns/keyless-auth/releases/tag/v2.4.0)
**WordPress.org Status:** ✅ Live and available for download
**SVN Revisions:** 3367782 (trunk), 3367789 (tag), 3367796 (screenshots)

## 🔧 Latest Patch v2.3.1

* **🎨 Fixed Admin Interface Consistency** - Resolved header styling issues on Options and Help pages
* **🔧 Enhanced CSS Loading** - Admin styles and JavaScript now properly loaded on all admin pages
* **📐 Logo Display Improvements** - Consistent 40x40px logo sizing across all admin interfaces

## 🚀 Major Features v2.3.0

* **🔐 WordPress Login Integration** - Added optional magic login field to wp-login.php with toggle control
* **⚙️ Enhanced Options Screen** - New dedicated Options page with feature toggles and controls
* **📖 Comprehensive Help System** - New Help & Instructions page with getting started guide and troubleshooting
* **🛠️ Admin Interface Improvements** - Better organized settings with clear navigation and user guidance

## 🔧 Security Patch v2.2.1

* **🔒 WordPress.org Plugin Check Compliance** - Fixed all remaining security warnings and database query issues
* **🛡️ Enhanced Database Security** - Added comprehensive phpcs annotations for legitimate direct database operations
* **⚙️ Improved Code Quality** - Fixed timezone-dependent date functions and SQL preparation warnings
* **📝 Better Documentation** - Clear explanations for security exceptions and database operations

## 🚀 Major Update in v2.2.0

* **🗄️ Custom Database Tables** - Migrated from wp_options to dedicated database tables for scalability and performance
* **📊 Enhanced Login Audit Log** - Comprehensive tracking with IP addresses, device types, user agents, and timestamps
* **⚡ Performance Improvements** - Optimized database queries and reduced wp_posts table bloat
* **🔐 Advanced Token Management** - Secure token storage with attempt tracking and automatic cleanup
* **📧 Enhanced Mail Logging** - Improved email tracking with status monitoring and delivery insights
* **🔄 Backwards Compatibility** - Seamless upgrade path with legacy system fallbacks
* **🛡️ Security Enhancements** - Better audit trails and login attempt monitoring
* **🔧 Database Infrastructure** - Foundation for future features like 2FA, companion app, and webhooks

## 🔧 Fixes in v2.1.1

* **🏷️ Consistent Branding** - All "Passwordless Authentication" references updated to "Keyless Auth"
* **🔒 Updated Security Nonces** - Changed from passwordless_login_request to keyless_login_request
* **📧 Fixed SMTP Test Emails** - Test emails now properly show "Keyless Auth" branding
* **📁 Correct Installation Path** - Documentation now references correct "keyless-auth" folder
* **📝 Fixed Menu References** - Updated from "PA Settings" to proper "Templates" menu name
* **🔗 Updated Repository URLs** - All GitHub links now point to correct keyless-auth repository
* **🌐 Clean Translation Template** - Regenerated keyless-auth.pot with only current strings
* **🧹 Removed Legacy Strings** - Cleaned up obsolete translation references from original fork

## ✨ New Features in v2.1.0

* **📧 Optional From Email Field** - Added optional "From Email" field in SMTP settings for flexible sender configuration
* **⚙️ Enhanced SMTP Flexibility** - Support scenarios where SMTP authentication email differs from desired sender email
* **📬 Maintained Deliverability** - Proper Message-ID domain alignment for SPF/DKIM/DMARC compliance preserved
* **🔄 Backwards Compatible** - Empty From Email field defaults to SMTP username, ensuring existing installations work unchanged

## ✨ Features from v2.0.12

* **🔗 Settings Link Added** - Direct settings link in WordPress plugin list for easier access
* **📧 Fixed Mail Logs View Button** - View Content button now properly displays email content
* **🎯 Improved Admin JavaScript** - Added missing functions for mail logs interaction
* **🔄 SMTP Cache Management** - Added "Clear SMTP Cache" button to resolve configuration issues when settings aren't updating
* **📧 Enhanced Email Deliverability** - Message-ID domain now matches authenticated sender for better SPF/DKIM/DMARC alignment
* **🛠️ Automatic Cache Clearing** - SMTP settings now automatically clear cache when saved to ensure fresh configuration
* **☑️ Bulk Delete Mail Logs** - Select multiple mail logs with checkboxes and delete them in one action
* **✅ Select All Checkbox** - Quickly select/deselect all mail logs for bulk operations

## 🔐 Features in v2.0.11

* **📧 Critical SMTP Fix** - Fixed sender email not being used, emails now properly send from configured SMTP address
* **📝 Fixed Mail Logging** - Resolved post type name length issue preventing mail logs from being saved
* **🔧 Fixed wp-config.php Instructions** - Restored missing JavaScript for credential storage toggle display  
* **🐛 Fixed Fatal Errors** - Resolved multiple undefined function errors in Mail Logger page
* **🔍 Enhanced Diagnostics** - Added diagnostic information to help troubleshoot mail logging issues

## 🏷️ Features in v2.0.10

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

### From WordPress.org (Recommended)
1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "**Keyless Auth**"
3. Click **Install Now** and then **Activate**
4. Go to **Keyless Auth > Templates** to configure templates and colors

### Manual Installation
1. Download from [WordPress.org](https://wordpress.org/plugins/keyless-auth/) or [GitHub](https://github.com/chrmrtns/keyless-auth)
2. Upload the `keyless-auth` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Keyless Auth > Templates** to configure templates and colors

### Getting Started
Create a page and use the shortcode `[keyless-auth]` to add the passwordless login form.

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

### Two-Factor Authentication (2FA)
- TOTP-based authentication using Google Authenticator, Authy, or similar apps
- QR code setup for easy configuration
- Role-based 2FA requirements (enforce for specific user roles)
- Comprehensive user management with search functionality
- Automatic integration with magic link authentication flow

### REST API (Beta - v3.3.0+)
Keyless Auth now provides REST API endpoints for modern integrations. The API runs in parallel with existing AJAX handlers for backward compatibility.

#### Enabling REST API
1. Go to **Keyless Auth → Options**
2. Enable "**Enable REST API (Beta)**"
3. Save settings

#### Authentication Endpoint

**Request Magic Link**
```
POST /wp-json/keyless-auth/v1/request-login
```

**Headers:**
```
Content-Type: application/json
X-WP-Nonce: {wp_rest_nonce}
```

**Request Body:**
```json
{
  "email_or_username": "user@example.com",
  "redirect_url": "https://example.com/dashboard" (optional)
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Magic login link sent! Check your email and click the link to login.",
  "data": {
    "user_id": 123,
    "email": "user@example.com"
  }
}
```

**Error Responses:**
```json
// 404 - User not found
{
  "code": "invalid_user",
  "message": "The username or email you provided does not exist.",
  "data": { "status": 404 }
}

// 403 - Admin approval required
{
  "code": "admin_approval_required",
  "message": "Your account is pending admin approval.",
  "data": { "status": 403 }
}

// 500 - Email sending failed
{
  "code": "email_failed",
  "message": "There was a problem sending your email.",
  "data": { "status": 500 }
}

// 503 - Emergency disabled
{
  "code": "emergency_disabled",
  "message": "Magic link login is temporarily disabled.",
  "data": { "status": 503 }
}
```

#### JavaScript Integration

**Using the API Abstraction Layer:**
```javascript
// The API layer automatically detects REST/AJAX based on settings
const api = new KeylessAuthAPI(chrmrtnsKlaApiConfig);

try {
  const response = await api.requestLoginLink(
    'user@example.com',
    'https://example.com/dashboard'
  );

  if (response.success) {
    console.log('Magic link sent!', response.message);
  } else {
    console.error('Error:', response.message);
  }
} catch (error) {
  console.error('Network error:', error);
}
```

**Direct REST API Call:**
```javascript
fetch('/wp-json/keyless-auth/v1/request-login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
  },
  credentials: 'same-origin',
  body: JSON.stringify({
    email_or_username: 'user@example.com',
    redirect_url: 'https://example.com/dashboard'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Success:', data.message);
  } else {
    console.error('Error:', data.message);
  }
});
```

#### PHP Integration

**Using WordPress HTTP API:**
```php
$response = wp_remote_post(rest_url('keyless-auth/v1/request-login'), array(
    'headers' => array(
        'Content-Type' => 'application/json',
        'X-WP-Nonce' => wp_create_nonce('wp_rest')
    ),
    'body' => wp_json_encode(array(
        'email_or_username' => 'user@example.com',
        'redirect_url' => 'https://example.com/dashboard'
    ))
));

if (!is_wp_error($response)) {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($body['success']) {
        echo 'Magic link sent!';
    }
}
```

#### Feature Flag

Control REST API availability programmatically:
```php
// Enable REST API via filter
add_filter('chrmrtns_kla_rest_api_enabled', '__return_true');

// Or check if enabled
$is_enabled = get_option('chrmrtns_kla_enable_rest_api', '0') === '1';
```

#### Future Pro Features (Planned)
- Bulk user operations
- Administrative actions via API
- Webhook integrations
- API rate limiting controls
- API analytics and logging

## 🔒 Security Features

- **Secure token generation** using `wp_hash()` with user ID, timestamp, and salt
- **Timing attack protection** with `hash_equals()`
- **Token expiration** - 10 minutes maximum
- **One-time use** tokens automatically deleted after use
- **Two-Factor Authentication** - TOTP-based 2FA with role-based enforcement
- **Enhanced magic link security** - 2FA integration prevents authentication bypass
- **Enhanced input sanitization** for all form fields
- **Comprehensive nonce verification** for all admin actions
- **UTC timezone consistency** - Prevents token expiration issues across different server timezones

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
Absolutely! Go to **Keyless Auth > Templates** to choose from predefined templates or create custom HTML templates using the WYSIWYG editor.

### How do I configure SMTP?
Go to **Keyless Auth > SMTP** to configure settings for providers like Gmail, Outlook, Mailgun, SendGrid, and more. Includes automatic port configuration and connection testing.

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

### v3.3.0
- **REQUIREMENT:** Minimum WordPress version increased from 3.9 to 5.6
- **COMPATIBILITY:** WordPress 5.6+ (December 2020) now required for security and maintainability
- **NOTE:** No breaking changes for users already on WordPress 5.6 or higher
- **RATIONALE:** WordPress 3.9 (April 2014) has critical unpatched security vulnerabilities
- **REFACTOR:** Core.php modular refactoring - Extracted utility, service, and presentation classes for better code organization
- **NEW:** UrlHelper class - Centralized URL manipulation and generation (getCurrentPageUrl, buildMagicLinkUrl, validateRedirectUrl, etc.)
- **NEW:** MessageFormatter class - Unified message formatting system (success, error, login messages with consistent HTML structure)
- **NEW:** AssetLoader class - Dedicated CSS/JS asset loading with dark mode support and custom CSS filter integration
- **NEW:** SecurityManager class - Centralized security operations (token generation/validation, user enumeration prevention, 2FA emergency disable)
- **NEW:** EmailService class - Dedicated email operations (magic link generation, template integration, token storage)
- **NEW:** LoginFormRenderer class - Dedicated form rendering (simple and full login forms, status messages, Profile Builder integration)
- **NEW:** TokenValidator class - Dedicated token validation and login processing (2FA integration, grace period handling, session management)
- **NEW:** WpLoginIntegration class - Dedicated wp-login.php integration (magic login field, submission handling, redirect logic, failed login handling)
- **IMPROVEMENT:** Reduced Core.php from 1,247 to 264 lines by extracting 3 utility classes, 2 service classes, and 3 specialized classes (79% reduction)
- **IMPROVEMENT:** Better code organization with Single Responsibility Principle - each class has one clear purpose
- **IMPROVEMENT:** Enhanced maintainability - related functions grouped into focused, reusable classes
- **IMPROVEMENT:** Improved testability - dependency injection pattern for service classes enables easy unit testing
- **SECURITY:** User enumeration prevention centralized in SecurityManager with 6 protection methods
- **SECURITY:** Token validation now centralized with logging support via Database integration
- **SECURITY:** 2FA integration cleanly separated in TokenValidator with grace period and session management
- **TECHNICAL:** Utility classes (UrlHelper, MessageFormatter, AssetLoader) use PSR-4 namespacing and static methods
- **TECHNICAL:** Service classes (SecurityManager, EmailService, TokenValidator) use dependency injection pattern
- **TECHNICAL:** Presentation layer (LoginFormRenderer) separated from business logic
- **TECHNICAL:** Zero breaking changes - all functionality preserved, just better organized
- **TECHNICAL:** Foundation for future enhancements - modular architecture ready for REST API migration
- **DEVELOPER:** Eight new classes available for theme/plugin integration: UrlHelper, MessageFormatter, AssetLoader, SecurityManager, EmailService, LoginFormRenderer, TokenValidator, WpLoginIntegration
- **FIX:** Removed deprecated load_plugin_textdomain() call - WordPress.org handles translations automatically since WP 4.6
- **FIX:** Added proper phpcs:ignore comments for GET parameter access in MessageFormatter for WordPress Plugin Check compliance

### v3.2.2
- **FIX:** Login error display on custom login pages - Wrong password/username errors now display properly instead of blank error
- **FIX:** wp_login_failed hook integration - Failed login attempts now redirect to custom login page with error parameters
- **IMPROVEMENT:** Error messages preserved during wp-login.php to custom page redirect flow
- **IMPROVEMENT:** Better error handling for standard WordPress password forms on custom login pages
- **TECHNICAL:** Added `handle_failed_login()` method to catch authentication failures and redirect with error codes
- **TECHNICAL:** Error parameters (login_error, login) now properly preserved and displayed via shortcodes
- **COMPATIBILITY:** Works harmoniously with User Enumeration Prevention feature - no conflicts

### v3.2.1
- **NEW:** Support URL setting in Options page - Configure optional support footer on password reset page
- **FIX:** Registered missing chrmrtns_kla_support_url option that was referenced but not functional
- **IMPROVEMENT:** Password reset page can now display custom support link when configured

### v3.2.0
- **NEW:** Custom Password Reset Page - Replace wp-login.php with branded shortcode-based reset page
- **NEW:** Password reset shortcode `[keyless-auth-password-reset]` - Embed on any page with any slug
- **NEW:** Custom password reset URL setting - Specify your own password reset page URL
- **NEW:** Two-step password reset flow - Email request form and password reset form with token validation
- **NEW:** Beautiful styled reset forms - Matching Keyless Auth gradient branding
- **IMPROVEMENT:** Flexible page URL - No hardcoded /reset-password route, users choose their own slug
- **IMPROVEMENT:** Smart "Forgot password?" link - Auto-switches between custom page and wp-login.php
- **IMPROVEMENT:** Optional support footer - Only displays if support URL is configured
- **IMPROVEMENT:** Properly scoped CSS - All styles prefixed to avoid theme conflicts
- **TECHNICAL:** New PasswordReset class at `includes/Core/PasswordReset.php`
- **TECHNICAL:** Full translation support with `_e()` and `esc_html_e()` functions
- **TECHNICAL:** Token validation using WordPress `check_password_reset_key()` function
- **TECHNICAL:** Secure nonce validation for both email request and password reset forms

### v3.1.0
- **NEW:** WooCommerce Integration - Magic link authentication on WooCommerce login forms
- **NEW:** Collapsible UI design - "Or login with magic link instead" toggle link
- **NEW:** WooCommerce setting toggle - Enable/disable integration from Options page
- **NEW:** Smart checkout redirect - Users return to checkout after login
- **FIX:** Custom email template not saving - Fixed field name mismatch
- **FIX:** Template sanitization now preserves inline styles and <style> tags properly
- **IMPROVEMENT:** Real-time color preview updates - All template previews update instantly
- **IMPROVEMENT:** WordPress standard notice classes - Better admin UI consistency
- **TECHNICAL:** PSR-4 namespaced class at `includes/Core/WooCommerce.php`
- **TECHNICAL:** Vanilla JavaScript implementation - No jQuery dependency

### v2.6.0
- **NEW:** Enhanced CSS system using CSS custom properties for consistent theming across all forms
- **NEW:** Block theme compatibility - forms now work perfectly with Twenty Twenty-Five and other block themes
- **NEW:** Professional blue color scheme (#0073aa) aligned with WordPress admin UI standards
- **NEW:** Dark mode support with automatic color adjustments based on system preferences
- **NEW:** High contrast mode support for improved accessibility
- **NEW:** Reduced motion support for users with motion sensitivity
- **IMPROVEMENT:** Higher CSS specificity without using !important rules for better maintainability
- **IMPROVEMENT:** Responsive mobile-first design with proper touch targets (16px minimum on mobile)
- **IMPROVEMENT:** Enhanced accessibility with proper focus states, ARIA support, and keyboard navigation
- **IMPROVEMENT:** Added wrapper classes (chrmrtns-kla-form-wrapper) for better style isolation
- **FIX:** Form styling conflicts with block themes completely resolved
- **FIX:** Input field styling now consistent across all WordPress themes
- **FIX:** Button hover, active, and focus states properly styled with visual feedback
- **FIX:** Checkbox styling enhanced with custom SVG checkmark
- **COMPATIBILITY:** Full responsive design optimized for mobile devices
- **ACCESSIBILITY:** Comprehensive accessibility improvements including focus indicators and screen reader support

### v2.5.0
- **NEW:** Added redirect parameter support to [keyless-auth] shortcode - now supports custom redirects like [keyless-auth-full]
- **NEW:** Enhanced shortcode documentation in admin help with comprehensive usage examples and options
- **FIX:** Fixed critical wp-login.php redirect interference preventing standard password login from working
- **FIX:** Resolved password login issues in [keyless-auth-full] shortcode caused by form submission conflicts
- **FIX:** Fixed WordPress coding standards violations - added proper phpcs:ignore comments for nonce verification warnings
- **IMPROVEMENT:** Enhanced form submission handling to prevent conflicts between magic link and standard WordPress login
- **IMPROVEMENT:** Updated admin help documentation with detailed shortcode options and usage examples
- **IMPROVEMENT:** Better hook timing using 'init' instead of 'template_redirect' for improved WordPress compatibility
- **IMPROVEMENT:** Enhanced wp-login.php redirect logic to preserve POST requests while redirecting GET requests
- **SECURITY:** Improved form identification system to prevent cross-form processing interference
- **COMPATIBILITY:** Both [keyless-auth] and [keyless-auth-full] now fully support password and magic link authentication

### v2.4.2
- **RESTORED:** Full 2FA authentication functionality - all hooks and methods reactivated
- **NEW:** Magic login integration on wp-login.php with clean form positioning in footer
- **NEW:** Immediate email notifications when 2FA is enabled or roles are configured to require 2FA
- **NEW:** Resend button in mail logs for troubleshooting email delivery issues
- **NEW:** Fix Pending Status button to resolve stuck email log statuses
- **FIX:** Resolved username field jumping issue that was causing 2FA validation errors
- **FIX:** Fixed SMTP mail logging false positive - now properly tracks pending/sent/failed status
- **FIX:** Fixed mail logs "Clear All Logs" button not working due to missing nonce verification
- **FIX:** Fixed magic login redirecting to 2FA when user is still in grace period
- **FIX:** Restored custom 2FA verification form with better styling (own page, not wp-login.php)
- **FIX:** Fixed PHP fatal errors - corrected undefined method calls in 2FA verification
- **FIX:** Optimized 2FA notification emails for better inbox delivery - removed spam trigger words
- **FIX:** Updated 2FA email template to use login page URL instead of admin panel direct links
- **FIX:** Removed broken emoji display in email templates that appeared as corrupted characters
- **IMPROVEMENT:** Clean magic login form styling with proper spacing and responsive design
- **IMPROVEMENT:** Spam-filter-friendly 2FA email content with softened language and removed trigger words
- **IMPROVEMENT:** Email notifications now sent immediately when 2FA settings change (system enabled, roles added, user role changed)
- **SECURITY:** Fixed all WordPress coding standards warnings - proper nonce verification, input sanitization, and translator comments
- **SECURITY:** Enhanced email template security with better content sanitization
- **COMPATIBILITY:** Both normal login and magic login work seamlessly without conflicts
- **PERFORMANCE:** Optimized 2FA verification flow with proper token cleanup and database operations

### v2.4.1
- **PATCH:** Temporarily disabled 2FA authentication hooks to resolve login conflicts - emergency mode and grace period functionality fully operational
- **IMPROVEMENT:** Enhanced grace period notices with dynamic colors and emojis based on urgency (red for <3 days, yellow for 4-7 days, blue for 8+ days)
- **FIX:** Removed all debug code to comply with WordPress.org Plugin Check requirements
- **FIX:** Fixed timezone function warnings by removing development date() calls
- **FIX:** Removed .DS_Store hidden files for full WordPress.org compliance
- **FIX:** Implemented proper singleton pattern to prevent multiple class instantiation
- **STABILITY:** Clean, production-ready code with all WordPress.org compliance issues resolved

### v2.4.0
- **NEW:** Complete Two-Factor Authentication (2FA) system with TOTP support using Google Authenticator, Authy, or similar apps
- **NEW:** QR code generation for easy 2FA setup with automatic secret key generation
- **NEW:** Role-based 2FA requirements - configure specific user roles to require 2FA authentication
- **NEW:** Dedicated 2FA user management page with search functionality and detailed user statistics
- **NEW:** Customizable login and post-login redirect URLs for enhanced user experience
- **SECURITY:** Enhanced magic link security - proper 2FA integration prevents authentication bypass vulnerability
- **CRITICAL:** Token expiration timezone issue - fixed UTC/local timezone mismatch causing premature token expiry
- **FIX:** 2FA users page header rendering - consistent styling across all admin pages
- **IMPROVEMENT:** Comprehensive session management for 2FA verification flow
- **IMPROVEMENT:** Frontend and admin context compatibility for 2FA verification forms
- **IMPROVEMENT:** Clean admin interface with removal of duplicate user management sections

### v2.3.1
- **FIX:** Fixed inconsistent header styling on Options and Help admin pages
- **FIX:** Admin CSS and JavaScript now properly loaded on all admin pages
- **IMPROVEMENT:** Consistent 40x40px logo display across all admin interfaces

### v2.3.0
- **NEW:** WordPress Login Integration - Added optional magic login field to wp-login.php with enable/disable toggle
- **NEW:** Options Settings Page - Dedicated Options page for enabling/disabling wp-login.php integration and other features
- **NEW:** Help & Instructions Page - Comprehensive help system with getting started guide, security features overview, and troubleshooting
- **IMPROVEMENT:** Enhanced admin menu structure with clearer navigation between Templates, Options, and Help sections
- **IMPROVEMENT:** Better user onboarding with step-by-step instructions and common issue solutions
- **IMPROVEMENT:** Streamlined settings organization for easier plugin configuration and management

### v2.2.1
- **SECURITY:** WordPress.org Plugin Check compliance - Fixed all remaining security warnings
- **FIX:** Database query preparation - Added proper phpcs annotations for legitimate direct queries
- **FIX:** Timezone-dependent functions - Changed date() to gmdate() for consistency
- **IMPROVEMENT:** Enhanced code documentation - Clear explanations for security exceptions
- **IMPROVEMENT:** Database operation safety - Comprehensive phpcs disable comments for custom table management

### v2.2.0
- **MAJOR:** Custom database architecture - Migrated from wp_options storage to dedicated database tables for better scalability
- **NEW:** Login audit log table - Comprehensive tracking of login attempts with IP addresses, device types, user agents, and timestamps
- **NEW:** Enhanced mail logs table - Advanced email tracking with status monitoring, SMTP responses, and delivery insights
- **NEW:** Secure token storage table - Dedicated table for login tokens with automatic expiration and cleanup
- **NEW:** Device tracking table - Foundation for future 2FA and companion app features
- **NEW:** Webhook logs table - Infrastructure for future webhook support and external integrations
- **IMPROVEMENT:** Performance optimization - Reduced database overhead by moving high-volume data out of wp_posts and wp_options
- **IMPROVEMENT:** Enhanced security - Better audit trails with detailed login attempt monitoring and device fingerprinting
- **IMPROVEMENT:** Backwards compatibility - Automatic detection and fallback to legacy systems for seamless upgrades
- **IMPROVEMENT:** Database indexing - Optimized queries with proper indexes for better performance at scale
- **IMPROVEMENT:** Automatic cleanup - Built-in maintenance routines for expired tokens and old log entries
- **DEVELOPER:** Modular database class - Clean separation of database operations with comprehensive error handling
- **DEVELOPER:** Migration system - Automatic database version management and upgrade handling

### v2.1.1
- **FIX:** Replaced all "Passwordless Authentication" references with "Keyless Auth" for consistent branding
- **FIX:** Updated nonce names from passwordless_login_request to keyless_login_request
- **FIX:** Changed SMTP test email subject/message to use "Keyless Auth" branding
- **FIX:** Updated installation instructions to reference correct "keyless-auth" folder name
- **FIX:** Fixed menu references from "PA Settings" to "Templates" in documentation
- **FIX:** Updated GitHub repository URLs from passwordless-auth to keyless-auth
- **IMPROVEMENT:** Regenerated translation template (keyless-auth.pot) with current strings only
- **IMPROVEMENT:** Removed obsolete translation strings from original fork

### v2.1.0
- **NEW:** Optional "From Email" field in SMTP settings - Allows specifying a different sender email address when SMTP username differs from desired sender
- **IMPROVEMENT:** Enhanced SMTP configuration flexibility - Supports scenarios where SMTP authentication uses one email but sender should appear as another
- **IMPROVEMENT:** Maintains proper email deliverability with Message-ID domain alignment for SPF/DKIM/DMARC compliance
- **IMPROVEMENT:** Backwards compatible - If From Email field is empty, uses SMTP username as before

### v2.0.12
- **FIX:** Added plugin action links for quick settings access from WordPress plugin list
- **FIX:** Mail logs "View Content" button functionality - Added missing JavaScript functions
- **IMPROVEMENT:** Enhanced admin JavaScript with global scope functions for mail logs interaction
- **IMPROVEMENT:** Fixed settings link URL to use correct "keyless-auth" slug instead of internal prefix
- **NEW:** SMTP cache management - Added "Clear SMTP Cache" button to resolve configuration issues
- **IMPROVEMENT:** Enhanced email deliverability - Message-ID domain now matches authenticated sender for better SPF/DKIM/DMARC alignment
- **IMPROVEMENT:** Automatic cache clearing - SMTP settings now automatically clear cache when saved
- **NEW:** Bulk delete mail logs - Added checkbox selection system with "Select All" for bulk operations
- **IMPROVEMENT:** WordPress-style bulk actions dropdown for familiar mail log management experience

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

### v2.0.0
- **NEW:** Complete rebrand to "Keyless Auth" with chrmrtns prefix by Chris Martens
- **NEW:** Dedicated top-level admin menu "Keyless Auth" with Settings submenu
- **NEW:** Visual template selection with live previews of each email template
- **NEW:** WYSIWYG email editor with HTML support for custom templates
- **NEW:** Advanced color controls supporting hex, RGB, HSL, HSLA formats
- **NEW:** Separate color customization for buttons, button hover, and text links
- **NEW:** Enhanced HTML sanitization allowing email-safe tags and attributes
- **NEW:** Template help section with placeholder documentation and HTML examples
- **NEW:** Improved email templates with better styling and link color support
- **NEW:** Color picker and text input synchronization for flexible color entry
- **REMOVED:** Profile Builder promotional section completely eliminated
- **SECURITY:** Fixed timing attack vulnerability in token comparison
- **SECURITY:** Added proper REQUEST_URI validation in URL generation
- **SECURITY:** Consistent input sanitization using $_GET instead of $_REQUEST
- **SECURITY:** Added validation before user meta deletion
- **SECURITY:** Enhanced HTML sanitization for email content
- **IMPROVEMENT:** Updated all function prefixes from wpa_ to chrmrtns_
- **IMPROVEMENT:** Updated shortcode to [chrmrtns-passwordless-auth]
- **IMPROVEMENT:** Enhanced email styling with responsive design
- **IMPROVEMENT:** Better error handling and validation throughout
- **IMPROVEMENT:** Dynamic TinyMCE editor initialization for better compatibility

### v1.1.3
- **FIX:** XSS issue with the already logged in message. Thanks to Mat Rollings
- **FIX:** Added nonce check for the admin notice dismiss action
- **FIX:** Sanitize additional output
- **FIX:** A compatibility bug with Profile Builder when an after login redirect returned an empty string

### v1.1.2
- **FIX:** Issues with form being processed multiple times
- **FIX:** An issue regarding AV Link Protection
- **MISC:** Added a filter over the headers of the email that is sent: wpa_email_headers
- **MISC:** Added a filter to allow adding of extra email verification logic: wpa_email_verify_login

### v1.1.1
- **IMPROVEMENT:** Redirect after login based on Profile Builder Pro custom redirects

### v1.1.0
- **FIX:** create_function to anonymous function so it works with PHP 7.2
- **IMPROVEMENT:** Localize certain strings
- **NEW:** Add wpa_after_login_redirect filter so you can redirect users after login
- **IMPROVEMENT:** Change logo and banner

### v1.0.9
- **FIX:** Fixed a problem with admin approval error message

### v1.0.8
- **NEW:** Added compatibility with Admin Approval from Profile Builder

### v1.0.7
- **FIX:** Properly localize plugin again. Changed the text domain to be the same with the slug

### v1.0.6
- **FIX:** Properly localize plugin

### v1.0.5
- **FIX:** Fixed an issue with the Email Content Type. Now we are using the wp_mail_content_type filter to set this
- **SECURITY:** Plugin security improvements

### v1.0.4
- **FIX:** Remove email 'from' filter. Should use wp_mail_from filter
- **NEW:** Added support for HTML inside the e-mail that gets sent
- **NEW:** Added the wpa_change_link_expiration filter to be able to change the lifespan of the token
- **NEW:** Added the wpa_change_form_label to be able to change the label for the login form
- **FIX:** Generating the url using add_query_args() function

### v1.0.3
- **FIX:** Minor readme change

### v1.0.2
- **FIX:** Added require_once for the PasswordHash class

### v1.0.1
- **SECURITY:** Tokens are now hashed in the database
- **SECURITY:** Sanitized the input fields data
- **FIX:** No longer using transients. Now using user_meta with an expiration meta since transients are not to be trusted
- **IMPROVEMENT:** Removed a br tag

### v1.0
- **NEW:** Initial version. Added a passwordless login form as a shortcode

## 🌐 WordPress.org Plugin

This plugin is officially available on WordPress.org!

[![WordPress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/keyless-auth.svg)](https://wordpress.org/plugins/keyless-auth/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/keyless-auth.svg)](https://wordpress.org/plugins/keyless-auth/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/stars/keyless-auth.svg)](https://wordpress.org/plugins/keyless-auth/)

**[📦 Install from WordPress.org](https://wordpress.org/plugins/keyless-auth/)**

## 🤝 Contributing

## 📚 Documentation & Support

### Getting Help
- **WordPress.org Support Forum**: Primary support channel for plugin issues
- **GitHub Repository**: Bug reports and feature requests welcome
- **WordPress.org Plugin Page**: [https://wordpress.org/plugins/keyless-auth/](https://wordpress.org/plugins/keyless-auth/)

### Requirements
- **WordPress**: 5.6 or higher (tested up to 6.8)
- **PHP**: 7.4 or higher
- **Email Delivery**: SMTP recommended for reliability

Issues and pull requests are welcome on [GitHub](https://github.com/chrmrtns/keyless-auth).

## 📄 License

This plugin is licensed under the [GPL v2](https://www.gnu.org/licenses/gpl-2.0.html) or later.

## 👨‍💻 Author

**Chris Martens**
- GitHub: [@chrmrtns](https://github.com/chrmrtns)
- Plugin URI: [https://github.com/chrmrtns/keyless-auth](https://github.com/chrmrtns/keyless-auth)
- WordPress.org: [https://wordpress.org/plugins/keyless-auth/](https://wordpress.org/plugins/keyless-auth/)

---

⭐ **If this plugin helps you, please consider giving it a star on GitHub and a rating on WordPress.org!**